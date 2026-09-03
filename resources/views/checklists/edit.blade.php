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
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                                <input type="date" name="tanggal_surat_tugas" id="tanggal_surat_tugas" value="{{ old('tanggal_surat_tugas', optional($checklist->suratTugasDetail->tanggal_surat_tugas ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
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
                        <p class="text-sm text-gray-500 mb-4">Centang pelaksana yang laporannya sudah/belum dikumpulkan, lalu pilih status dan simpan. Status disimpan per pelaksana (bulk).</p>

                        @if($stPelaksanas->count() > 0)
                            @php
                                $reportStatuses = $checklist->travelReportPelaksanas->keyBy('surat_tugas_pelaksana_id');
                            @endphp
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
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stPelaksanas as $idx => $p)
                                            @php
                                                $existing = $reportStatuses->get($p->id);
                                                $currStatus = old('report_status.status.'.$p->id, $existing ? $existing->status : \App\Models\TravelReportPelaksana::STATUS_BELUM);
                                                $currChecked = old('report_status.selected.'.$p->id) ? true : ($existing ? true : false);
                                            @endphp
                                            <tr class="report-row">
                                                <td class="px-3 py-2 text-center">
                                                    <input type="checkbox" name="report_status[selected][{{ $p->id }}]" value="1"
                                                           class="report-check rounded border-gray-300 text-indigo-600" {{ $currChecked ? 'checked' : '' }}>
                                                </td>
                                                <td class="px-3 py-2 font-medium text-gray-800">{{ $p->nama_pelaksana }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $p->nomor_surat ?: '-' }}</td>
                                                <td class="px-3 py-2">
                                                    <select name="report_status[status][{{ $p->id }}]" class="report-status block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                                        @foreach(\App\Models\TravelReportPelaksana::STATUS_LIST as $st)
                                                            <option value="{{ $st }}" {{ $currStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Simpan Perubahan
                                </button>
                                <p class="text-xs text-gray-500">
                                    Checklist <strong>Laporan Perjalanan</strong> hanya menjadi <strong>Lengkap</strong> bila seluruh pelaksana sudah mengumpulkan.
                                </p>
                            </div>
                        @else
                            <p class="text-gray-500 italic text-sm">Isi terlebih dahulu checklist <strong>Surat Tugas</strong> supaya daftar pelaksana tersedia.</p>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('requests.show', $checklist->request_id) }}" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded text-sm">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded text-sm">
                        Simpan Semua Perubahan
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

                const selAll = document.getElementById('report-select-all');
                if (selAll) {
                    selAll.addEventListener('change', function () {
                        rows.forEach(function (row) {
                            row.querySelector('.report-check').checked = selAll.checked;
                            syncRow(row);
                        });
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
