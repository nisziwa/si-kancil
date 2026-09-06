<div>
    <label for="program_id" class="block text-sm font-medium text-gray-700">Program *</label>
    <select name="program_id" id="program_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Program --</option>
        @foreach($parents['program'] as $p)
            <option value="{{ $p->id }}" @selected(old('program_id', $row->program_id ?? '') == $p->id)>{{ $p->kode_program }} - {{ $p->nama_program }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="kode_kegiatan" class="block text-sm font-medium text-gray-700">Kode Kegiatan *</label>
    <input type="text" name="kode_kegiatan" id="kode_kegiatan" value="{{ old('kode_kegiatan', $row->kode_kegiatan ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_kegiatan" class="block text-sm font-medium text-gray-700">Nama Kegiatan *</label>
    <input type="text" name="nama_kegiatan" id="nama_kegiatan" value="{{ old('nama_kegiatan', $row->nama_kegiatan ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>