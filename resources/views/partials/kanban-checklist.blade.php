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
                    <div class="p-3 bg-white border border-gray-200 rounded shadow-sm cursor-move kanban-item" data-id="{{ $item->id }}">
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

<!-- Tambahkan CDN SortableJS jika belum ada di app layout -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const columns = document.querySelectorAll('.kanban-items');

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
                    .then(response => response.json())
                    .then(data => {
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

