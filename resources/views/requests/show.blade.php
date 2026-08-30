<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail FPA') }} - {{ $fpaRequest->nomor_fpa }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('requests.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Kembali</a>
                @if($fpaRequest->status_spj === 'Persiapan')
                    <a href="{{ route('requests.edit', $fpaRequest->id) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Edit FPA</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Informasi FPA</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nomor FPA</p>
                        <p class="font-semibold">{{ $fpaRequest->nomor_fpa }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status SPJ</p>
                        <p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($fpaRequest->status_spj == 'Persiapan') bg-gray-100 text-gray-800 
                                @elseif($fpaRequest->status_spj == 'Pelaksanaan') bg-blue-100 text-blue-800 
                                @elseif($fpaRequest->status_spj == 'Pengumpulan SPJ') bg-yellow-100 text-yellow-800 
                                @elseif($fpaRequest->status_spj == 'Dikirim ke PPK') bg-indigo-100 text-indigo-800 
                                @elseif($fpaRequest->status_spj == 'Perbaikan') bg-red-100 text-red-800 
                                @elseif($fpaRequest->status_spj == 'Selesai') bg-green-100 text-green-800 
                                @endif">
                                {{ $fpaRequest->status_spj }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Jenis Pengeluaran</p>
                        <p class="font-semibold">{{ $fpaRequest->expenseType->nama ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Periode</p>
                        <p class="font-semibold">{{ $fpaRequest->periode }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Deskripsi Permintaan</p>
                        <p class="font-semibold">{{ $fpaRequest->deskripsi_permintaan }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Lokasi</p>
                        <p class="font-semibold">{{ $fpaRequest->lokasi ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tanggal Mulai - Selesai</p>
                        <p class="font-semibold">
                            {{ $fpaRequest->tanggal_mulai ? $fpaRequest->tanggal_mulai->format('d/m/Y') : '-' }} 
                            s/d 
                            {{ $fpaRequest->tanggal_selesai ? $fpaRequest->tanggal_selesai->format('d/m/Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Deadline SPJ</p>
                        <p class="font-semibold text-red-600">{{ $fpaRequest->deadline_spj ? $fpaRequest->deadline_spj->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pembuat FPA</p>
                        <p class="font-semibold">{{ $fpaRequest->user->name ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder untuk Sprint 4 dan 5 (Kanban Checklist & History) -->
            <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg p-6 border-dashed border-2 border-gray-300">
                <p class="text-center text-gray-500 italic">Area ini akan diisi Kanban Checklist Dokumen pada Sprint selanjutnya.</p>
            </div>

        </div>
    </div>
</x-app-layout>
