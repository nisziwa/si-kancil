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
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
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

            <!-- Form Generate per Pelaksana -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3 border-b pb-2">Generate Superkendis per Pelaksana</h3>
                <p class="text-sm text-gray-500 mb-4">Centang pelaksana yang ingin dibuatkan Superkendis, lalu isi data per pelaksana (kecamatan tujuan, tanggal perjalanan, NIP).</p>

                @if($superkendisDone && $stChecklist && $stChecklist->suratTugasDetail && $stChecklist->suratTugasDetail->pelaksanas->count() > 0)
                    <form method="POST" action="{{ route('requests.superkendis.bulk', $requestModel->id) }}" id="superkendis-form">
                        @csrf

                        <!-- Tabel Pelaksana + Checkbox + Input -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 w-10">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600">
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Pelaksana</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Surat Tugas</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Kecamatan Tujuan *</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal Perjalanan *</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Jenis Kegiatan</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">NIP</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Dokumen</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($stChecklist->suratTugasDetail->pelaksanas as $index => $pelaksana)
                                        @php
                                            $sk = $pelaksana->superkendis;
                                            $presetKecamatan = old('pelaksana.'.$pelaksana->id.'.kecamatan', $sk->kecamatan ?? '');
                                            $presetTanggal = old('pelaksana.'.$pelaksana->id.'.tanggal_perjalanan', $sk && $sk->tanggal_perjalanan ? $sk->tanggal_perjalanan->format('Y-m-d') : '');
                                            $presetJenis = old('pelaksana.'.$pelaksana->id.'.jenis_kegiatan', $sk->jenis_kegiatan ?? 'Pendataan Lapangan');
                                            $presetNip = old('pelaksana.'.$pelaksana->id.'.nip', $sk && $sk->nip ? $sk->nip : '');
                                            $checked = in_array((int) $pelaksana->id, $selectedPelaksanaIds, true);
                                        @endphp
                                        <tr class="pelaksana-row hover:bg-gray-50">
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" name="pelaksana[{{ $pelaksana->id }}][selected]" value="1"
                                                       class="pelaksana-check rounded border-gray-300 text-indigo-600"
                                                       {{ $checked ? 'checked' : '' }}>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap">
                                                {{ $pelaksana->nama_pelaksana }}
                                                <span class="block text-xs text-gray-400">(dari Surat Tugas)</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $pelaksana->nomor_surat ?: '-' }}</td>
                                            <td class="px-4 py-3">
                                                <select name="pelaksana[{{ $pelaksana->id }}][kecamatan]"
                                                        class="pelaksana-input mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                                    <option value="">-- Pilih --</option>
                                                    @foreach($kecamatans as $k)
                                                        <option value="{{ $k->kecamatan }}" {{ $presetKecamatan === $k->kecamatan ? 'selected' : '' }}>
                                                            {{ $k->kecamatan }} (Rp {{ number_format($k->besaran_biaya_transport, 0, ',', '.') }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="date" name="pelaksana[{{ $pelaksana->id }}][tanggal_perjalanan]"
                                                       value="{{ $presetTanggal }}"
                                                       class="pelaksana-input mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                            </td>
                                            <td class="px-4 py-3">
                                                <select name="pelaksana[{{ $pelaksana->id }}][jenis_kegiatan]"
                                                        class="pelaksana-input mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                                    @foreach(\App\Http\Controllers\SuperkendisController::JENIS_KEGIATAN_LIST as $jk)
                                                        <option value="{{ $jk }}" {{ $presetJenis === $jk ? 'selected' : '' }}>{{ $jk }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" name="pelaksana[{{ $pelaksana->id }}][nip]"
                                                       value="{{ $presetNip }}"
                                                       placeholder="Kosongkan -> '-'"
                                                       class="pelaksana-input mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-xs disabled:bg-gray-100">
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($sk)
                                                    <div class="flex flex-col gap-1 text-xs">
                                                        @if($sk->file_docx)
                                                            <a href="{{ asset('storage/' . $sk->file_docx) }}" target="_blank" class="text-green-700 hover:underline">DOCX tersimpan</a>
                                                        @endif
                                                        @if($sk->file_pdf)
                                                            <a href="{{ asset('storage/' . $sk->file_pdf) }}" target="_blank" class="text-red-700 hover:underline">PDF tersimpan</a>
                                                        @endif
                                                        <span class="text-gray-400">{{ $sk->jabatan }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">Belum digenerate</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xs text-gray-500 mt-2">
                            Nama pelaksana dan nomor surat diambil dari <strong>Surat Tugas</strong>. NIP opsional: jika kosong atau tidak sesuai akan terisi "-". Kecamatan tujuan digunakan untuk mengambil besaran biaya transport dari SK Rate. Generate ulang akan memperbarui dokumen yang sudah ada.
                        </p>

                        <!-- Format & Metode -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Format Output</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="format" value="docx" checked class="rounded border-gray-300 text-indigo-600">
                                        <span class="ml-1 text-sm text-gray-700">DOCX</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="format" value="pdf" class="rounded border-gray-300 text-indigo-600">
                                        <span class="ml-1 text-sm text-gray-700">PDF</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Metode Output</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="method" value="separate" checked class="rounded border-gray-300 text-indigo-600">
                                        <span class="ml-1 text-sm text-gray-700">Pisah File (ZIP)</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="method" value="merged" class="rounded border-gray-300 text-indigo-600">
                                        <span class="ml-1 text-sm text-gray-700">Gabung Satu File</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-2">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Generate Superkendis
                            </button>
                            <a href="{{ route('requests.superkendis', $requestModel->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">
                                Reset
                            </a>
                        </div>
                    </form>
                @else
                    <p class="text-gray-500 italic text-sm">Belum ada pelaksana Surat Tugas yang lengkap untuk digenerate.</p>
                @endif
            </div>

        </div>
    </div>

    @if($superkendisDone && $stChecklist && $stChecklist->suratTugasDetail && $stChecklist->suratTugasDetail->pelaksanas->count() > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const rows = document.querySelectorAll('.pelaksana-row');

                function syncRow(row) {
                    const checked = row.querySelector('.pelaksana-check').checked;
                    row.querySelectorAll('.pelaksana-input').forEach(function (input) {
                        input.disabled = !checked;
                        if (!checked) {
                            input.value = '';
                        }
                    });
                    row.classList.toggle('bg-indigo-50', checked);
                }

                rows.forEach(function (row) {
                    row.querySelector('.pelaksana-check').addEventListener('change', function () {
                        syncRow(row);
                    });
                    syncRow(row);
                });

                const selectAll = document.getElementById('select-all');
                selectAll.addEventListener('change', function () {
                    rows.forEach(function (row) {
                        row.querySelector('.pelaksana-check').checked = selectAll.checked;
                        syncRow(row);
                    });
                });

                document.getElementById('superkendis-form').addEventListener('submit', function (e) {
                    const anyChecked = Array.from(document.querySelectorAll('.pelaksana-check')).some(function (c) { return c.checked; });
                    if (!anyChecked) {
                        e.preventDefault();
                        alert('Pilih minimal satu pelaksana untuk generate Superkendis.');
                        return;
                    }
                    // Validasi per pelaksana yang dipilih: kecamatan & tanggal wajib
                    let missing = false;
                    rows.forEach(function (row) {
                        const checked = row.querySelector('.pelaksana-check').checked;
                        if (!checked) return;
                        const kecamatan = row.querySelector('select.pelaksana-input').value;
                        const tanggal = row.querySelector('input[type=date].pelaksana-input').value;
                        if (!kecamatan || !tanggal) {
                            missing = true;
                            row.classList.add('bg-red-50');
                        }
                    });
                    if (missing) {
                        e.preventDefault();
                        alert('Kecamatan tujuan dan tanggal perjalanan wajib diisi untuk setiap pelaksana yang dipilih.');
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
