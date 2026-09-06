<div>
    <label for="kode_program" class="block text-sm font-medium text-gray-700">Kode Program *</label>
    <input type="text" name="kode_program" id="kode_program" value="{{ old('kode_program', $row->kode_program ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_program" class="block text-sm font-medium text-gray-700">Nama Program *</label>
    <input type="text" name="nama_program" id="nama_program" value="{{ old('nama_program', $row->nama_program ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>