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
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Khusus: SPD / SPPD -->
                @if(str_contains($checklist->nama_dokumen, 'SPD') || str_contains($checklist->nama_dokumen, 'SPPD'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Perjalanan Dinas (SPD/SPPD)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nomor_spd" class="block text-sm font-medium text-gray-700">Nomor SPD</label>
                                <input type="text" name="nomor_spd" id="nomor_spd" value="{{ old('nomor_spd', $checklist->travelDetail->nomor_spd ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: SPD/123/2026">
                            </div>

                            <div>
                                <label for="travel_nama_pelaksana" class="block text-sm font-medium text-gray-700">Nama Pelaksana</label>
                                <input type="text" name="travel_nama_pelaksana" id="travel_nama_pelaksana" value="{{ old('travel_nama_pelaksana', $checklist->travelDetail->nama_pelaksana ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="tempat_berangkat" class="block text-sm font-medium text-gray-700">Tempat Berangkat</label>
                                <input type="text" name="tempat_berangkat" id="tempat_berangkat" value="{{ old('tempat_berangkat', $checklist->travelDetail->tempat_berangkat ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="tempat_tujuan" class="block text-sm font-medium text-gray-700">Tempat Tujuan</label>
                                <input type="text" name="tempat_tujuan" id="tempat_tujuan" value="{{ old('tempat_tujuan', $checklist->travelDetail->tempat_tujuan ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700">Tanggal Berangkat</label>
                                <input type="date" name="tanggal_berangkat" id="tanggal_berangkat" value="{{ old('tanggal_berangkat', optional($checklist->travelDetail->tanggal_berangkat ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                                <input type="date" name="tanggal_kembali" id="tanggal_kembali" value="{{ old('tanggal_kembali', optional($checklist->travelDetail->tanggal_kembali ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="transportasi" class="block text-sm font-medium text-gray-700">Moda Transportasi</label>
                                <input type="text" name="transportasi" id="transportasi" value="{{ old('transportasi', $checklist->travelDetail->transportasi ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: Darat / Kendaraan Pribadi / Pesawat">
                            </div>

                            <div class="md:col-span-2">
                                <label for="maksud_perjalanan" class="block text-sm font-medium text-gray-700">Maksud Perjalanan</label>
                                <textarea name="maksud_perjalanan" id="maksud_perjalanan" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('maksud_perjalanan', $checklist->travelDetail->maksud_perjalanan ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Khusus: Pengeluaran Riil + Surat Non Kendaraan Dinas -->
                @if(str_contains($checklist->nama_dokumen, 'Pengeluaran Riil'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Pengeluaran Riil & Surat Non Kendaraan Dinas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="real_nomor_surat_tugas" class="block text-sm font-medium text-gray-700">Nomor Surat Tugas</label>
                                <input type="text" name="real_nomor_surat_tugas" id="real_nomor_surat_tugas" value="{{ old('real_nomor_surat_tugas', $checklist->realExpenseDetail->nomor_surat_tugas ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="real_tanggal_surat_tugas" class="block text-sm font-medium text-gray-700">Tanggal Surat Tugas</label>
                                <input type="date" name="real_tanggal_surat_tugas" id="real_tanggal_surat_tugas" value="{{ old('real_tanggal_surat_tugas', optional($checklist->realExpenseDetail->tanggal_surat_tugas ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="real_nama_pelaksana" class="block text-sm font-medium text-gray-700">Nama Pelaksana</label>
                                <input type="text" name="real_nama_pelaksana" id="real_nama_pelaksana" value="{{ old('real_nama_pelaksana', $checklist->realExpenseDetail->nama_pelaksana ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="real_jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                                <input type="text" name="real_jabatan" id="real_jabatan" value="{{ old('real_jabatan', $checklist->realExpenseDetail->jabatan ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="real_tanggal_kegiatan" class="block text-sm font-medium text-gray-700">Tanggal Kegiatan</label>
                                <input type="date" name="real_tanggal_kegiatan" id="real_tanggal_kegiatan" value="{{ old('real_tanggal_kegiatan', optional($checklist->realExpenseDetail->tanggal_kegiatan ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="jumlah_pengeluaran" class="block text-sm font-medium text-gray-700">Jumlah Pengeluaran (Rp)</label>
                                <input type="number" step="0.01" name="jumlah_pengeluaran" id="jumlah_pengeluaran" value="{{ old('jumlah_pengeluaran', $checklist->realExpenseDetail->jumlah_pengeluaran ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label for="uraian_pengeluaran" class="block text-sm font-medium text-gray-700">Uraian Pengeluaran</label>
                                <textarea name="uraian_pengeluaran" id="uraian_pengeluaran" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('uraian_pengeluaran', $checklist->realExpenseDetail->uraian_pengeluaran ?? '') }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label for="real_keterangan" class="block text-sm font-medium text-gray-700">Keterangan Tambahan</label>
                                <textarea name="real_keterangan" id="real_keterangan" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('real_keterangan', $checklist->realExpenseDetail->keterangan ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form Khusus: Laporan Perjalanan -->
                @if(str_contains($checklist->nama_dokumen, 'Laporan Perjalanan'))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 text-indigo-700">Detail Laporan Perjalanan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="report_nama_pelaksana" class="block text-sm font-medium text-gray-700">Nama Pelaksana</label>
                                <input type="text" name="report_nama_pelaksana" id="report_nama_pelaksana" value="{{ old('report_nama_pelaksana', $checklist->travelReport->nama_pelaksana ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="report_tujuan" class="block text-sm font-medium text-gray-700">Tujuan Perjalanan</label>
                                <input type="text" name="report_tujuan" id="report_tujuan" value="{{ old('report_tujuan', $checklist->travelReport->tujuan ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="report_tanggal_kegiatan" class="block text-sm font-medium text-gray-700">Tanggal Kegiatan</label>
                                <input type="date" name="report_tanggal_kegiatan" id="report_tanggal_kegiatan" value="{{ old('report_tanggal_kegiatan', optional($checklist->travelReport->tanggal_kegiatan ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            </div>

                            <div>
                                <label for="report_dokumentasi" class="block text-sm font-medium text-gray-700">Upload Foto/Dokumentasi Kegiatan</label>
                                <input type="file" name="report_dokumentasi" id="report_dokumentasi" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @if($checklist->travelReport && $checklist->travelReport->dokumentasi)
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $checklist->travelReport->dokumentasi) }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                            Lihat Lampiran Dokumentasi
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="md:col-span-2">
                                <label for="report_uraian_kegiatan" class="block text-sm font-medium text-gray-700">Uraian Hasil Kegiatan</label>
                                <textarea name="report_uraian_kegiatan" id="report_uraian_kegiatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('report_uraian_kegiatan', $checklist->travelReport->uraian_kegiatan ?? '') }}</textarea>
                            </div>
                        </div>
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

                bindRemove();
            });
        </script>
    @endif
</x-app-layout>
