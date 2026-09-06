<div>
    <label for="kode_akun" class="block text-sm font-medium text-gray-700">Kode Akun *</label>
    <input type="text" name="kode_akun" id="kode_akun" value="{{ old('kode_akun', $row->kode_akun ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_akun" class="block text-sm font-medium text-gray-700">Nama Akun *</label>
    <input type="text" name="nama_akun" id="nama_akun" value="{{ old('nama_akun', $row->nama_akun ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>