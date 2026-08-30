<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard Kendali SPJ') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('requests.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    + Tambah FPA Baru
                </a>
                <a href="{{ route('calendar.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                    📅 Kalender Kegiatan
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <!-- 1. STATISTIC CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 font-semibold uppercase">Total FPA</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-gray-400">
                    <p class="text-xs text-gray-500 font-semibold uppercase">Persiapan</p>
                    <p class="text-2xl font-extrabold text-gray-700 mt-1">{{ $stats['persiapan'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs text-gray-500 font-semibold uppercase">Dikirim ke PPK</p>
                    <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $stats['dikirim_ppk'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 font-semibold uppercase">Perbaikan</p>
                    <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $stats['perbaikan'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 font-semibold uppercase">Selesai</p>
                    <p class="text-2xl font-extrabold text-green-600 mt-1">{{ $stats['selesai'] }}</p>
                </div>
            </div>

            <!-- 2. FILTER FORM (Bulan, Tahun, Pencarian) -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="bulan" class="block text-xs font-semibold text-gray-600 uppercase">Bulan</label>
                        <select name="bulan" id="bulan" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="all" {{ $currentMonth === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (string)$currentMonth === (string)$m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->locale('id')->isoFormat('MMMM') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label for="tahun" class="block text-xs font-semibold text-gray-600 uppercase">Tahun</label>
                        <select name="tahun" id="tahun" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="all" {{ $currentYear === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                            @for($y = 2024; $y <= 2030; $y++)
                                <option value="{{ $y }}" {{ (string)$currentYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label for="search" class="block text-xs font-semibold text-gray-600 uppercase">Pencarian</label>
                        <input type="text" name="search" id="search" value="{{ $search }}" placeholder="No FPA / Kegiatan / Lokasi..." class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded text-sm flex-1">
                            Filter
                        </button>
                        <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- 3. KANBAN 4 KOLOM FPA INTERAKTIF -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Kanban Monitoring Posisi SPJ</h3>
                        <p class="text-xs text-gray-500">Geser card FPA antar kolom sesuai alur status yang diperbolehkan.</p>
                    </div>
                    <span id="ajax-toast" class="hidden text-xs bg-green-100 text-green-800 px-3 py-1 rounded font-semibold transition-all"></span>
                </div>

                @php
                    $columnColors = [
                        'Persiapan' => 'bg-gray-100 border-gray-300 text-gray-700',
                        'Dikirim ke PPK' => 'bg-indigo-50 border-indigo-200 text-indigo-800',
                        'Perbaikan' => 'bg-red-50 border-red-200 text-red-800',
                        'Selesai' => 'bg-green-50 border-green-200 text-green-800',
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3" id="fpa-kanban-board">
                    @foreach($statuses as $status)
                        <div class="fpa-kanban-column flex flex-col rounded-lg {{ $columnColors[$status] }} border p-2 min-h-[400px]" data-status="{{ $status }}">
                            <div class="flex justify-between items-center mb-2 px-1">
                                <h4 class="font-bold text-xs uppercase">{{ $status }}</h4>
                                <span class="text-xs font-semibold px-1.5 py-0.5 bg-white rounded shadow-xs">
                                    {{ $fpaRequests->where('status_spj', $status)->count() }}
                                </span>
                            </div>

                            <div class="fpa-kanban-items flex-1 space-y-2 overflow-y-auto max-h-[600px] p-1">
                                @foreach($fpaRequests->where('status_spj', $status) as $fpa)
                                    @php
                                        $progress = $fpa->checklist_progress;
                                        $priority = $fpa->priority_info;
                                    @endphp
                                    <div class="fpa-card bg-white p-3 rounded shadow-xs border border-gray-200 cursor-move hover:shadow-md transition-shadow" data-id="{{ $fpa->id }}">
                                        <div class="flex justify-between items-start">
                                            <span class="text-xs font-bold text-blue-600">
                                                @if($fpa->has_nomor_fpa)
                                                    {{ $fpa->nomor_fpa }}
                                                @else
                                                    <span class="text-gray-400 italic">Belum ada nomor FPA</span>
                                                @endif
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded">
                                                {{ $fpa->expenseType->nama ?? '-' }}
                                            </span>
                                        </div>

                                        <p class="text-xs text-gray-800 font-medium mt-1 line-clamp-2">
                                            {{ $fpa->deskripsi_permintaan }}
                                        </p>

                                        <!-- Prioritas SPJ (bukan checklist) -->
                                        <div class="mt-2 pt-2 border-t">
                                            <div class="flex items-center justify-between text-[10px]">
                                                <span class="font-semibold
                                                    @if($priority['level'] === 'danger') text-red-700
                                                    @elseif($priority['level'] === 'warning') text-amber-700
                                                    @else text-gray-600 @endif">
                                                    ⚑ {{ $priority['label'] }}
                                                </span>
                                            </div>
                                            @if($fpa->deadline_spj)
                                                <div class="text-[10px] text-gray-500 mt-0.5">
                                                    Deadline: {{ $fpa->deadline_spj->format('d/m/Y') }}
                                                    @if($priority['terlambat']) <span class="text-red-600 font-bold">(Terlambat)</span> @endif
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-2 text-right">
                                            <a href="{{ route('requests.show', $fpa->id) }}" class="text-[11px] font-semibold text-indigo-600 hover:underline">
                                                Detail & SPJ →
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 4. TABEL RINGKASAN FPA -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Tabel Ringkasan Dokumen FPA</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">No FPA</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Deskripsi Kegiatan</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Deadline</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Status SPJ</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Progress Dokumen</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600 uppercase text-xs">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($fpaRequests as $fpa)
                                @php $progress = $fpa->checklist_progress; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-blue-600 whitespace-nowrap">
                                        @if($fpa->has_nomor_fpa)
                                            {{ $fpa->nomor_fpa }}
                                        @else
                                            <span class="text-gray-400 italic">Belum ada nomor FPA</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $fpa->deskripsi_permintaan }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $fpa->expenseType->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-red-600 whitespace-nowrap">{{ $fpa->deadline_spj ? $fpa->deadline_spj->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                            @if($fpa->status_spj == 'Persiapan') bg-gray-100 text-gray-800
                                            @elseif($fpa->status_spj == 'Dikirim ke PPK') bg-indigo-100 text-indigo-800
                                            @elseif($fpa->status_spj == 'Perbaikan') bg-red-100 text-red-800
                                            @elseif($fpa->status_spj == 'Selesai') bg-green-100 text-green-800
                                            @endif">
                                            {{ $fpa->status_spj }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $progress['persen'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-gray-700">{{ $progress['persen'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <a href="{{ route('requests.show', $fpa->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold text-xs">
                                            Buka Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 italic">
                                        Tidak ada data FPA yang sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Script SortableJS untuk FPA Kanban -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const columns = document.querySelectorAll('.fpa-kanban-items');
            const toast = document.getElementById('ajax-toast');

            columns.forEach(function(column) {
                new Sortable(column, {
                    group: 'fpa-shared',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: function (evt) {
                        const itemEl = evt.item;
                        const newColumn = itemEl.closest('.fpa-kanban-column');
                        const newStatus = newColumn.getAttribute('data-status');
                        const fpaId = itemEl.getAttribute('data-id');

                        fetch(`/requests/${fpaId}/status-ajax`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                toast.textContent = `Status FPA diubah ke: ${newStatus}`;
                                toast.classList.remove('hidden');
                                toast.classList.remove('bg-red-100', 'text-red-800');
                                toast.classList.add('bg-green-100', 'text-green-800');
                                setTimeout(() => toast.classList.add('hidden'), 3000);
                            } else {
                                // Tampilkan peringatan jika status diblokir (mis. belum lengkap dokumen)
                                toast.textContent = data.message || 'Status tidak dapat diubah.';
                                toast.classList.remove('hidden');
                                toast.classList.remove('bg-green-100', 'text-green-800');
                                toast.classList.add('bg-red-100', 'text-red-800');
                                setTimeout(() => toast.classList.add('hidden'), 6000);
                                // Kembalikan card ke kolom asal
                                location.reload();
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            toast.textContent = 'Terjadi kesalahan koneksi.';
                            toast.classList.remove('hidden');
                            toast.classList.remove('bg-green-100', 'text-green-800');
                            toast.classList.add('bg-red-100', 'text-red-800');
                            setTimeout(() => toast.classList.add('hidden'), 4000);
                            location.reload();
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
