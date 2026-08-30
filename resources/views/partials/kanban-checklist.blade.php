@php
    $columns = [
        'Belum Ada' => 'bg-gray-100',
        'Belum Lengkap' => 'bg-yellow-100',
        'Lengkap' => 'bg-green-100',
        'Perlu Perbaikan' => 'bg-red-100'
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-4" id="kanban-board">
    @foreach($columns as $status => $bgClass)
        <div class="kanban-column flex flex-col rounded-md {{ $bgClass }} p-3 min-h-[300px]" data-status="{{ $status }}">
            <h4 class="font-bold text-gray-700 text-center mb-3">{{ $status }}</h4>
            
            <div class="kanban-items flex-1 space-y-2">
                @foreach($fpaRequest->checklists->where('status', $status) as $item)
                    <div class="kanban-item bg-white p-3 rounded shadow-sm border border-gray-200 cursor-move" data-id="{{ $item->id }}">
                        <p class="font-semibold text-sm">{{ $item->nama_dokumen }}</p>
                        @if($item->catatan)
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $item->catatan }}</p>
                        @endif
                        <div class="mt-2 text-right">
                            <a href="{{ route('checklists.edit', $item->id) }}" class="text-xs text-blue-600 hover:underline">Edit Detail</a>
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
