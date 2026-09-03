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
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold">{{ $item->nama_dokumen }}</p>
                            <label class="shrink-0 cursor-pointer" title="Pilih untuk aksi massal">
                                <input type="checkbox" class="kanban-bulk-check rounded border-gray-300 text-indigo-600" value="{{ $item->id }}">
                            </label>
                        </div>
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
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-3 border-b border-indigo-200 bg-indigo-50 rounded-t-lg">
            <h4 class="font-bold text-indigo-800">Konfirmasi Laporan Perjalanan</h4>
            <button type="button" id="laporan-modal-close" class="text-indigo-500 hover:text-indigo-800 font-bold text-lg leading-none">&times;</button>
        </div>
        <div class="px-5 py-4">
            <p id="laporan-message" class="text-sm text-gray-700"></p>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
            <button type="button" id="laporan-modal-close2" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">Batal</button>
            <a href="#" id="laporan-modal-link" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">Lengkapi Laporan</a>
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
        const msgEl = document.getElementById('laporan-message');
        const linkBtn = document.getElementById('laporan-modal-link');

        function closeLaporanModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('laporan-modal-close').addEventListener('click', closeLaporanModal);
        document.getElementById('laporan-modal-close2').addEventListener('click', closeLaporanModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeLaporanModal();
        });

        function showLaporanModal(message, checklistId) {
            msgEl.textContent = message;
            linkBtn.href = `/checklists/${checklistId}/edit`;
            modal.classList.remove('hidden');
        }

        function requestStatus(itemEl, fromColumn, newColumn, newStatus, itemId) {
            fetch(`/checklists/${itemId}/status`, {
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
                // Bila validasi gagal, kembalikan card ke kolom semula.
                if (!ok || (data && data.success === false)) {
                    if (fromColumn && fromColumn !== newColumn) {
                        fromColumn.querySelector('.kanban-items').appendChild(itemEl);
                    }
                    // Bila ini Laporan Perjalanan yang butuh konfirmasi -> tampilkan popup baru.
                    if (data && data.require_confirmation) {
                        showLaporanModal(data.message, data.checklist_id);
                        return;
                    }
                    const msg = (data && data.message) ? data.message : 'Gagal update status';
                    alert(msg);
                    return;
                }
                if (data.success && data.history) {
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
                if (fromColumn && fromColumn !== newColumn) {
                    fromColumn.querySelector('.kanban-items').appendChild(itemEl);
                }
                alert('Terjadi kesalahan koneksi');
            });
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

                    // Semua perubahan status (termasuk Laporan Perjalanan) diproses lewat
                    // endpoint yang memvalidasi: bila Laporan belum terkumpul semua -> popup konfirmasi.
                    requestStatus(itemEl, fromColumn, newColumn, newStatus, itemId);
                }
            });
        });
    });
</script>
