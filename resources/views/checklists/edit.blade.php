<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Kelola Dokumen') }} — {{ $checklist->nama_dokumen }}
            </h2>
            <a href="{{ route('requests.show', $checklist->request_id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                ← Kembali ke FPA
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Ada kesalahan input:</strong>
                    <ul class="list-disc pl-5 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('checklists.update', $checklist->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Informasi Dasar & Status Checklist -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold mb-4 border-b pb-2 text-gray-800">Status & Catatan Dokumen</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Dokumen</label>
                            <p class="mt-1 text-base font-bold text-gray-900">{{ $checklist->nama_dokumen }}</p>
                            <p class="text-xs text-gray-500">Nomor FPA: {{ $checklist->request->nomor_fpa ?? '-' }}</p>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status Kelengkapan *</label>
                            <select name="status" id="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="Belum Ada" {{ old('status', $checklist->status) == 'Belum Ada' ? 'selected' : '' }}>Belum Ada</option>
                                <option value="Belum Lengkap" {{ old('status', $checklist->status) == 'Belum Lengkap' ? 'selected' : '' }}>Belum Lengkap</option>
                                <option value="Lengkap" {{ old('status', $checklist->status) == 'Lengkap' ? 'selected' : '' }}>Lengkap</option>
                                <option value="Perlu Perbaikan" {{ old('status', $checklist->status) == 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan Tambahan</label>
                            <textarea name="catatan" id="catatan" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Tambahkan catatan koreksi atau penjelasan dokumen...">{{ old('catatan', $checklist->catatan) }}</textarea>
                        </div>

                        <!-- Upload File Dokumen -->
                        <div class="md:col-span-2 border-t pt-4">
                            <label for="file_dokumen" class="block text-sm font-medium text-gray-700">Upload File Dokumen</label>
                            <input type="file" name="file_dokumen" id="file_dokumen" accept=".pdf,.jpg,.jpeg,.png,.docx" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-1">Maksimal 10MB. Format: PDF, JPG, PNG, DOCX</p>
                            
                            @if($checklist->file_path)
                                <div class="mt-3 flex items-center gap-3 p-3 bg-blue-50 rounded border border-blue-200">
                                    <span class="text-sm text-blue-800">📄 File tersimpan:</span>
                                    <a href="{{ asset('storage/' . $checklist->file_path) }}" target="_blank" class="text-sm font-semibold text-blue-600 hover:underline">
                                        Lihat / Download File
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Form Khusus: Surat Tugas -->
                @if(str_contains($checklist->nama_dokumen, 'Surat Tugas'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Surat Tugas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nomor_surat_tugas" class="block text-sm font-medium text-gray-700">Nomor Surat Tugas</label>
                                <input type="text" name="nomor_surat_tugas" id="nomor_surat_tugas" value="{{ old('nomor_surat_tugas', $checklist->suratTugasDetail->nomor_surat_tugas ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: ST/001/VIII/2026">
                            </div>

                            <div>
                                <label for="tanggal_surat_tugas" class="block text-sm font-medium text-gray-700">Tanggal Surat Tugas</label>
                                <input type="text" name="tanggal_surat_tugas" id="tanggal_surat_tugas" value="{{ old('tanggal_surat_tugas', optional($checklist->suratTugasDetail->tanggal_surat_tugas ?? null)->format('Y-m-d')) }}" class="datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm bg-white">
                            </div>

                            <div class="md:col-span-2">
                                <label for="isi_tugas" class="block text-sm font-medium text-gray-700">Isi Tugas / Uraian Penugasan</label>
                                <textarea name="isi_tugas" id="isi_tugas" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Maksud dan uraian penugasan...">{{ old('isi_tugas', $checklist->suratTugasDetail->isi_tugas ?? '') }}</textarea>
                            </div>

                            <!-- Daftar Pelaksana (banyak) + Nomor Sub Otomatis -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Daftar Pelaksana Surat Tugas</label>
                                <p class="text-xs text-gray-500 mb-2">Nomor sub pelaksana dihasilkan otomatis dari nomor surat utama. Contoh: B-1027/... → B-1027.1/..., B-1027.2/...</p>

                                <table class="min-w-full divide-y divide-gray-200 text-sm" id="pelaksana-table">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama Pelaksana</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Sub</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @php
                                            $existingPelaksanas = $checklist->suratTugasDetail && $checklist->suratTugasDetail->pelaksanas
                                                ? $checklist->suratTugasDetail->pelaksanas
                                                : collect();
                                        @endphp
                                        @if($existingPelaksanas->count() > 0)
                                            @foreach($existingPelaksanas as $idx => $p)
                                                <tr>
                                                    <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                                    <td class="px-3 py-2">
                                                        <input type="text" name="pelaksana_nama[]" value="{{ old('pelaksana_nama.'.$idx, $p->nama_pelaksana) }}" class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-500">{{ $p->nomor_surat ?: '-' }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <button type="button" class="remove-pelaksana text-red-600 hover:text-red-800 font-semibold text-xs">Hapus</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td class="px-3 py-2 text-gray-500 pel-urutan">1</td>
                                                <td class="px-3 py-2">
                                                    <input type="text" name="pelaksana_nama[]" value="{{ old('pelaksana_nama.0') }}" class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                                </td>
                                                <td class="px-3 py-2 text-gray-400">-</td>
                                                <td class="px-3 py-2 text-center">
                                                    <button type="button" class="remove-pelaksana text-red-600 hover:text-red-800 font-semibold text-xs">Hapus</button>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <button type="button" id="add-pelaksana" class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-800">+ Tambah Pelaksana</button>

                                <!-- Input Massal Pelaksana dengan Preview & Konfirmasi -->
                                <div class="p-4 mt-4 rounded-md border border-indigo-200 bg-indigo-50">
                                    <label class="block text-sm font-semibold text-indigo-800">Input Pelaksana Massal</label>
                                    <p class="text-xs text-gray-600 mb-2">Masukkan banyak nama sekaligus lalu pilih pemisah dan lihat pratinjau sebelum ditambahkan.</p>
                                    <textarea id="bulk-pelaksana" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh:&#10;Hamdia;Holil;Onal"></textarea>

                                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                                        <span class="font-medium text-gray-700">Pemisah:</span>
                                        <label class="inline-flex items-center gap-1">
                                            <input type="radio" name="bulk_separator" value="newline" checked class="accent-indigo-600"> Baris baru
                                        </label>
                                        <label class="inline-flex items-center gap-1">
                                            <input type="radio" name="bulk_separator" value="semicolon" class="accent-indigo-600"> Titik koma (;)
                                        </label>
                                        <label class="inline-flex items-center gap-1">
                                            <input type="radio" name="bulk_separator" value="comma" class="accent-indigo-600"> Koma (,)
                                        </label>
                                    </div>

                                    <div class="flex gap-2 mt-3">
                                        <button type="button" id="preview-pelaksana" class="px-3 py-1 text-xs font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700">Pratinjau</button>
                                        <button type="button" id="confirm-pelaksana" class="px-3 py-1 text-xs font-bold text-white bg-green-600 rounded hover:bg-green-700" disabled>Tambah ke Daftar</button>
                                        <span id="bulk-hint" class="text-xs font-bold mt-1 text-red-600"></span>
                                    </div>

                                    <div id="bulk-preview" class="hidden mt-3 p-3 bg-white rounded border border-gray-200 text-sm">
                                        <p class="text-xs font-semibold text-gray-600 mb-1">Pratinjau ({<span id="preview-count">0</span>} nama):</p>
                                        <ol id="preview-list" class="pl-5 list-decimal"></ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Khusus: SPD / SPPD -->
                @if(str_contains($checklist->nama_dokumen, 'SPD') || str_contains($checklist->nama_dokumen, 'SPPD'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Perjalanan Dinas (SPD/SPPD)</h3>
                        <p class="text-sm text-gray-500 mb-4">Data pelaksana dan nomor diambil otomatis dari <strong>Surat Tugas</strong>. Tidak ada input manual duplikat.</p>

                        @if($stPelaksanas->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama Pelaksana</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Sub</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Tugas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stPelaksanas as $idx => $p)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                                <td class="px-3 py-2 font-medium text-gray-800">{{ $p->nama_pelaksana }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $p->nomor_surat ?: '-' }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $stDetail->nomor_surat_tugas ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 italic text-sm">Isi terlebih dahulu checklist <strong>Surat Tugas</strong> (nomor + daftar pelaksana) agar daftar pelaksana SPD/SPPD tersedia.</p>
                        @endif
                    </div>
                @endif

                <!-- Form Khusus: Pengeluaran Riil + Surat Non Kendaraan Dinas -->
                @if(str_contains($checklist->nama_dokumen, 'Pengeluaran Riil'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Pengeluaran Riil & Surat Non Kendaraan Dinas</h3>
                        <p class="text-sm text-gray-500 mb-4">Data diambil dari <strong>Superkendis</strong> yang digenerate per pelaksana Surat Tugas. Tidak ada input manual.</p>

                        @if($stPelaksanas->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama Pelaksana</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Sub</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stPelaksanas as $idx => $p)
                                            @php
                                                $sk = $p->superkendis;
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2 text-gray-500">{{ $idx + 1 }}</td>
                                                <td class="px-3 py-2 font-medium text-gray-800">{{ $p->nama_pelaksana }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $p->nomor_surat ?: '-' }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    @if($sk)
                                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Sudah Generate</span>
                                                    @else
                                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Belum Ada</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @if($sk && $sk->file_docx)
                                                        <a href="{{ asset('storage/' . $sk->file_docx) }}" target="_blank" class="text-xs font-semibold text-green-700 hover:underline">Download</a>
                                                    @else
                                                        <span class="text-xs text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Checklist ini otomatis menjadi <strong>Lengkap</strong> setelah seluruh Superkendis pelaksana digenerate (lihat halaman <a href="{{ route('requests.superkendis', $checklist->request_id) }}" class="text-indigo-600 hover:underline">Generate Superkendis</a>).
                            </p>
                        @else
                            <p class="text-gray-500 italic text-sm">Isi terlebih dahulu checklist <strong>Surat Tugas</strong> supaya daftar pelaksana tersedia, lalu generate Superkendis di halaman FPA.</p>
                        @endif
                    </div>
                @endif

                <!-- Form Khusus: Laporan Perjalanan -->
                @if(str_contains($checklist->nama_dokumen, 'Laporan Perjalanan'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Laporan Perjalanan</h3>
                        <p class="text-sm text-gray-500 mb-4">Atur status pengumpulan laporan per pelaksana (bulk), unggah laporan, atau generate laporan otomatis. Status disimpan per pelaksana; checklist baru <strong>Lengkap</strong> bila seluruh pelaksana sudah mengumpulkan.</p>

                        @if($stPelaksanas->count() > 0)
                            @php
                                $reportStatuses = $checklist->travelReportPelaksanas->keyBy('surat_tugas_pelaksana_id');
                            @endphp

                            <!-- Bulk Action di luar tabel pelaksana -->
                            <div class="mb-4 p-3 rounded-md border border-indigo-200 bg-indigo-50 flex flex-wrap items-center gap-3">
                                <span class="text-sm font-semibold text-indigo-800">Ubah Status Massal:</span>
                                <select id="bulk-report-status" class="border-gray-300 rounded-md shadow-sm sm:text-sm text-gray-700">
                                    <option value="Sudah Mengumpulkan">Sudah Mengumpulkan</option>
                                    <option value="Belum Mengumpulkan">Belum Mengumpulkan</option>
                                </select>
                                <button type="button" id="bulk-report-apply" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-4 rounded text-sm">
                                    Terapkan ke Pelaksana Terpilih
                                </button>
                                <span class="text-xs text-gray-500">(centang pelaksana pada tabel)</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 w-10 text-left text-xs font-semibold text-gray-600 uppercase">
                                                <input type="checkbox" id="report-select-all" class="rounded border-gray-300 text-indigo-600">
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Sub</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Laporan Terunggah / Generate</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stPelaksanas as $idx => $p)
                                            @php
                                                $existing = $reportStatuses->get($p->id);
                                                $currStatus = old('report_status.status.'.$p->id, $existing ? $existing->status : \App\Models\TravelReportPelaksana::STATUS_BELUM);
                                                $currChecked = old('report_status.selected.'.$p->id) ? true : ($existing ? true : false);
                                                $report = $travelReports->get($p->id);
                                            @endphp
                                            <tr class="report-row">
                                                <td class="px-3 py-2 text-center">
                                                    <input type="checkbox" name="report_status[selected][{{ $p->id }}]" value="1"
                                                           class="report-check rounded border-gray-300 text-indigo-600" data-pelaksana="{{ $p->id }}" {{ $currChecked ? 'checked' : '' }}>
                                                </td>
                                                <td class="px-3 py-2 font-medium text-gray-800">{{ $p->nama_pelaksana }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $p->nomor_surat ?: '-' }}</td>
                                                <td class="px-3 py-2">
                                                    <select name="report_status[status][{{ $p->id }}]" data-pelaksana="{{ $p->id }}"
                                                            data-has-file="{{ ($report && ($report->file_docx || $report->file_pdf)) ? 1 : 0 }}"
                                                            class="report-status block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                                        @foreach(\App\Models\TravelReportPelaksana::STATUS_LIST as $st)
                                                            <option value="{{ $st }}" {{ $currStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @if($report && $report->file_docx)
                                                        <a href="{{ asset('storage/' . $report->file_docx) }}" target="_blank" class="text-xs font-semibold text-green-700 hover:underline">Lihat File</a>
                                                    @else
                                                        <span class="text-xs text-gray-400">Belum ada file</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center whitespace-nowrap">
                                                    <div class="flex gap-2 justify-center">
                                                        <label class="text-xs font-semibold text-indigo-600 cursor-pointer hover:underline upload-trigger" data-pelaksana="{{ $p->id }}" data-nama="{{ $p->nama_pelaksana }}">
                                                            Upload
                                                        </label>
                                                        <button type="button" class="text-xs font-semibold text-blue-600 hover:underline generate-trigger"
                                                                data-pelaksana="{{ $p->id }}" data-nama="{{ $p->nama_pelaksana }}"
                                                                data-judul="{{ $report ? $report->judul_laporan : '' }}"
                                                                data-jenis="{{ $report ? $report->jenis_laporan : '' }}"
                                                                data-tanggal="{{ $report && $report->tanggal_laporan ? $report->tanggal_laporan->format('Y-m-d') : '' }}"
                                                                data-pok="{{ $report && $report->pok_rincian_id ? $report->pok_rincian_id : '' }}">
                                                            Generate
                                                        </button>
                                                    </div>
                                                    <input type="file" class="hidden upload-file" data-pelaksana="{{ $p->id }}" accept=".pdf,.jpg,.jpeg,.png,.docx">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-4 text-xs text-gray-500">
                                Checklist <strong>Laporan Perjalanan</strong> hanya menjadi <strong>Lengkap</strong> bila seluruh pelaksana sudah mengumpulkan.
                            </p>
                        @else
                            <p class="text-gray-500 italic text-sm">Isi terlebih dahulu checklist <strong>Surat Tugas</strong> supaya daftar pelaksana tersedia.</p>
                        @endif
                    </div>

                    <!-- Modal Generate Laporan Perjalanan -->
                    <div id="generate-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between px-5 py-3 border-b border-indigo-200 bg-indigo-50 rounded-t-lg sticky top-0">
                                <h4 class="font-bold text-indigo-800">Generate Laporan Perjalanan</h4>
                                <button type="button" id="generate-modal-close" class="text-indigo-500 hover:text-indigo-800 font-bold text-lg leading-none">&times;</button>
                            </div>
                            <form id="generate-form" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="pelaksana_id" id="gen-pelaksana-id">
                                <input type="hidden" name="format" value="docx">

                                <div class="px-5 py-4 space-y-4 text-sm">
                                    <p class="text-xs text-gray-500">Pelaksana: <strong id="gen-pelaksana-nama"></strong></p>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jenis Laporan *</label>
                                        <select name="jenis_laporan" id="gen-jenis" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                            @foreach(\App\Models\TravelReport::JENIS_LIST as $jenis)
                                                <option value="{{ $jenis }}">{{ \App\Models\TravelReport::JENIS_LABELS[$jenis] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Judul Laporan *</label>
                                        <input type="text" name="judul_laporan" id="gen-judul" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: SURVEI UBINAN PALAWIJA SUBROUND 3 TAHUN 2026">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tanggal Laporan *</label>
                                        <input type="text" name="tanggal_laporan" id="gen-tanggal" class="datepicker mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm bg-white">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">POK / Pembiayaan *</label>
                                        <input type="text" id="gen-pok-search" autocomplete="off" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Ketik rincian pembiayaan...">
                                        <input type="hidden" name="pok_rincian_id" id="gen-pok-id">
                                        <div id="gen-pok-results" class="hidden mt-1 border border-gray-200 rounded bg-white shadow divide-y divide-gray-100 max-h-60 overflow-y-auto"></div>
                                        <div id="gen-pok-detail" class="hidden mt-3 p-3 bg-gray-50 rounded border border-gray-200 text-xs space-y-1"></div>
                                        <p class="text-xs text-red-600 mt-1" id="gen-pok-error"></p>
                                    </div>
                                </div>

                                <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">
                                    <button type="button" id="generate-modal-close2" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">Batal</button>
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">Generate & Unduh</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('requests.show', $checklist->request_id) }}" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded text-sm">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded text-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

    @if(str_contains($checklist->nama_dokumen, 'Surat Tugas'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tbody = document.querySelector('#pelaksana-table tbody');

                function updateNumbering() {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(function (row, i) {
                        const noCell = row.querySelector('.pel-urutan');
                        if (noCell) noCell.textContent = i + 1;
                    });
                }

                function bindRemove() {
                    tbody.querySelectorAll('.remove-pelaksana').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            const rows = tbody.querySelectorAll('tr');
                            if (rows.length <= 1) return;
                            btn.closest('tr').remove();
                            updateNumbering();
                        });
                    });
                }

                document.getElementById('add-pelaksana').addEventListener('click', function () {
                    const count = tbody.querySelectorAll('tr').length;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-gray-500 pel-urutan">${count + 1}</td>
                        <td class="px-3 py-2"><input type="text" name="pelaksana_nama[]" class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></td>
                        <td class="px-3 py-2 text-gray-400">-</td>
                        <td class="px-3 py-2 text-center"><button type="button" class="remove-pelaksana text-red-600 hover:text-red-800 font-semibold text-xs">Hapus</button></td>
                    `;
                    tbody.appendChild(tr);
                    bindRemove();
                    updateNumbering();
                });

                // --- Input Massal Pelaksana ---
                function addPelaksanaRow(name) {
                    const count = tbody.querySelectorAll('tr').length;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-gray-500 pel-urutan">${count + 1}</td>
                        <td class="px-3 py-2"><input type="text" name="pelaksana_nama[]" value="${name.replace(/"/g, '&quot;')}" class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></td>
                        <td class="px-3 py-2 text-gray-400">-</td>
                        <td class="px-3 py-2 text-center"><button type="button" class="remove-pelaksana text-red-600 hover:text-red-800 font-semibold text-xs">Hapus</button></td>
                    `;
                    tbody.appendChild(tr);
                    bindRemove();
                    updateNumbering();
                }

                function parseBulk() {
                    const raw = document.getElementById('bulk-pelaksana').value;
                    const sep = document.querySelector('input[name="bulk_separator"]:checked').value;
                    let parts = [];
                    if (sep === 'newline') {
                        parts = raw.split(/\r?\n/);
                    } else if (sep === 'semicolon') {
                        parts = raw.split(';');
                    } else {
                        parts = raw.split(',');
                    }
                    return parts.map(s => s.trim()).filter(s => s !== '');
                }

                document.getElementById('preview-pelaksana').addEventListener('click', function () {
                    const names = parseBulk();
                    const preview = document.getElementById('bulk-preview');
                    const list = document.getElementById('preview-list');
                    const hint = document.getElementById('bulk-hint');
                    list.innerHTML = '';
                    hint.textContent = '';
                    document.getElementById('preview-count').textContent = names.length;
                    if (names.length === 0) {
                        hint.textContent = 'Tidak ada nama yang ditemukan.';
                        document.getElementById('confirm-pelaksana').disabled = true;
                        preview.classList.add('hidden');
                        return;
                    }
                    names.forEach(function (n) {
                        const li = document.createElement('li');
                        li.textContent = n;
                        list.appendChild(li);
                    });
                    preview.classList.remove('hidden');
                    document.getElementById('confirm-pelaksana').disabled = false;
                });

                document.getElementById('confirm-pelaksana').addEventListener('click', function () {
                    const names = parseBulk();
                    if (names.length === 0) return;
                    if (!confirm('Tambahkan ' + names.length + ' pelaksana ke daftar?')) return;
                    names.forEach(addPelaksanaRow);
                    document.getElementById('bulk-pelaksana').value = '';
                    document.getElementById('bulk-preview').classList.add('hidden');
                    document.getElementById('preview-list').innerHTML = '';
                    document.getElementById('confirm-pelaksana').disabled = true;
                });

                bindRemove();
            });
        </script>
    @endif

    @if(str_contains($checklist->nama_dokumen, 'Laporan Perjalanan'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const rows = document.querySelectorAll('.report-row');
                const checklistId = {{ $checklist->id }};
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                function syncRow(row) {
                    const checked = row.querySelector('.report-check').checked;
                    const sel = row.querySelector('.report-status');
                    sel.disabled = !checked;
                    row.classList.toggle('bg-indigo-50', checked);
                }

                rows.forEach(function (row) {
                    row.querySelector('.report-check').addEventListener('change', function () { syncRow(row); });
                    syncRow(row);
                });

                // Konfirmasi saat mengubah kembali ke "Belum Mengumpulkan" padahal sudah ada file laporan.
                rows.forEach(function (row) {
                    const sel = row.querySelector('.report-status');
                    const pid = sel.dataset.pelaksana;
                    const hasFile = sel.dataset.hasFile === '1';
                    let prev = sel.value;
                    sel.addEventListener('change', function () {
                        if (sel.value === 'Belum Mengumpulkan' && prev !== 'Belum Mengumpulkan' && hasFile) {
                            const nama = row.querySelector('.report-check').closest('tr').querySelector('td:nth-child(2)').textContent.trim();
                            if (!confirm('Pelaksana ' + nama + ' sudah memiliki laporan yang diunggah. Yakin ingin mengubah status menjadi Belum Mengumpulkan?')) {
                                sel.value = prev;
                                return;
                            }
                        }
                        prev = sel.value;
                    });
                });

                const selAll = document.getElementById('report-select-all');
                if (selAll) {
                    selAll.addEventListener('change', function () {
                        rows.forEach(function (row) {
                            row.querySelector('.report-check').checked = selAll.checked;
                            syncRow(row);
                        });
                    });
                }

                function flash(message, isError) {
                    alert(message);
                }

                /* ---------- Bulk status massal (di luar tabel) ---------- */
                document.getElementById('bulk-report-apply').addEventListener('click', function () {
                    const ids = [];
                    rows.forEach(function (row) {
                        if (row.querySelector('.report-check').checked) {
                            ids.push(row.querySelector('.report-check').dataset.pelaksana);
                        }
                    });
                    if (!ids.length) {
                        flash('Centang minimal satu pelaksana terlebih dahulu.', true);
                        return;
                    }
                    const status = document.getElementById('bulk-report-status').value;
                    fetch(`/checklists/${checklistId}/pelaksana/bulk-status`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ pelaksana_ids: ids, status: status })
                    })
                    .then(res => res.json().then(d => ({ ok: res.ok, data: d })))
                    .then(({ ok, data }) => {
                        if (!ok) {
                            flash((data && data.message) ? data.message : 'Gagal mengubah status massal.', true);
                        } else {
                            ids.forEach(function (pid) {
                                rows.forEach(function (row) {
                                    if (row.querySelector('.report-check').dataset.pelaksana === pid) {
                                        row.querySelector('.report-status').value = status;
                                    }
                                });
                            });
                            flash('Status ' + ids.length + ' pelaksana diperbarui menjadi ' + status + '.');
                        }
                    })
                    .catch(() => flash('Terjadi kesalahan koneksi.', true));
                });

                /* ---------- Upload laporan per pelaksana ---------- */
                document.querySelectorAll('.upload-trigger').forEach(function (label) {
                    label.addEventListener('click', function () {
                        const fileInput = label.closest('td').querySelector('.upload-file');
                        fileInput.click();
                    });
                });
                document.querySelectorAll('.upload-file').forEach(function (input) {
                    input.addEventListener('change', function () {
                        const pid = input.dataset.pelaksana;
                        const file = input.files[0];
                        if (!file) return;
                        const fd = new FormData();
                        fd.append('file_laporan', file);
                        fetch(`/checklists/${checklistId}/pelaksana/${pid}/upload`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            body: fd
                        })
                        .then(res => res.json().then(d => ({ ok: res.ok, data: d })))
                        .then(({ ok, data }) => {
                            if (ok && data.success) {
                                rows.forEach(function (row) {
                                    if (row.querySelector('.report-check').dataset.pelaksana === pid) {
                                        row.querySelector('.report-status').value = 'Sudah Mengumpulkan';
                                    }
                                });
                                flash(data.message || 'Laporan berhasil diunggah.');
                                setTimeout(function () { location.reload(); }, 1200);
                            } else {
                                flash((data && data.message) ? data.message : 'Gagal mengunggah laporan.', true);
                            }
                        })
                        .catch(() => flash('Terjadi kesalahan koneksi.', true));
                    });
                });

                /* ---------- Generate laporan (modal + autocomplete POK) ---------- */
                const genModal = document.getElementById('generate-modal');
                const genForm = document.getElementById('generate-form');
                let pokTimer = null;

                function showGenError(msg) {
                    document.getElementById('gen-pok-error').textContent = msg || '';
                }

                function openGenerateModal(pelaksanaId, nama) {
                    document.getElementById('gen-pelaksana-id').value = pelaksanaId;
                    document.getElementById('gen-pelaksana-nama').textContent = nama;
                    genModal.classList.remove('hidden');
                    showGenError('');
                }

                document.getElementById('generate-modal-close').addEventListener('click', () => genModal.classList.add('hidden'));
                document.getElementById('generate-modal-close2').addEventListener('click', () => genModal.classList.add('hidden'));
                genModal.addEventListener('click', function (e) {
                    if (e.target === genModal) genModal.classList.add('hidden');
                });

                document.querySelectorAll('.generate-trigger').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const pid = btn.dataset.pelaksana;
                        const nama = btn.dataset.nama;
                        document.getElementById('gen-judul').value = btn.dataset.judul || '';
                        document.getElementById('gen-jenis').value = btn.dataset.jenis || 'LAPORAN_PENDATAAN';
                        const tanggal = btn.dataset.tanggal || '';
                        const flatpickrEl = document.getElementById('gen-tanggal');
                        if (window.flatpickr) {
                            const fp = flatpickrEl._flatpickr;
                            if (fp) fp.setDate(tanggal || '');
                            else flatpickrEl.value = tanggal;
                        } else {
                            flatpickrEl.value = tanggal;
                        }
                        document.getElementById('gen-pok-search').value = '';
                        document.getElementById('gen-pok-id').value = btn.dataset.pok || '';
                        document.getElementById('gen-pok-results').classList.add('hidden');
                        document.getElementById('gen-pok-detail').classList.add('hidden');
                        if (btn.dataset.pok) { loadPokDetail(btn.dataset.pok); }
                        openGenerateModal(pid, nama);
                    });
                });

                function renderPokResults(items) {
                    const box = document.getElementById('gen-pok-results');
                    box.innerHTML = '';
                    if (!items.length) {
                        box.innerHTML = '<div class="px-3 py-2 text-gray-500">Tidak ada POK yang cocok.</div>';
                        box.classList.remove('hidden');
                        return;
                    }
                    items.forEach(function (it) {
                        const div = document.createElement('div');
                        div.className = 'px-3 py-2 cursor-pointer hover:bg-indigo-50';
                        div.textContent = it.rincian;
                        div.addEventListener('click', function () {
                            document.getElementById('gen-pok-search').value = it.rincian;
                            document.getElementById('gen-pok-id').value = it.id;
                            box.classList.add('hidden');
                            renderPokDetail(it);
                        });
                        box.appendChild(div);
                    });
                    box.classList.remove('hidden');
                }

                function renderPokDetail(it) {
                    const detail = document.getElementById('gen-pok-detail');
                    const lines = [
                        ['Program', it.program],
                        ['Kegiatan', it.kegiatan],
                        ['Output', it.output],
                        ['Sub Output', it.sub_output],
                        ['Komponen', it.komponen],
                        ['Akun', it.akun],
                        ['Rincian', it.rincian],
                    ];
                    detail.innerHTML = lines.map(function (l) {
                        return '<div><span class="font-semibold text-gray-700">' + l[0] + ':</span> ' + l[1] + '</div>';
                    }).join('');
                    detail.classList.remove('hidden');
                }

                function loadPokDetail(id) {
                    fetch(`/travel-reports/pok/${id}`)
                        .then(res => res.json())
                        .then(d => { if (d.success) renderPokDetail(d.data); })
                        .catch(() => {});
                }

                document.getElementById('gen-pok-search').addEventListener('input', function () {
                    clearTimeout(pokTimer);
                    const q = this.value;
                    if (q.length < 3) {
                        document.getElementById('gen-pok-results').classList.add('hidden');
                        return;
                    }
                    pokTimer = setTimeout(function () {
                        fetch(`/travel-reports/pok/search?q=${encodeURIComponent(q)}`)
                            .then(res => res.json())
                            .then(d => { if (d.success) renderPokResults(d.data); })
                            .catch(() => {});
                    }, 300);
                });

                genForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const pid = document.getElementById('gen-pelaksana-id').value;
                    const pokId = document.getElementById('gen-pok-id').value;
                    const judul = document.getElementById('gen-judul').value.trim();
                    const tanggal = document.getElementById('gen-tanggal').value;
                    if (!pokId) {
                        showGenError('Pilih POK (pembiayaan) terlebih dahulu.');
                        return;
                    }
                    if (!judul) {
                        showGenError('Judul laporan wajib diisi.');
                        return;
                    }
                    if (!tanggal) {
                        showGenError('Tanggal laporan wajib diisi.');
                        return;
                    }
                    showGenError('');
                    const fd = new FormData(genForm);
                    fetch(`/checklists/${checklistId}/pelaksana/${pid}/laporan`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: fd
                    })
                    .then(async function (res) {
                        if (!res.ok) {
                            let message = 'Gagal generate laporan: lengkapi data laporan perjalanan terlebih dahulu.';
                            const ct = res.headers.get('Content-Type') || '';
                            if (ct.indexOf('json') !== -1) { try { const j = await res.json(); message = (j.errors ? Object.values(j.errors).flat().join(' ') : j.message) || message; } catch (e) {} }
                            showGenError(message);
                            return;
                        }
                        const blob = await res.blob();
                        const disposition = res.headers.get('Content-Disposition') || '';
                        const filename = (disposition.match(/filename="?([^"]+)"?/) || [null, 'Laporan_Perjalanan.docx'])[1];
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url; a.download = filename;
                        document.body.appendChild(a); a.click(); document.body.removeChild(a);
                        setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
                        genModal.classList.add('hidden');
                        alert('Laporan berhasil digenerate.');
                        setTimeout(function () { location.reload(); }, 1200);
                    })
                    .catch(() => showGenError('Terjadi kesalahan koneksi.'));
                });
            });
        </script>
    @endif
</x-app-layout>
