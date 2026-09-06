<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Generate Superkendis') }}
            </h2>
            <a href="{{ route('requests.show', $requestModel->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                ← Kembali ke Detail FPA
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            @if(request('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ request('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Ada kesalahan!</strong>
                    <ul class="list-disc pl-5 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!$superkendisDone)
                <div class="bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded">
                    Superkendis hanya dapat digenerate setelah checklist <strong>Surat Tugas</strong> berstatus <strong>Lengkap</strong> dan seluruh pelaksana beserta nomor surat tersedia.
                </div>
            @endif

            <!-- Blok Informasi FPA -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3 border-b pb-2">Informasi FPA</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Nomor FPA</p>
                        <p class="font-semibold">
                            @if($requestModel->has_nomor_fpa)
                                {{ $requestModel->nomor_fpa }}
                            @else
                                <span class="text-gray-400 italic">Belum ada nomor FPA</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Deskripsi</p>
                        <p class="font-semibold">{{ $requestModel->deskripsi_permintaan }}</p>
                    </div>
                </div>
            </div>

            <!-- Form Generate per Pelaksana (partial di-share dengan Kelola Dokumen) -->
            @include('partials.superkendis-form', [
                'superkendisDone' => $superkendisDone,
                'stChecklist' => $stChecklist,
                'kecamatans' => $kecamatans,
                'selectedPelaksanaIds' => $selectedPelaksanaIds,
            ])

        </div>
    </div>
</x-app-layout>
