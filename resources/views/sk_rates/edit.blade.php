<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit SK Rate Perjalanan') }} — {{ $rate->kecamatan }}
            </h2>
            <x-ui.btn variant="muted" :href="route('sk-rates.index')">
                ← Kembali
            </x-ui.btn>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.card class="p-6">
                @if($errors->any())
                    <x-ui.alert type="danger" class="mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                <form action="{{ route('sk-rates.update', $rate->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kecamatan *</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $rate->kecamatan) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ibukota Kecamatan *</label>
                            <input type="text" name="ibukota_kecamatan" value="{{ old('ibukota_kecamatan', $rate->ibukota_kecamatan) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Besaran Biaya Transport (Rp) *</label>
                            <input type="number" name="besaran_biaya_transport" value="{{ old('besaran_biaya_transport', $rate->besaran_biaya_transport) }}" min="0" step="0.01" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <textarea name="keterangan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('keterangan', $rate->keterangan) }}</textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <x-ui.btn variant="primary" type="submit">
                                Simpan Perubahan
                            </x-ui.btn>
                            <x-ui.btn variant="secondary" :href="route('sk-rates.index')">
                                Batal
                            </x-ui.btn>
                        </div>
                    </div>
                </form>
            </x-ui.card>

            <!-- History Perubahan -->
            <x-ui.card class="p-6">
                <h3 class="text-lg font-bold mb-3 border-b pb-2">Riwayat Perubahan SK Rate</h3>
                @if($rate->histories->isEmpty())
                    <p class="text-gray-500 italic text-sm">Belum ada riwayat perubahan.</p>
                @else
                    <ul class="space-y-4">
                        @foreach($rate->histories->sortByDesc('created_at') as $history)
                            <li class="border border-gray-200 rounded p-3 text-sm">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-xs uppercase font-semibold px-2 py-0.5 rounded
                                        @if($history->aksi === 'create') bg-green-100 text-green-800
                                        @elseif($history->aksi === 'update') bg-blue-100 text-blue-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $history->aksi }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ \App\Support\Tanggal::formatDateTime($history->created_at) }}
                                        @if($history->user)
                                            oleh {{ $history->user->name }}
                                        @endif
                                    </span>
                                </div>
                                @if($history->data_sebelum)
                                    <div class="mt-1">
                                        <span class="text-xs font-semibold text-gray-500">Sebelum:</span>
                                        <pre class="text-xs text-gray-600 bg-gray-50 p-2 rounded mt-1 whitespace-pre-wrap">{{ $history->data_sebelum }}</pre>
                                    </div>
                                @endif
                                @if($history->data_sesudah && $history->aksi !== 'delete')
                                    <div class="mt-1">
                                        <span class="text-xs font-semibold text-gray-500">Sesudah:</span>
                                        <pre class="text-xs text-gray-600 bg-gray-50 p-2 rounded mt-1 whitespace-pre-wrap">{{ $history->data_sesudah }}</pre>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
