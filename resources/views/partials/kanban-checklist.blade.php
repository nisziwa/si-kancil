@php
    $columns = [
        'Belum Ada' => 'bg-gray-100',
        'Belum Lengkap' => 'bg-yellow-100',
        'Lengkap' => 'bg-green-100',
        'Perlu Perbaikan' => 'bg-red-100'
    ];
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-4" id="kanban-board">
    @foreach($columns as $status => $bgClass)
        <div class="kanban-column flex flex-col rounded-md {{ $bgClass }} p-3 min-h-[300px]" data-status="{{ $status }}">
            <h4 class="mb-3 font-bold text-center text-gray-700">{{ $status }}</h4>

            <div class="flex-1 space-y-2 kanban-items">
                @foreach($fpaRequest->checklists->where('status', $status) as $item)
                    <div class="p-3 bg-white border border-gray-200 rounded shadow-sm cursor-move kanban-item"
                         data-id="{{ $item->id }}"
                         data-nama="{{ $item->nama_dokumen }}"
                         data-laporan="{{ str_contains($item->nama_dokumen, 'Laporan Perjalanan') ? 1 : 0 }}"
                         data-status="{{ $item->status }}">
                        <p class="text-sm font-semibold">{{ $item->nama_dokumen }}</p>
                        @if($item->catatan)
                            <p class="mt-1 text-xs text-gray-500 truncate">{{ $item->catatan }}</p>
                        @endif
                        @if($item->file_path)
                            <div class="mt-1">
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="inline-flex items-center text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200 hover:underline">
                                    📎 File Dokumen
                                </a>
                            </div>
                        @endif
                        <div class="mt-2 text-right">
                            <a href="{{ route('checklists.edit', $item->id) }}" class="text-xs text-blue-600 font-medium hover:underline">Edit & Kelola Detail →</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<!-- Modal Konfirmasi Laporan Perjalanan -->
<div id="laporan-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-indigo-200 bg-indigo-50 rounded-t-lg">
            <h4 class="font-bold text-indigo-800">Konfirmasi Laporan Perjalanan</h4>
            <button type="button" id="laporan-modal-close" class="text-indigo-500 hover:text-indigo-800 font-bold text-lg leading-none">&times;</button>
        </div>
        <div class="px-5 py-4 overflow-y-auto">
            <p class="text-sm text-gray-700 mb-2">Tandai pelaksana yang laporannya Sudah/Belum Mengumpulkan. Checklist baru menjadi <strong>Lengkap</strong> bila seluruh pelaksana sudah mengumpulkan.</p>
            <div id="laporan-pelaksana-list" class="space-y-2 text-sm"></div>
            <p id="laporan-error" class="hidden mt-3 p-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm"></p>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
            <button type="button" id="laporan-modal-close2" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">Batal</button>
            <button type="button" id="laporan-modal-save" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">Simpan & Lengkapi</button>
        </div>
    </div>
</div>

