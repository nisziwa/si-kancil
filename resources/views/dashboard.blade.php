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

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
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
                                        <div class="flex justify-between items-start gap-2">
                                            <label class="inline-flex items-center cursor-pointer select-none" title="Pilih FPA untuk aksi bulk">
                                                <input type="checkbox" class="fpa-kanban-check rounded border-gray-300 text-indigo-600"
                                                       value="{{ $fpa->id }}" data-nomor="{{ $fpa->nomor_fpa ?: 'Belum ada nomor FPA' }}"
                                                       data-status="{{ $fpa->status_spj }}">
                                                <span class="sr-only">Pilih FPA</span>
                                            </label>
                                            <span class="text-xs font-bold text-blue-600 flex-1">
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

            <!-- Bulk Move Kanban Cards -->
            <div id="kanban-bulk-bar" class="hidden bg-indigo-600 text-white rounded-lg shadow-lg p-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-center gap-2 flex-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" id="kanban-select-all" class="rounded border-gray-300 text-white">
                            <span class="text-sm font-semibold">Pilih Semua</span>
                        </label>
                        <span class="text-sm" id="kanban-selected-count">0 dipilih</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="kanban-bulk-status" class="block w-full text-sm border-gray-300 rounded-md shadow-sm text-gray-800">
                            <option value="">-- Pilih Status Tujuan --</option>
                            @foreach(\App\Models\Request::STATUS_LIST as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="kanban-bulk-move"
                                class="bg-white text-indigo-700 font-bold py-2 px-4 rounded text-sm hover:bg-indigo-50">
                            Pindahkan
                        </button>
                        <button type="button" id="kanban-bulk-clear"
                                class="text-white hover:bg-indigo-700 font-semibold py-2 px-3 rounded text-sm">
                            Batal
                        </button>
                    </div>
                </div>
                <div id="kanban-bulk-result" class="hidden mt-3 p-3 rounded bg-white text-gray-800 text-sm"></div>
            </div>

            <!-- 4. TABEL RINGKASAN FPA -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Tabel Ringkasan Dokumen FPA</h3>

                <!-- Bulk Move Kanban FPA -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 mb-4 p-3 bg-indigo-50 rounded border border-indigo-200">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pilih FPA</label>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="bulk-select-all" class="rounded border-gray-300 text-indigo-600">
                            <span class="text-xs text-gray-600">Semua ({{ $fpaRequests->count() }})</span>
                        </div>
                    </div>
                    <div>
                        <label for="bulk-status" class="block text-xs font-semibold text-gray-600 uppercase mb-1">Status Tujuan</label>
                        <select id="bulk-status" class="block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Pilih Status --</option>
                            @foreach(\App\Models\Request::STATUS_LIST as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="bulk-move-btn"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                        Pindahkan Terpilih
                    </button>
                </div>

                <!-- Hasil Bulk Move -->
                <div id="bulk-result" class="hidden mb-4 p-4 rounded border text-sm"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 uppercase text-xs w-10">
                                    <input type="checkbox" id="table-select-all" class="rounded border-gray-300 text-indigo-600">
                                </th>
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
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" class="bulk-fpa-check rounded border-gray-300 text-indigo-600" value="{{ $fpa->id }}" data-nomor="{{ $fpa->nomor_fpa ?: 'Belum ada nomor FPA' }}">
                                    </td>
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
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500 italic">
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

    <!-- Modal Peringatan Kanban -->
    <div id="kanban-warning-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-3 border-b border-red-200 bg-red-50 rounded-t-lg">
                <h4 class="font-bold text-red-800">Status tidak dapat diubah</h4>
                <button type="button" id="kanban-warning-close" class="text-red-500 hover:text-red-800 font-bold text-lg leading-none">&times;</button>
            </div>
            <div class="px-5 py-4">
                <p class="text-sm text-gray-700 mb-3" id="kanban-warning-nomor"></p>
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Alasan:</p>
                <ul id="kanban-warning-reasons" class="list-disc pl-5 text-sm text-gray-700 space-y-1"></ul>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 flex justify-end">
                <button type="button" id="kanban-warning-ok"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Script SortableJS untuk FPA Kanban -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const columns = document.querySelectorAll('.fpa-kanban-items');
            const toast = document.getElementById('ajax-toast');
            const modal = document.getElementById('kanban-warning-modal');
            const modalNomor = document.getElementById('kanban-warning-nomor');
            const modalReasons = document.getElementById('kanban-warning-reasons');
            let modalTimer = null;

            function closeWarning() {
                if (modalTimer) clearTimeout(modalTimer);
                modal.classList.add('hidden');
            }

            document.getElementById('kanban-warning-close').addEventListener('click', closeWarning);
            document.getElementById('kanban-warning-ok').addEventListener('click', closeWarning);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeWarning();
            });

            function showWarning(nomorFpa, reasonText) {
                // Tampilkan alasan dari data.errors bila ada, jika tidak pakai message.
                let reasons = [];
                if (Array.isArray(reasonText)) {
                    reasons = reasonText;
                } else {
                    reasons = [reasonText];
                }
                modalNomor.textContent = 'FPA: ' + nomorFpa;
                modalReasons.innerHTML = '';
                reasons.forEach(function (r) {
                    const li = document.createElement('li');
                    li.textContent = r;
                    modalReasons.appendChild(li);
                });
                modal.classList.remove('hidden');
                // Tahan minimal 8 detik sebelum otomatis menutup.
                if (modalTimer) clearTimeout(modalTimer);
                modalTimer = setTimeout(closeWarning, 8000);
            }

            function showToast(msg, ok) {
                toast.textContent = msg;
                toast.classList.remove('hidden');
                if (ok) {
                    toast.classList.remove('bg-red-100', 'text-red-800');
                    toast.classList.add('bg-green-100', 'text-green-800');
                    setTimeout(() => toast.classList.add('hidden'), 3000);
                } else {
                    toast.classList.remove('bg-green-100', 'text-green-800');
                    toast.classList.add('bg-red-100', 'text-red-800');
                    setTimeout(() => toast.classList.add('hidden'), 6000);
                }
            }

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
                        const fromColumn = evt.from ? evt.from.closest('.fpa-kanban-column') : null;

                        fetch(`/requests/${fpaId}/status-ajax`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(response => response.json().then(data => ({ ok: response.ok, data })))
                        .then(({ ok, data }) => {
                            if (data && data.success) {
                                showToast(`Status FPA diubah ke: ${newStatus}`, true);
                                return;
                            }
                            // Gagal: kembalikan card ke kolom asal TANPA reload.
                            if (fromColumn && fromColumn !== newColumn) {
                                fromColumn.querySelector('.fpa-kanban-items').appendChild(itemEl);
                            }
                            const nomorFpa = (data && data.nomor_fpa) ? data.nomor_fpa : 'Belum ada nomor FPA';
                            const reasons = (data && data.errors) ? data.errors : ((data && data.message) ? data.message : 'Status tidak dapat diubah.');
                            showWarning(nomorFpa, reasons);
                        })
                        .catch(err => {
                            console.error(err);
                            if (fromColumn && fromColumn !== newColumn) {
                                fromColumn.querySelector('.fpa-kanban-items').appendChild(itemEl);
                            }
                            showWarning('Belum ada nomor FPA', 'Terjadi kesalahan koneksi. Silakan coba lagi.');
                        });
                    }
                });
            });

            /* ---------- Bulk Move Kanban FPA ---------- */
            const fpaChecks = () => document.querySelectorAll('.bulk-fpa-check:checked');
            const bulkStatus = document.getElementById('bulk-status');
            const bulkResult = document.getElementById('bulk-result');

            function syncSelectAll() {
                const all = document.querySelectorAll('.bulk-fpa-check');
                const checked = fpaChecks();
                const tableAll = document.getElementById('table-select-all');
                const bulkAll = document.getElementById('bulk-select-all');
                if (tableAll) tableAll.checked = all.length > 0 && checked.length === all.length;
                if (bulkAll) bulkAll.checked = all.length > 0 && checked.length === all.length;
            }

            document.querySelectorAll('.bulk-fpa-check').forEach(c => c.addEventListener('change', syncSelectAll));

            function bindSelectAll(selectAllId, targetId) {
                const btn = document.getElementById(selectAllId);
                const target = document.getElementById(targetId);
                if (!btn || !target) return;
                btn.addEventListener('change', function () {
                    document.querySelectorAll(target).forEach(c => { c.checked = btn.checked; });
                });
            }
            bindSelectAll('table-select-all', '.bulk-fpa-check');
            bindSelectAll('bulk-select-all', '.bulk-fpa-check');

            document.getElementById('bulk-move-btn').addEventListener('click', function () {
                const checked = fpaChecks();
                if (checked.length === 0) {
                    bulkResult.classList.remove('hidden');
                    bulkResult.className = 'hidden mb-4 p-4 rounded border text-sm bg-red-50 border-red-200 text-red-700';
                    bulkResult.classList.remove('hidden');
                    bulkResult.innerHTML = 'Pilih minimal satu FPA terlebih dahulu.';
                    return;
                }
                const status = bulkStatus.value;
                if (!status) {
                    bulkResult.classList.remove('hidden');
                    bulkResult.className = 'hidden mb-4 p-4 rounded border text-sm bg-red-50 border-red-200 text-red-700';
                    bulkResult.classList.remove('hidden');
                    bulkResult.innerHTML = 'Pilih status tujuan terlebih dahulu.';
                    return;
                }

                const ids = Array.from(checked).map(c => c.value);

                fetch(`{{ route('requests.status.bulk') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: ids, status: status })
                })
                .then(res => res.json().then(data => ({ ok: res.ok, data })))
                .then(({ data }) => {
                    let html = '';
                    const succ = (data.results && data.results.success) || [];
                    const fail = (data.results && data.results.failed) || [];
                    if (succ.length > 0) {
                        html += '<div class="mb-2 font-semibold text-green-800">Berhasil:</div>';
                        html += '<ul class="list-disc pl-5">';
                        succ.forEach(s => html += `<li>${s.nomor_fpa} → ${s.status}</li>`);
                        html += '</ul>';
                    }
                    if (fail.length > 0) {
                        html += '<div class="mb-2 mt-2 font-semibold text-red-800">Gagal:</div>';
                        html += '<ul class="list-disc pl-5">';
                        fail.forEach(f => html += `<li><strong>${f.nomor_fpa}</strong>: ${(f.errors || []).join(' ')}</li>`);
                        html += '</ul>';
                    }
                    bulkResult.className = 'mb-4 p-4 rounded border text-sm '
                        + (fail.length > 0 ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800');
                    bulkResult.innerHTML = html || 'Tidak ada perubahan.';
                    bulkResult.classList.remove('hidden');
                })
                .catch(err => {
                    console.error(err);
                    bulkResult.className = 'mb-4 p-4 rounded border text-sm bg-red-50 border-red-200 text-red-800';
                    bulkResult.innerHTML = 'Terjadi kesalahan saat memproses.';
                    bulkResult.classList.remove('hidden');
                    bulkResult.classList.add('block');
                });
            });

            /* ---------- Bulk Select & Move Kanban Cards ---------- */
            const kanbanChecks = () => Array.from(document.querySelectorAll('.fpa-kanban-check'));
            const kanbanChecked = () => kanbanChecks().filter(c => c.checked);
            const kanbanBulkBar = document.getElementById('kanban-bulk-bar');
            const kanbanCount = document.getElementById('kanban-selected-count');
            const kanbanStatus = document.getElementById('kanban-bulk-status');
            const kanbanResult = document.getElementById('kanban-bulk-result');
            const kanbanSelectAll = document.getElementById('kanban-select-all');

            function updateKanbanBulkBar() {
                const n = kanbanChecked().length;
                if (kanbanCount) kanbanCount.textContent = n + ' dipilih';
                if (kanbanBulkBar) kanbanBulkBar.classList.toggle('hidden', n === 0);
                if (kanbanSelectAll) kanbanSelectAll.checked = n > 0 && n === kanbanChecks().length;
                if (n === 0 && kanbanResult) {
                    kanbanResult.classList.add('hidden');
                    kanbanResult.innerHTML = '';
                }
            }

            kanbanChecks().forEach(cb => cb.addEventListener('change', updateKanbanBulkBar));

            if (kanbanSelectAll) {
                kanbanSelectAll.addEventListener('change', function () {
                    const state = kanbanSelectAll.checked;
                    kanbanChecks().forEach(cb => { cb.checked = state; });
                    updateKanbanBulkBar();
                });
            }

            document.getElementById('kanban-bulk-clear').addEventListener('click', function () {
                kanbanChecks().forEach(cb => { cb.checked = false; });
                if (kanbanStatus) kanbanStatus.value = '';
                updateKanbanBulkBar();
            });

            document.getElementById('kanban-bulk-move').addEventListener('click', function () {
                const checked = kanbanChecked();
                if (checked.length === 0) {
                    showKanbanBulkResult([], [], 'Pilih minimal satu FPA di kanban terlebih dahulu.');
                    return;
                }
                const status = kanbanStatus.value;
                if (!status) {
                    showKanbanBulkResult([], [], 'Pilih status tujuan terlebih dahulu.');
                    return;
                }

                const ids = checked.map(c => c.value);
                const nomorMap = {};
                checked.forEach(c => { nomorMap[c.value] = c.dataset.nomor; });

                fetch(`{{ route('requests.status.bulk') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ids: ids, status: status })
                })
                .then(res => res.json().then(data => ({ ok: res.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data || !data.results) {
                        showKanbanBulkResult([], [], 'Terjadi kesalahan saat memproses.');
                        return;
                    }
                    const succ = data.results.success || [];
                    const fail = data.results.failed || [];
                    showKanbanBulkResult(succ, fail, null);
                    // Hapus seleksi kartu yang berhasil dipindahkan, sisakan yang gagal.
                    const movedIds = new Set(succ.filter(s => s.changed).map(s => s.nomor_fpa));
                    // Nomor FPA unik sebagai kunci; jika sama, tandai via API id bila tersedia.
                    const failedNomor = new Set(fail.map(f => f.nomor_fpa));
                    kanbanChecks().forEach(cb => {
                        if (failedNomor.has(cb.dataset.nomor)) return;
                        cb.checked = false;
                    });
                    updateKanbanBulkBar();
                })
                .catch(err => {
                    console.error(err);
                    showKanbanBulkResult([], [], 'Terjadi kesalahan saat memproses.');
                });
            });

            function showKanbanBulkResult(succ, fail, directMsg) {
                if (!kanbanResult) return;
                let html = '';
                if (directMsg) {
                    html = `<div class="font-semibold text-red-700">${directMsg}</div>`;
                    kanbanResult.className = 'mt-3 p-3 rounded bg-white text-gray-800 text-sm border border-red-200';
                } else {
                    if (succ.length > 0) {
                        html += '<div class="mb-1 font-semibold text-green-800">Berhasil:</div>';
                        html += '<ul class="list-disc pl-5 mb-2">';
                        succ.forEach(s => html += `<li>${s.nomor_fpa} → ${s.status}${s.changed ? '' : ' (tanpa perubahan)'}</li>`);
                        html += '</ul>';
                    }
                    if (fail.length > 0) {
                        html += '<div class="mb-1 font-semibold text-red-800">Gagal:</div>';
                        html += '<ul class="list-disc pl-5">';
                        fail.forEach(f => html += `<li><strong>${f.nomor_fpa}</strong>: ${(f.errors || []).join(' ')}</li>`);
                        html += '</ul>';
                    }
                    html = html || 'Tidak ada perubahan.';
                    kanbanResult.className = 'mt-3 p-3 rounded bg-white text-gray-800 text-sm border '
                        + (fail.length > 0 ? 'border-red-200' : 'border-green-200');
                }
                kanbanResult.innerHTML = html;
                kanbanResult.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
