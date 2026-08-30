<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail FPA') }} - {{ $fpaRequest->nomor_fpa ?? 'Belum ada nomor FPA' }}
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

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Informasi FPA</h3>

                @php $priority = $fpaRequest->priority_info; @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nomor FPA</p>
                        @if($fpaRequest->has_nomor_fpa)
                            <p class="font-semibold">{{ $fpaRequest->nomor_fpa }}</p>
                        @else
                            <p class="font-semibold text-gray-400 italic">Belum ada nomor FPA</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status SPJ</p>
                        <p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($fpaRequest->status_spj == 'Persiapan') bg-gray-100 text-gray-800
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
                        <p class="text-sm text-gray-500">Periode Kegiatan</p>
                        <p class="font-semibold">{{ $fpaRequest->periode ?: '-' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500">Deskripsi Permintaan</p>
                        <p class="font-semibold">{{ $fpaRequest->deskripsi_permintaan }}</p>
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

                <!-- Progress / Prioritas SPJ -->
                @if($fpaRequest->deadline_spj)
                    <div class="mt-4 p-3 rounded-md border
                        @if($priority['level'] === 'danger') bg-red-50 border-red-300
                        @elseif($priority['level'] === 'warning') bg-amber-50 border-amber-300
                        @else bg-gray-50 border-gray-300 @endif">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-gray-700">Prioritas SPJ:</span>
                            <span class="text-sm font-semibold
                                @if($priority['level'] === 'danger') text-red-700
                                @elseif($priority['level'] === 'warning') text-amber-700
                                @else text-gray-600 @endif">
                                {{ $priority['label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Deadline: {{ $fpaRequest->deadline_spj->format('d/m/Y') }} | Sisa hari: {{ $priority['sisa_hari'] }} | Keterlambatan: {{ $priority['terlambat'] ? 'Ya' : 'Tidak' }}</p>
                    </div>
                @endif
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

            <!-- Baris 8: Superkendis -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Superkendis</h3>
                        <p class="text-xs text-gray-500">Generate Surat Keterangan Bukan Kendaraan Dinas (Superkendis) untuk para pelaksana Surat Tugas.</p>
                    </div>
                    <a href="{{ route('requests.superkendis', $fpaRequest->id) }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm"
                       @if(!$fpaRequest->checklists->contains(fn($c) => str_contains($c->nama_dokumen, 'Surat Tugas'))) @endif>
                        Generate Superkendis
                    </a>
                </div>

                @php
                    $stChecklist = $fpaRequest->checklists->first(fn($c) => str_contains($c->nama_dokumen, 'Surat Tugas'));
                @endphp

                @if($stChecklist && $stChecklist->status === 'Lengkap' && $stChecklist->suratTugasDetail && $stChecklist->suratTugasDetail->pelaksanas->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama Pelaksana</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Tugas</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Generate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stChecklist->suratTugasDetail->pelaksanas as $index => $pelaksana)
                                <tr>
                                    <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $pelaksana->nama_pelaksana }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $pelaksana->nomor_surat ?: '-' }}</td>
                                    <td class="px-4 py-2 text-center whitespace-nowrap">
                                        <a href="{{ route('requests.superkendis', $fpaRequest->id) }}?pelaksana={{ $pelaksana->id }}" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs">Buka Form →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('requests.superkendis', $fpaRequest->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded text-sm">Buka Superkendis →</a>
                    </div>
                @else
                    <p class="text-gray-500 italic text-sm">
                        Superkendis hanya dapat digenerate setelah checklist <strong>Surat Tugas</strong> berstatus <strong>Lengkap</strong> dan seluruh pelaksana tersedia.
                    </p>
                @endif
            </div>

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
