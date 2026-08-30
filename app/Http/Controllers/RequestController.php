<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = FpaRequest::with(['expenseType', 'user'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_fpa', 'like', "%{$search}%")
                  ->orWhere('deskripsi_permintaan', 'like', "%{$search}%")
                  ->orWhere('periode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_spj', $request->status);
        }

        $requests = $query->paginate(10)->withQueryString();
        $statuses = FpaRequest::STATUS_LIST;

        return view('requests.index', compact('requests', 'statuses'));
    }

    public function create()
    {
        $expenseTypes = ExpenseType::where('is_active', true)->get();
        return view('requests.create', compact('expenseTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_fpa' => 'required|string|unique:requests,nomor_fpa',
            'deskripsi_permintaan' => 'required|string',
            'jenis_pengeluaran_id' => 'required|exists:expense_types,id',
            'periode' => 'required|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'nullable|string',
            'deadline_spj' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status_spj'] = 'Persiapan';

        $fpaRequest = FpaRequest::create($validated);

        return redirect()->route('requests.show', $fpaRequest->id)
            ->with('success', 'FPA berhasil dibuat. Checklist sedang di-generate.');
    }

    public function show($id)
    {
        $fpaRequest = FpaRequest::with(['expenseType', 'user', 'checklists', 'statusHistories.user'])->findOrFail($id);
        return view('requests.show', compact('fpaRequest'));
    }

    public function edit($id)
    {
        $fpaRequest = FpaRequest::findOrFail($id);
        $expenseTypes = ExpenseType::where('is_active', true)->get();

        return view('requests.edit', compact('fpaRequest', 'expenseTypes'));
    }

    public function update(Request $request, $id)
    {
        $fpaRequest = FpaRequest::findOrFail($id);

        $validated = $request->validate([
            'nomor_fpa' => 'required|string|unique:requests,nomor_fpa,' . $id,
            'deskripsi_permintaan' => 'required|string',
            'jenis_pengeluaran_id' => 'required|exists:expense_types,id',
            'periode' => 'required|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lokasi' => 'nullable|string',
            'deadline_spj' => 'nullable|date',
        ]);

        $fpaRequest->update($validated);

        return redirect()->route('requests.show', $fpaRequest->id)
            ->with('success', 'FPA berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $fpaRequest = FpaRequest::findOrFail($id);

        if ($fpaRequest->status_spj !== 'Persiapan') {
            return redirect()->back()->with('error', 'Hanya FPA dengan status Persiapan yang dapat dihapus.');
        }

        $fpaRequest->delete();

        return redirect()->route('requests.index')
            ->with('success', 'FPA berhasil dihapus.');
    }
}
