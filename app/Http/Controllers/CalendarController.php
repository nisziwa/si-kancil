<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Support\Tanggal;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events(): JsonResponse
    {
        $fpaList = FpaRequest::with('expenseType')->get();

        $statusColors = [
            'Persiapan' => '#6b7280',
            'Dikirim ke PPK' => '#4f46e5',
            'Perbaikan' => '#dc2626',
            'Selesai' => '#16a34a',
        ];

        $events = [];

        foreach ($fpaList as $fpa) {
            $startDate = $fpa->tanggal_mulai
                ? $fpa->tanggal_mulai->format('Y-m-d')
                : $fpa->created_at->format('Y-m-d');

            // FullCalendar end date is exclusive for all-day events
            $endDate = $fpa->tanggal_selesai
                ? $fpa->tanggal_selesai->copy()->addDay()->format('Y-m-d')
                : $startDate;

            $events[] = [
                'id' => $fpa->id,
                'title' => "{$fpa->nomor_fpa} - {$fpa->deskripsi_permintaan}",
                'start' => $startDate,
                'end' => $endDate,
                'url' => route('requests.show', $fpa->id),
                'backgroundColor' => $statusColors[$fpa->status_spj] ?? '#6b7280',
                'borderColor' => $statusColors[$fpa->status_spj] ?? '#6b7280',
                'allDay' => true,
                'extendedProps' => [
                    'nomor_fpa' => $fpa->nomor_fpa,
                    'status_spj' => $fpa->status_spj,
                    'lokasi' => $fpa->lokasi ?: '-',
                    'deadline' => $fpa->deadline_spj ? Tanggal::format($fpa->deadline_spj) : '-',
                    'jenis_pengeluaran' => $fpa->expenseType->nama ?? '-',
                ],
            ];
        }

        return response()->json($events);
    }
}
