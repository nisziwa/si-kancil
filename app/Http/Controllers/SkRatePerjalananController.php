<?php

namespace App\Http\Controllers;

use App\Models\SkRatePerjalanan;
use App\Models\SkRatePerjalananHistory;
use Illuminate\Http\Request;

class SkRatePerjalananController extends Controller
{
    public function index(Request $request)
    {
        $query = SkRatePerjalanan::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kecamatan', 'like', '%' . $search . '%')
                    ->orWhere('ibukota_kecamatan', 'like', '%' . $search . '%')
                    ->orWhere('keterangan', 'like', '%' . $search . '%');
            });
        }

        $rates = $query->orderBy('kecamatan')->paginate(15)->withQueryString();

        return view('sk_rates.index', compact('rates'));
    }

    public function create()
    {
        return view('sk_rates.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateRate($request);

        $rate = SkRatePerjalanan::create($validated);

        SkRatePerjalananHistory::create([
            'sk_rate_perjalanan_id' => $rate->id,
            'data_sebelum' => null,
            'data_sesudah' => json_encode($this->snapshot($rate)),
            'aksi' => 'create',
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('sk-rates.index')->with('success', 'SK Rate Perjalanan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $rate = SkRatePerjalanan::with('histories.user')->findOrFail($id);

        return view('sk_rates.edit', compact('rate'));
    }

    public function update(Request $request, $id)
    {
        $rate = SkRatePerjalanan::findOrFail($id);
        $validated = $this->validateRate($request);

        $dataSebelum = $this->snapshot($rate);

        $rate->update($validated);

        SkRatePerjalananHistory::create([
            'sk_rate_perjalanan_id' => $rate->id,
            'data_sebelum' => json_encode($dataSebelum),
            'data_sesudah' => json_encode($this->snapshot($rate)),
            'aksi' => 'update',
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('sk-rates.index')->with('success', 'SK Rate Perjalanan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $rate = SkRatePerjalanan::findOrFail($id);

        SkRatePerjalananHistory::create([
            'sk_rate_perjalanan_id' => $rate->id,
            'data_sebelum' => json_encode($this->snapshot($rate)),
            'data_sesudah' => null,
            'aksi' => 'delete',
            'user_id' => $request->user()->id,
        ]);

        $rate->delete();

        return redirect()->route('sk-rates.index')->with('success', 'SK Rate Perjalanan berhasil dihapus.');
    }

    protected function validateRate(Request $request): array
    {
        return $request->validate([
            'kecamatan' => 'required|string|max:255',
            'ibukota_kecamatan' => 'required|string|max:255',
            'besaran_biaya_transport' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string|max:2000',
        ], [
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'ibukota_kecamatan.required' => 'Ibukota Kecamatan wajib diisi.',
            'besaran_biaya_transport.required' => 'Besaran biaya transport wajib diisi.',
            'besaran_biaya_transport.numeric' => 'Besaran biaya transport harus berupa angka.',
        ]);
    }

    protected function snapshot(SkRatePerjalanan $rate): array
    {
        return [
            'kecamatan' => $rate->kecamatan,
            'ibukota_kecamatan' => $rate->ibukota_kecamatan,
            'besaran_biaya_transport' => $rate->besaran_biaya_transport,
            'keterangan' => $rate->keterangan,
        ];
    }
}
