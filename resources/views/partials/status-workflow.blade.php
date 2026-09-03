@php
    $statusList = \App\Models\Request::STATUS_LIST;
    $currentStatus = $fpaRequest->status_spj;
    $currentIndex = array_search($currentStatus, $statusList);
    $transitions = \App\Http\Controllers\RequestStatusController::TRANSITIONS;
    $allowedNext = $transitions[$currentStatus] ?? [];
@endphp

<!-- Status Workflow Steps -->
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">Workflow Status SPJ</h3>

    <!-- Progress Steps -->
    <div class="flex items-center justify-between mb-6">
        @foreach($statusList as $index => $status)
            <div class="flex flex-col items-center flex-1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold
                    @if($index < $currentIndex) bg-green-500 text-white
                    @elseif($index === $currentIndex) bg-blue-600 text-white ring-4 ring-blue-200
                    @else bg-gray-200 text-gray-500
                    @endif">
                    @if($index < $currentIndex) ✓ @else {{ $index + 1 }} @endif
                </div>
                <p class="mt-2 text-xs text-center
                    @if($index === $currentIndex) font-bold text-blue-600
                    @elseif($index < $currentIndex) text-green-600
                    @else text-gray-400
                    @endif">
                    {{ $status }}
                </p>
            </div>
            @if(!$loop->last)
                <div class="flex-1 h-1 mx-1
                    @if($index < $currentIndex) bg-green-400
                    @else bg-gray-200
                    @endif rounded"></div>
            @endif
        @endforeach
    </div>

    <!-- Tombol Ubah Status -->
    @if($currentStatus !== 'Selesai')
        <div class="border-t pt-4">
            <h4 class="font-semibold text-gray-700 mb-3">Ubah Status SPJ</h4>

            @if(empty($allowedNext))
                <p class="text-sm text-gray-500 italic">Tidak ada transisi status lain dari status ini.</p>
            @else
                <form action="{{ route('requests.status.update', $fpaRequest->id) }}" method="POST" enctype="multipart/form-data" id="status-form">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="status_baru" class="block text-sm font-medium text-gray-700">Status Baru *</label>
                            <select name="status_baru" id="status_baru" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    onchange="toggleStatusFields(this.value)">
                                <option value="">-- Pilih Status --</option>
                                @foreach($allowedNext as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tanggal Kirim PPK (muncul jika Dikirim ke PPK) -->
                        <div id="field-tanggal-kirim" class="hidden">
                            <label for="tanggal_kirim_ppk" class="block text-sm font-medium text-gray-700">Tanggal Kirim ke PPK *</label>
                            <input type="date" name="tanggal_kirim_ppk" id="tanggal_kirim_ppk" value="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Tanggal Selesai SPJ (muncul jika Selesai) -->
                        <div id="field-tanggal-selesai" class="hidden">
                            <label for="tanggal_selesai_spj" class="block text-sm font-medium text-gray-700">Tanggal Selesai SPJ *</label>
                            <input type="date" name="tanggal_selesai_spj" id="tanggal_selesai_spj" value="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Catatan -->
                        <div class="md:col-span-2" id="field-catatan">
                            <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <!-- File Bukti -->
                        <div id="field-file-bukti" class="hidden">
                            <label for="file_bukti" class="block text-sm font-medium text-gray-700">Upload File Bukti (opsional)</label>
                            <input type="file" name="file_bukti" id="file_bukti" accept=".pdf,.jpg,.jpeg,.png,.docx" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-1">Max 10MB. Format: PDF, JPG, PNG, DOCX</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Ubah Status
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @else
        <div class="border-t pt-4 text-center">
            <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 font-semibold rounded-full text-sm">
                ✓ SPJ telah Selesai
            </span>
        </div>
    @endif
</div>

<!-- Timeline History Status SPJ -->
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">Timeline Riwayat Status SPJ</h3>

    @if($fpaRequest->statusHistories->count() > 0)
        <div class="space-y-4">
            @foreach($fpaRequest->statusHistories as $history)
                <div class="flex items-start gap-4 border-l-4 border-blue-400 pl-4 py-2">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $history->status_lama ?? '(Awal)' }}
                            <span class="text-gray-400 mx-1">→</span>
                            <span class="text-blue-600">{{ $history->status_baru }}</span>
                        </p>
                        @if($history->catatan)
                            <p class="text-sm text-gray-600 mt-1">{{ $history->catatan }}</p>
                        @endif
                        @if($history->file_bukti)
                            <a href="{{ asset('storage/' . $history->file_bukti) }}" target="_blank" class="text-xs text-blue-500 hover:underline mt-1 inline-block">📎 Lihat Bukti</a>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">
                            Oleh {{ $history->user->name ?? '-' }} — {{ \App\Support\Tanggal::formatDateTime($history->created_at) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 italic text-sm">Belum ada riwayat perubahan status.</p>
    @endif
</div>

<script>
    function toggleStatusFields(status) {
        document.getElementById('field-tanggal-kirim').classList.toggle('hidden', status !== 'Dikirim ke PPK');
        document.getElementById('field-tanggal-selesai').classList.toggle('hidden', status !== 'Selesai');
        document.getElementById('field-file-bukti').classList.toggle('hidden', status !== 'Perbaikan' && status !== 'Selesai');
    }
</script>
