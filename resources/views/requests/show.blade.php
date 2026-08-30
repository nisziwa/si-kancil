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

            <!-- Sprint 5: Status Workflow SPJ & History -->
            @include('partials.status-workflow', ['fpaRequest' => $fpaRequest])

            <!-- Sprint 4: Kanban Checklist SPJ & History -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- Kanban Area (3/4) -->
                <div class="lg:col-span-3 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2">Kanban Checklist Dokumen</h3>
                    @if($fpaRequest->checklists->count() > 0)
                        @include('partials.kanban-checklist', ['fpaRequest' => $fpaRequest])
                    @else
                        <p class="text-gray-500 italic">Belum ada checklist untuk FPA ini.</p>
                    @endif
                </div>

                <!-- History Sidebar (1/4) -->
                <div class="bg-gray-50 shadow-sm sm:rounded-lg p-6 border border-gray-200 h-[600px] overflow-y-auto">
                    <h3 class="text-md font-bold mb-4 border-b pb-2 text-gray-700">Riwayat Perubahan Status</h3>

                    <ul id="history-list" class="space-y-2">
                        @php
                            // Ambil history dari relasi checklist yang ada (lewat checklist_histories)
                            // Karena relasi langsung dari request tidak ada, kita query lewat model
                            $histories = \App\Models\ChecklistHistory::whereIn('checklist_id', $fpaRequest->checklists->pluck('id'))
                                        ->with(['checklist', 'user'])
                                        ->orderByDesc('created_at')
                                        ->get();
                        @endphp

                        @forelse($histories as $history)
                            <li class="mb-2 text-sm pb-2 border-b">
                                <span class="font-semibold text-gray-800">{{ $history->checklist->nama_dokumen ?? 'Dokumen' }}</span>
                                diubah ke <span class="text-blue-600">{{ $history->status_baru }}</span>
                                <br><span class="text-xs text-gray-500">Oleh {{ $history->user->name ?? '-' }} pada {{ $history->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-gray-500 italic" id="empty-history">Belum ada riwayat perubahan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
