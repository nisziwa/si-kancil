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
            <h4 class="font-bold text-indigo-800" id="laporan-modal-title">Konfirmasi Laporan Perjalanan</h4>
            <button type="button" id="laporan-modal-close" class="text-indigo-500 hover:text-indigo-800 font-bold text-lg leading-none">&times;</button>
        </div>
        <div class="px-5 py-4">
            <p id="laporan-message" class="text-sm text-gray-700"></p>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
            <button type="button" id="laporan-modal-close2" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">Batal</button>
            <button type="button" id="laporan-modal-ok" class="hidden bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">Ubah Status SPJ</button>
            <a href="#" id="laporan-modal-link" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">Lengkapi Laporan</a>
        </div>
    </div>
</div>

<!-- Tambahkan CDN SortableJS jika belum ada di app layout -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const columns = document.querySelectorAll('.kanban-items');

        /* ---------- Modal Konfirmasi Laporan Perjalanan / Surat Tugas ---------- */
        const modal = document.getElementById('laporan-modal');
        const titleEl = document.getElementById('laporan-modal-title');
        const msgEl = document.getElementById('laporan-message');
        const linkBtn = document.getElementById('laporan-modal-link');
        const okBtn = document.getElementById('laporan-modal-ok');

        function closeLaporanModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('laporan-modal-close').addEventListener('click', closeLaporanModal);
        document.getElementById('laporan-modal-close2').addEventListener('click', closeLaporanModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeLaporanModal();
        });

        function showLaporanModal(title, message, linkText, checklistId) {
            titleEl.textContent = title;
            msgEl.textContent = message;
            okBtn.classList.add('hidden');
            okBtn.onclick = null;
            if (linkText && checklistId) {
                linkBtn.textContent = linkText;
                linkBtn.href = `/checklists/${checklistId}/edit`;
                linkBtn.classList.remove('hidden');
            } else {
                linkBtn.classList.add('hidden');
            }
            modal.classList.remove('hidden');
        }

        function showSjpConfirmModal(title, message, onConfirm) {
            titleEl.textContent = title;
            msgEl.textContent = message;
            linkBtn.classList.add('hidden');
            okBtn.textContent = 'Ubah Status SPJ';
            okBtn.classList.remove('hidden');
            okBtn.onclick = function () {
                closeLaporanModal();
                onConfirm();
            };
            modal.classList.remove('hidden');
        }

        /* ---------- Update UI Status SPJ tanpa refresh ---------- */
        const SPJ_STATUS_LIST = ['Persiapan', 'Dikirim ke PPK', 'Perbaikan', 'Selesai'];

        const SPJ_BADGE_CLASSES = {
            'Persiapan': 'bg-gray-100 text-gray-800',
            'Dikirim ke PPK': 'bg-indigo-100 text-indigo-800',
            'Perbaikan': 'bg-red-100 text-red-800',
            'Selesai': 'bg-green-100 text-green-800'
        };

        function updateSpjStatusUi(status) {
            const badge = document.getElementById('status-spj-badge');
            if (badge) {
                const cls = SPJ_BADGE_CLASSES[status];
                if (cls) {
                    Object.values(SPJ_BADGE_CLASSES).forEach(c => {
                        c.split(' ').forEach(t => badge.classList.remove(t));
                    });
                    cls.split(' ').forEach(t => badge.classList.add(t));
                }
                badge.textContent = status;
            }

            const idx = SPJ_STATUS_LIST.indexOf(status);
            document.querySelectorAll('.wf-step-item').forEach(item => {
                const i = parseInt(item.getAttribute('data-index'), 10);
                const circle = item.querySelector('.wf-circle');
                const label = item.querySelector('.wf-label');
                if (circle) {
                    circle.className = 'wf-circle w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold ' +
                        (i < idx ? 'bg-green-500 text-white' : (i === idx ? 'bg-blue-600 text-white ring-4 ring-blue-200' : 'bg-gray-200 text-gray-500'));
                    circle.textContent = i < idx ? '\u2713' : (i + 1);
                }
                if (label) {
                    label.className = 'wf-label mt-2 text-xs text-center ' +
                        (i === idx ? 'font-bold text-blue-600' : (i < idx ? 'text-green-600' : 'text-gray-400'));
                }
            });
            document.querySelectorAll('.wf-connector').forEach(conn => {
                const i = parseInt(conn.getAttribute('data-index'), 10);
                conn.className = 'wf-connector flex-1 h-1 mx-1 rounded ' + (i < idx ? 'bg-green-400' : 'bg-gray-200');
            });

            // Sesuaikan pilihan "Ubah Status SPJ" dengan map transisi terbaru.
            const sel = document.getElementById('status_baru');
            if (sel) {
                const options = (window.spjTransitions && window.spjTransitions[status]) || [];
                sel.innerHTML = '<option value="">-- Pilih Status --</option>' +
                    options.map(s => '<option value="' + s + '">' + s + '</option>').join('');
                ['field-tanggal-kirim', 'field-tanggal-selesai', 'field-file-bukti'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.classList.add('hidden');
                });
            }

            // Saat Selesai, area "Ubah Status" diganti pesan selesai.
            const formWrap = document.getElementById('status-change-wrap');
            const doneWrap = document.getElementById('status-done-message');
            if (status === 'Selesai') {
                if (formWrap) formWrap.classList.add('hidden');
                if (doneWrap) doneWrap.classList.remove('hidden');
            } else {
                if (formWrap) formWrap.classList.remove('hidden');
                if (doneWrap) doneWrap.classList.add('hidden');
            }
        }

        function requestStatus(itemEl, fromColumn, newColumn, newStatus, itemId) {
            const sendPatch = function (confirmSpj) {
                return fetch(`/checklists/${itemId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus, confirm_spj: confirmSpj ? 1 : 0 })
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })));
            };

            const handleResult = function ({ ok, data }) {
                // Bila validasi gagal, kembalikan card ke kolom semula.
                if (!ok || (data && data.success === false)) {
                    if (fromColumn && fromColumn !== newColumn) {
                        fromColumn.querySelector('.kanban-items').appendChild(itemEl);
                    }
                    const itemName = itemEl.getAttribute('data-nama');
                    // Dokumen dipindah ke Perlu Perbaikan saat SPJ "Dikirim ke PPK"
                    // -> konfirmasi untuk ikut mengubah status SPJ menjadi Perbaikan.
                    if (data && data.require_spj_confirm) {
                        showSjpConfirmModal('Konfirmasi '+itemName, data.message, function () {
                            sendPatch(true).then(handleResult);
                        });
                        return;
                    }
                    // Surat Tugas belum lengkap -> modal Konfirmasi + Lengkapi Isian.
                    if (data && data.require_st_confirmation) {
                        showLaporanModal('Konfirmasi ' + itemName, data.message, 'Lengkapi Isian', data.checklist_id);
                        return;
                    }
                    // Laporan Perjalanan terkendala (pengumpulan / Dokumentasi) -> modal konfirmasi.
                    if (data && data.require_confirmation) {
                        showLaporanModal('Konfirmasi ' + itemName, data.message, data.link_text || 'Lengkapi Laporan', data.checklist_id);
                        return;
                    }
                    showLaporanModal('Perhatian', (data && data.message) ? data.message : 'Gagal update status', null, null);
                    return;
                }
                // Bila berhasil, pastikan kartu berada di kolom tujuan. Saat flow konfirmasi
                // SPJ, kartu sempat dikembalikan ke kolom asal pada respons 422 pertama
                // sehingga perlu dipindah DOM-nya agar langsung tercermin tanpa refresh.
                if (ok && data.success && newColumn) {
                    const targetItems = newColumn.querySelector('.kanban-items');
                    if (targetItems && itemEl.parentElement !== targetItems) {
                        targetItems.appendChild(itemEl);
                    }
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
                    showLaporanModal('Perhatian', 'Gagal update status', null, null);
                }

                // Status SPJ ikut berubah (mis. Dikirim ke PPK -> Perbaikan):
                // perbarui badge & workflow langsung tanpa refresh.
                if (data.status_spj) {
                    updateSpjStatusUi(data.status_spj);
                }
            };

            sendPatch(false)
                .then(handleResult)
                .catch(error => {
                    console.error('Error:', error);
                    if (fromColumn && fromColumn !== newColumn) {
                        fromColumn.querySelector('.kanban-items').appendChild(itemEl);
                    }
                    showLaporanModal('Perhatian', 'Terjadi kesalahan koneksi', null, null);
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
