<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentMonth = $request->input('bulan', Carbon::now()->month);
        $currentYear = $request->input('tahun', Carbon::now()->year);
        $search = $request->input('search');

        // Statistik Keseluruhan
        $stats = [
            'total' => FpaRequest::count(),
            'persiapan' => FpaRequest::where('status_spj', 'Persiapan')->count(),
            'dikirim_ppk' => FpaRequest::where('status_spj', 'Dikirim ke PPK')->count(),
            'perbaikan' => FpaRequest::where('status_spj', 'Perbaikan')->count(),
            'selesai' => FpaRequest::where('status_spj', 'Selesai')->count(),
        ];

        // Query FPA dengan Filter Kanban & Tabel
        $query = FpaRequest::with(['expenseType', 'checklists', 'user']);

        if ($currentMonth && $currentMonth !== 'all') {
            $query->where(function ($q) use ($currentMonth) {
                $q->whereMonth('created_at', $currentMonth)
                    ->orWhereMonth('tanggal_mulai', $currentMonth);
            });
        }

        if ($currentYear && $currentYear !== 'all') {
            $query->where(function ($q) use ($currentYear) {
                $q->whereYear('created_at', $currentYear)
                    ->orWhereYear('tanggal_mulai', $currentYear);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_fpa', 'like', "%{$search}%")
                    ->orWhere('deskripsi_permintaan', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhere('periode', 'like', "%{$search}%");
            });
        }

        $fpaRequests = $query->orderBy('created_at', 'desc')->get();
        $statuses = FpaRequest::STATUS_LIST;

        return view('dashboard', compact(
            'stats',
            'fpaRequests',
            'currentMonth',
            'currentYear',
            'search',
            'statuses'
        ));
    }
}
