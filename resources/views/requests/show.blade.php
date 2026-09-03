<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Detail FPA') }} - {{ $fpaRequest->nomor_fpa ?? 'Belum ada nomor FPA' }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('requests.index') }}" class="px-4 py-2 font-bold text-white bg-gray-500 rounded hover:bg-gray-700">Kembali</a>
                @if($fpaRequest->status_spj === 'Persiapan')
                    <a href="{{ route('requests.edit', $fpaRequest->id) }}" class="px-4 py-2 font-bold text-white bg-yellow-500 rounded hover:bg-yellow-700">Edit FPA</a>
                    <form action="{{ route('requests.destroy', $fpaRequest->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus FPA ini? Semua data checklist dan riwayat terkait akan ikut terhapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 font-bold text-white bg-red-600 rounded hover:bg-red-700">Hapus FPA</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="relative px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="p-6 mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <h3 class="pb-2 mb-4 text-lg font-bold border-b">Informasi FPA</h3>

                @php $priority = $fpaRequest->priority_info; @endphp
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm text-gray-500">Nomor FPA</p>
                        @if($fpaRequest->has_nomor_fpa)
                            <p class="font-semibold">{{ $fpaRequest->nomor_fpa }}</p>
                        @else
                            <p class="italic font-semibold text-gray-400">Belum ada nomor FPA</p>
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
                            {{ \App\Support\Tanggal::format($fpaRequest->tanggal_mulai) }}
                            s/d
                            {{ \App\Support\Tanggal::format($fpaRequest->tanggal_selesai) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Deadline SPJ</p>
                        <p class="font-semibold text-red-600">{{ \App\Support\Tanggal::format($fpaRequest->deadline_spj) }}</p>
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
                            <span class="text-sm font-semibold text-gray-700">Prioritas SPJ:</span>
                            <span class="text-sm font-semibold
                                @if($priority['level'] === 'danger') text-red-700
                                @elseif($priority['level'] === 'warning') text-amber-700
                                @else text-gray-600 @endif">
                                {{ $priority['label'] }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Deadline: {{ \App\Support\Tanggal::format($fpaRequest->deadline_spj) }} | Sisa hari: {{ $priority['sisa_hari'] }} | Keterlambatan: {{ $priority['terlambat'] ? 'Ya' : 'Tidak' }}</p>
                    </div>
                @endif
            </div>

            @if ($errors->any())
                <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
                    <strong class="font-bold">Ada kesalahan!</strong>
                    <ul class="pl-5 mt-2 list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Sprint 5: Status Workflow SPJ & History -->
            @include('partials.status-workflow', ['fpaRequest' => $fpaRequest])

            <!-- Baris 8: Superkendis -->
            @php
                $stChecklist = $fpaRequest->checklists->first(fn($c) => str_contains($c->nama_dokumen, 'Surat Tugas'));
                $stReady = $stChecklist
                    && $stChecklist->status === 'Lengkap'
                    && $stChecklist->suratTugasDetail
                    && $stChecklist->suratTugasDetail->pelaksanas->count() > 0;
            @endphp
            <div class="p-6 mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between pb-3 mb-4 border-b">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Superkendis</h3>
                        <p class="text-xs text-gray-500">Generate Surat Keterangan Bukan Kendaraan Dinas (Superkendis) untuk para pelaksana Surat Tugas.</p>
                    </div>
                    @if($stReady)
                        <a href="{{ route('requests.superkendis', $fpaRequest->id) }}"
                           class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                            Generate Superkendis
                        </a>
                    @endif
                </div>

                @if($stReady)
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-xs font-semibold text-left text-gray-600 uppercase">No</th>
                                <th class="px-4 py-2 text-xs font-semibold text-left text-gray-600 uppercase">Nama Pelaksana</th>
                                <th class="px-4 py-2 text-xs font-semibold text-left text-gray-600 uppercase">Nomor Surat Tugas</th>
                                <th class="px-4 py-2 text-xs font-semibold text-center text-gray-600 uppercase">Generate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stChecklist->suratTugasDetail->pelaksanas as $index => $pelaksana)
                                <tr>
                                    <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $pelaksana->nama_pelaksana }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $pelaksana->nomor_surat ?: '-' }}</td>
                                    <td class="px-4 py-2 text-center whitespace-nowrap">
                                        @if($pelaksana->superkendis)
                                            <a href="{{ route('requests.superkendis', $fpaRequest->id) }}?pelaksana={{ $pelaksana->id }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900">Buka Form →</a>
                                            <div class="flex gap-2 justify-center mt-1 text-xs">
                                                @if($pelaksana->superkendis->file_docx)
                                                    <a href="{{ asset('storage/' . $pelaksana->superkendis->file_docx) }}" target="_blank" class="text-green-700 hover:underline">DOCX</a>
                                                @endif
                                                @if($pelaksana->superkendis->file_pdf)
                                                    <a href="{{ asset('storage/' . $pelaksana->superkendis->file_pdf) }}" target="_blank" class="text-red-700 hover:underline">PDF</a>
                                                @endif
                                            </div>
                                        @else
                                            <a href="{{ route('requests.superkendis', $fpaRequest->id) }}?pelaksana={{ $pelaksana->id }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900">Buka Form →</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="flex gap-2 mt-3">
                        <a href="{{ route('requests.superkendis', $fpaRequest->id) }}" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">Buka Superkendis →</a>
                    </div>
                @else
                    <p class="text-sm italic text-gray-500">
                        Superkendis hanya dapat digenerate setelah checklist <strong>Surat Tugas</strong> berstatus <strong>Lengkap</strong> dan seluruh pelaksana tersedia.
                    </p>
                @endif
            </div>

            <!-- Sprint 4: Kanban Checklist SPJ & History -->
            <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-4">
                <!-- Kanban Area (3/4) -->
                <div class="p-6 bg-white shadow-sm lg:col-span-3 sm:rounded-lg">
                    <h3 class="pb-2 mb-4 text-lg font-bold border-b">Kanban Checklist Dokumen</h3>
                    @if($fpaRequest->checklists->count() > 0)
                        @include('partials.kanban-checklist', ['fpaRequest' => $fpaRequest])
                    @else
                        <p class="italic text-gray-500">Belum ada checklist untuk FPA ini.</p>
                    @endif
                </div>

                <!-- History Sidebar (1/4) -->
                <div class="bg-gray-50 shadow-sm sm:rounded-lg p-6 border border-gray-200 h-[600px] overflow-y-auto">
                    <h3 class="pb-2 mb-4 font-bold text-gray-700 border-b text-md">Riwayat Perubahan Status</h3>

                    <ul id="history-list" class="space-y-2">
                        @forelse($checklistHistory as $history)
                            <li class="pb-2 mb-2 text-sm border-b">
                                <span class="font-semibold text-gray-800">{{ $history->checklist->nama_dokumen ?? 'Dokumen' }}</span>
                                diubah ke <span class="text-blue-600">{{ $history->status_baru }}</span>
                                <br><span class="text-xs text-gray-500">Oleh {{ $history->user->name ?? '-' }} pada {{ \App\Support\Tanggal::formatDateTime($history->created_at) }}</span>
                            </li>
                        @empty
                            <li class="text-xs italic text-gray-500" id="empty-history">Belum ada riwayat perubahan.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
