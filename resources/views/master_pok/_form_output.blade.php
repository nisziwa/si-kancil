<div>
    <label for="kegiatan_id" class="block text-sm font-medium text-gray-700">Kegiatan *</label>
    <select name="kegiatan_id" id="kegiatan_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Kegiatan --</option>
        @foreach($parents['kegiatan'] as $k)
            <option value="{{ $k->id }}" @selected(old('kegiatan_id', $row->kegiatan_id ?? '') == $k->id)>{{ $k->kode_kegiatan }} - {{ $k->nama_kegiatan }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="kode_output" class="block text-sm font-medium text-gray-700">Kode Output *</label>
    <input type="text" name="kode_output" id="kode_output" value="{{ old('kode_output', $row->kode_output ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_output" class="block text-sm font-medium text-gray-700">Nama Output *</label>
    <input type="text" name="nama_output" id="nama_output" value="{{ old('nama_output', $row->nama_output ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>