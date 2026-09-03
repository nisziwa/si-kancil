<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit SK Rate Perjalanan') }} — {{ $rate->kecamatan }}
            </h2>
            <a href="{{ route('sk-rates.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
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
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('sk-rates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">
                                Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- History Perubahan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
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
                                        {{ $history->created_at ? $history->created_at->format('d-m-Y H:i') : '-' }}
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
            </div>
        </div>
    </div>
</x-app-layout>
