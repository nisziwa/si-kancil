<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\DocumentTemplate;
use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = FpaRequest::with(['expenseType', 'user'])->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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
            'nomor_fpa' => 'nullable|string|unique:requests,nomor_fpa',
            'deskripsi_permintaan' => 'required|string',
            'jenis_pengeluaran_id' => 'required|exists:expense_types,id',
            'periode' => 'required|string|in:'.implode(',', FpaRequest::PERIOD_LIST),
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'deadline_spj' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status_spj'] = 'Persiapan';
        $validated['nomor_fpa'] = $request->filled('nomor_fpa') ? $request->input('nomor_fpa') : null;

        $fpaRequest = FpaRequest::create($validated);

        // Auto-generate Checklist SPJ dari DocumentTemplate
        $templates = DocumentTemplate::where('expense_type_id', $fpaRequest->jenis_pengeluaran_id)
            ->orderBy('urutan')
            ->get();

        foreach ($templates as $template) {
            SpjChecklist::create([
                'request_id' => $fpaRequest->id,
                'nama_dokumen' => $template->nama_dokumen,
                'status' => 'Belum Ada',
                'is_required' => $template->is_required,
                'urutan' => $template->urutan,
            ]);
        }

        return redirect()->route('requests.show', $fpaRequest->id)
            ->with('success', 'FPA berhasil dibuat dan Checklist Dokumen otomatis digenerate.');
    }

    public function show($id)
    {
        $fpaRequest = FpaRequest::with(['expenseType', 'user', 'checklists.suratTugasDetail.pelaksanas.superkendis', 'statusHistories.user'])->findOrFail($id);

        $checklistHistory = ChecklistHistory::whereIn('checklist_id', $fpaRequest->checklists->pluck('id'))
            ->with(['checklist', 'user'])
            ->orderByDesc('created_at')
            ->get();

        return view('requests.show', compact('fpaRequest', 'checklistHistory'));
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

        if ($fpaRequest->status_spj !== 'Persiapan') {
            return redirect()->route('requests.show', $fpaRequest->id)
                ->with('error', 'FPA hanya dapat diedit saat berstatus Persiapan.');
        }

        $validated = $request->validate([
            'nomor_fpa' => 'nullable|string|unique:requests,nomor_fpa,'.$id,
            'deskripsi_permintaan' => 'required|string',
            'jenis_pengeluaran_id' => 'required|exists:expense_types,id',
            'periode' => 'required|string|in:'.implode(',', FpaRequest::PERIOD_LIST),
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'deadline_spj' => 'nullable|date',
        ]);

        $validated['nomor_fpa'] = $request->filled('nomor_fpa') ? $request->input('nomor_fpa') : null;

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

    /**
     * Pengecekan nomor FPA secara langsung (live) via AJAX.
     */
    public function checkNomorFpa(Request $request)
    {
        $request->validate(['nomor' => 'nullable|string']);

        $nomor = trim($request->input('nomor', ''));
        $ignoreId = $request->input('ignore_id');

        if ($nomor === '') {
            return response()->json(['available' => true, 'message' => '']);
        }

        $query = FpaRequest::where('nomor_fpa', $nomor);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'Nomor FPA sudah digunakan.' : '',
        ]);
    }
}
