<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Permintaan / FPA') }} - {{ $fpaRequest->nomor_fpa }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('requests.update', $fpaRequest->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nomor FPA -->
                        <div>
                            <label for="nomor_fpa" class="block text-sm font-medium text-gray-700">Nomor FPA *</label>
                            <input type="text" name="nomor_fpa" id="nomor_fpa" value="{{ old('nomor_fpa', $fpaRequest->nomor_fpa) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Jenis Pengeluaran -->
                        <div>
                            <label for="jenis_pengeluaran_id" class="block text-sm font-medium text-gray-700">Jenis Pengeluaran *</label>
                            <select name="jenis_pengeluaran_id" id="jenis_pengeluaran_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Jenis --</option>
                                @foreach($expenseTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('jenis_pengeluaran_id', $fpaRequest->jenis_pengeluaran_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->nama }} ({{ $type->kode }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Deskripsi -->
                        <div class="md:col-span-2">
                            <label for="deskripsi_permintaan" class="block text-sm font-medium text-gray-700">Deskripsi Permintaan *</label>
                            <textarea name="deskripsi_permintaan" id="deskripsi_permintaan" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('deskripsi_permintaan', $fpaRequest->deskripsi_permintaan) }}</textarea>
                        </div>

                        <!-- Periode -->
                        <div>
                            <label for="periode" class="block text-sm font-medium text-gray-700">Periode Kegiatan *</label>
                            <input type="text" name="periode" id="periode" value="{{ old('periode', $fpaRequest->periode) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Lokasi -->
                        <div>
                            <label for="lokasi" class="block text-sm font-medium text-gray-700">Lokasi</label>
                            <input type="text" name="lokasi" id="lokasi" value="{{ old('lokasi', $fpaRequest->lokasi) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', $fpaRequest->tanggal_mulai?->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', $fpaRequest->tanggal_selesai?->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <!-- Deadline SPJ -->
                        <div>
                            <label for="deadline_spj" class="block text-sm font-medium text-gray-700">Deadline SPJ</label>
                            <input type="date" name="deadline_spj" id="deadline_spj" value="{{ old('deadline_spj', $fpaRequest->deadline_spj?->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('requests.index') }}" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">Batal</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update FPA</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
