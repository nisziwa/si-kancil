<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tambah SK Rate Perjalanan') }}
            </h2>
            <x-ui.btn variant="muted" :href="route('sk-rates.index')">
                ← Kembali
            </x-ui.btn>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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

                <form action="{{ route('sk-rates.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kecamatan *</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ibukota Kecamatan *</label>
                            <input type="text" name="ibukota_kecamatan" value="{{ old('ibukota_kecamatan') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Besaran Biaya Transport (Rp) *</label>
                            <input type="number" name="besaran_biaya_transport" value="{{ old('besaran_biaya_transport') }}" min="0" step="0.01" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                            <textarea name="keterangan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('keterangan') }}</textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <x-ui.btn variant="primary" type="submit">
                                Simpan SK Rate
                            </x-ui.btn>
                            <x-ui.btn variant="secondary" :href="route('sk-rates.index')">
                                Batal
                            </x-ui.btn>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