<!-- Tambahkan CDN SortableJS jika belum ada di app layout -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const columns = document.querySelectorAll('.kanban-items');

        /* ---------- Modal Konfirmasi Laporan Perjalanan ---------- */
        const modal = document.getElementById('laporan-modal');
        const listEl = document.getElementById('laporan-pelaksana-list');
        const errorEl = document.getElementById('laporan-error');
        const saveBtn = document.getElementById('laporan-modal-save');
        let pendingItem = null;
        let pendingFromCol = null;
        let pendingChecklistId = null;

        function closeLaporanModal(revert = true) {
            modal.classList.add('hidden');
            if (revert && pendingItem && pendingFromCol) {
                // Kembalikan card ke kolom asal bila dibatalkan.
                pendingFromCol.querySelector('.kanban-items').appendChild(pendingItem);
            }
            pendingItem = null;
            pendingFromCol = null;
            pendingChecklistId = null;
        }

        document.getElementById('laporan-modal-close').addEventListener('click', closeLaporanModal);
        document.getElementById('laporan-modal-close2').addEventListener('click', closeLaporanModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeLaporanModal();
        });

        function openLaporanModal(itemEl, fromColumn) {
            pendingItem = itemEl;
            pendingFromCol = fromColumn;
            pendingChecklistId = itemEl.getAttribute('data-id');
            errorEl.classList.add('hidden');
            listEl.innerHTML = '<p class="text-sm text-gray-500 italic">Memuat data pelaksana...</p>';
            modal.classList.remove('hidden');

            fetch(`/checklists/${pendingChecklistId}/laporan-pelaksana`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json().then(d => ({ ok: res.ok, data: d })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    listEl.innerHTML = '<p class="text-sm text-red-600">Gagal memuat data pelaksana.</p>';
                    return;
                }
                renderLaporanList(data.pelaksanas || [], data.status_list || []);
            })
            .catch(() => {
                listEl.innerHTML = '<p class="text-sm text-red-600">Terjadi kesalahan saat memuat data.</p>';
            });
        }

        function renderLaporanList(pelaksanas, statusList) {
            if (!pelaksanas.length) {
                listEl.innerHTML = '<p class="text-sm text-gray-500 italic">Belum ada pelaksana Surat Tugas.</p>';
                saveBtn.disabled = true;
                return;
            }
            saveBtn.disabled = false;
            listEl.innerHTML = '';
            pelaksanas.forEach(function (p) {
                const row = document.createElement('div');
                row.className = 'report-row flex items-start gap-3 p-2 rounded border border-gray-200';
                row.innerHTML = `
                    <input type="checkbox" class="report-check mt-1 rounded border-gray-300 text-indigo-600" checked>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">${p.nama}</p>
                        <p class="text-xs text-gray-500">${p.nomor_surat || '-'}</p>
                    </div>
                    <select class="report-status text-xs border-gray-300 rounded-md shadow-sm">
                        ${statusList.map(s => `<option value="${s}" ${s === p.status ? 'selected' : ''}>${s}</option>`).join('')}
                    </select>
                `;
                row.dataset.pelaksanaId = p.id;
                listEl.appendChild(row);
            });
        }

        saveBtn.addEventListener('click', function () {
            const ids = [];
            const statuses = {};
            listEl.querySelectorAll('.report-row').forEach(function (row) {
                if (row.querySelector('.report-check').checked) {
                    const id = row.dataset.pelaksanaId;
                    ids.push(id);
                    statuses[id] = row.querySelector('.report-status').value;
                }
            });
            const selected = {};
            ids.forEach(id => { selected[id] = 1; });

            fetch(`/checklists/${pendingChecklistId}/laporan-pelaksana`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    checklist_id: pendingChecklistId,
                    report_status: { selected: selected, status: statuses }
                })
            })
            .then(res => res.json().then(d => ({ ok: res.ok, data: d })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    errorEl.textContent = (data && data.message) ? data.message : 'Gagal menyimpan status.';
                    errorEl.classList.remove('hidden');
                    return;
                }
                // Berhasil: tutup modal, perbarui history, pertahankan card di kolom Lengkap.
                if (data.history) {
                    const historyList = document.getElementById('history-list');
                    if (historyList) {
                        const newLi = document.createElement('li');
                        newLi.className = 'mb-2 text-sm pb-2 border-b';
                        newLi.innerHTML = `
                            <span class="font-semibold text-gray-800">${data.history.document}</span>
                            diubah ke <span class="text-blue-600">${data.history.status_baru}</span>
                            <br><span class="text-xs text-gray-500">Oleh ${data.history.user} pada ${data.history.time}</span>
                        `;
                        historyList.prepend(newLi);
                    }
                }
                closeLaporanModal(false);
            })
            .catch(() => {
                errorEl.textContent = 'Terjadi kesalahan koneksi.';
                errorEl.classList.remove('hidden');
            });
        });

        function moveReportToLengkap(itemEl, fromColumn) {
            openLaporanModal(itemEl, fromColumn);
        }

        columns.forEach(function(column) {
            new Sortable(column, {
                group: 'shared',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function (evt) {
                    const itemEl = evt.item;
                    const newColumn = itemEl.closest('.kanban-column');
                    const newStatus = newColumn.getAttribute('data-status');
                    const itemId = itemEl.getAttribute('data-id');
                    const fromColumn = evt.from ? evt.from.closest('.kanban-column') : null;
                    const fromStatus = fromColumn ? fromColumn.getAttribute('data-status') : null;

                    // Laporan Perjalanan -> Lengkap: butuh konfirmasi popup.
                    if (itemEl.getAttribute('data-laporan') === '1'
                        && newStatus === 'Lengkap'
                        && fromStatus !== 'Lengkap') {
                        moveReportToLengkap(itemEl, fromColumn);
                        return;
                    }

                    // Lakukan request AJAX
                    fetch(`/checklists/${itemId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status: newStatus
                        })
                    })
                    .then(response => response.json().then(data => ({ ok: response.ok, data })))
                    .then(({ ok, data }) => {
                        // Bila validasi gagal, kembalikan card ke kolom semula.
                        if (!ok || (data && data.success === false)) {
                            if (fromColumn && fromColumn !== newColumn) {
                                fromColumn.querySelector('.kanban-items').appendChild(itemEl);
                            }
                            const msg = (data && data.message) ? data.message : 'Gagal update status';
                            alert(msg);
                            return;
                        }
                        if (data.success && data.history) {
                            // Update sidebar history
                            const historyList = document.getElementById('history-list');
                            if (historyList) {
                                const newLi = document.createElement('li');
                                newLi.className = 'mb-2 text-sm pb-2 border-b';
                                newLi.innerHTML = `
                                    <span class="font-semibold text-gray-800">${data.history.document}</span>
                                    diubah ke <span class="text-blue-600">${data.history.status_baru}</span>
                                    <br><span class="text-xs text-gray-500">Oleh ${data.history.user} pada ${data.history.time}</span>
                                `;
                                historyList.prepend(newLi);
                            }
                        } else if (!data.success) {
                            alert('Gagal update status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan koneksi');
                    });
                }
            });
        });
    });
</script>
