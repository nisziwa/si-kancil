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

            <!-- Form Generate Satu Pelaksana -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3 border-b pb-2">Generate Superkendis per Pelaksana</h3>

                @if($superkendisDone && $stChecklist && $stChecklist->suratTugasDetail && $stChecklist->suratTugasDetail->pelaksanas->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="kecamatan" class="block text-sm font-medium text-gray-700">Kecamatan Tujuan *</label>
                            <select name="kecamatan" id="kecamatan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" form="superkendis-form">
                                <option value="">-- Pilih Kecamatan --</option>
                                @forelse($kecamatans as $k)
                                    <option value="{{ $k->kecamatan }}" {{ old('kecamatan', request('kecamatan')) === $k->kecamatan ? 'selected' : '' }}>
                                        {{ $k->kecamatan }} (Rp {{ number_format($k->besaran_biaya_transport, 0, ',', '.') }})
                                    </option>
                                @empty
                                    <option value="" disabled>Belum ada data rate kecamatan. Isi tabel sk_rate_perjalanan terlebih dahulu.</option>
                                @endforelse
                            </select>
                        </div>

                        <div>
                            <label for="tanggal_perjalanan" class="block text-sm font-medium text-gray-700">Tanggal Perjalanan *</label>
                            <input type="date" name="tanggal_perjalanan" id="tanggal_perjalanan" required value="{{ old('tanggal_perjalanan', request('tanggal_perjalanan')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" form="superkendis-form">
                        </div>

                        <div>
                            <label for="nip" class="block text-sm font-medium text-gray-700">NIP (opsional)</label>
                            <input type="text" name="nip" id="nip" value="{{ old('nip', request('nip')) }}" placeholder="Kosongkan jika tidak ada" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" form="superkendis-form">
                            <p class="text-xs text-gray-500 mt-1">Jika kosong atau format tidak sesuai, akan terisi "-".</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Format File</label>
                            <div class="mt-1 flex gap-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="format" value="docx" form="superkendis-form" class="rounded border-gray-300 text-indigo-600" checked>
                                    <span class="ml-2 text-sm text-gray-700">DOCX</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="format" value="pdf" form="superkendis-form" class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700">PDF</span>
                                </label>
                            </div>
                        </div>

                        <form id="superkendis-form" method="POST" action="{{ route('requests.superkendis.generate', ['requestId' => $requestModel->id, 'pelaksanaId' => request('pelaksana', $stChecklist->suratTugasDetail->pelaksanas->first()->id)]) }}" class="md:col-span-2">
                            @csrf
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Generate Superkendis
                            </button>
                        </form>
                    </div>

                    <!-- Tabel Pelaksana & Generate -->
                    <table class="min-w-full divide-y divide-gray-200 text-sm mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nama Pelaksana</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nomor Sub Surat Tugas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stChecklist->suratTugasDetail->pelaksanas as $index => $pelaksana)
                                <tr class="{{ request('pelaksana') == $pelaksana->id ? 'bg-indigo-50' : '' }}">
                                    <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $pelaksana->nama_pelaksana }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $pelaksana->nomor_surat ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Bulk Download -->
                    <div class="mt-6 border-t pt-4">
                        <h4 class="font-semibold text-gray-700 mb-3">Bulk Download Superkendis</h4>

                        <form method="POST" id="bulk-form">
                            @csrf
                            <input type="hidden" name="kecamatan" value="{{ old('kecamatan', request('kecamatan')) }}">
                            <input type="hidden" name="tanggal_perjalanan" value="{{ old('tanggal_perjalanan', request('tanggal_perjalanan')) }}">
                            <input type="hidden" name="nip" value="{{ old('nip', request('nip')) }}">

                            <div class="flex items-end gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Format</label>
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center"><input type="radio" name="format" value="docx" checked class="rounded border-gray-300 text-indigo-600"><span class="ml-1 text-sm text-gray-700">DOCX</span></label>
                                        <label class="inline-flex items-center"><input type="radio" name="format" value="pdf" class="rounded border-gray-300 text-indigo-600"><span class="ml-1 text-sm text-gray-700">PDF</span></label>
                                    </div>
                                </div>
                                <button type="submit" formaction="{{ route('requests.superkendis.bulk-separate', $requestModel->id) }}"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded text-sm">
                                    Pisah File (ZIP)
                                </button>
                                <button type="submit" formaction="{{ route('requests.superkendis.bulk-merged', $requestModel->id) }}"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded text-sm">
                                    Gabung Satu File
                                </button>
                            </div>
                        </form>

                        <p class="text-xs text-gray-500 mt-2">
                            <strong>Pisah file:</strong> ZIP berisi file terpisah per pelaksana (mis. Superkendis_Budi.docx, Superkendis_Siti.docx).<br>
                            <strong>Gabung:</strong> satu file gabungan (mis. Superkendis_Gabungan.docx).
                        </p>
                    </div>
                @else
                    <p class="text-gray-500 italic text-sm">Belum ada pelaksana Surat Tugas yang lengkap untuk digenerate.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
