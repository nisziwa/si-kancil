<div>
    <label for="sub_output_id" class="block text-sm font-medium text-gray-700">Sub Output *</label>
    <select name="sub_output_id" id="sub_output_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Sub Output --</option>
        @foreach($parents['subOutput'] as $so)
            <option value="{{ $so->id }}" @selected(old('sub_output_id', $row->sub_output_id ?? '') == $so->id)>{{ $so->kode_sub_output }} - {{ $so->nama_sub_output }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="kode_komponen" class="block text-sm font-medium text-gray-700">Kode Komponen *</label>
    <input type="text" name="kode_komponen" id="kode_komponen" value="{{ old('kode_komponen', $row->kode_komponen ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_komponen" class="block text-sm font-medium text-gray-700">Nama Komponen *</label>
    <input type="text" name="nama_komponen" id="nama_komponen" value="{{ old('nama_komponen', $row->nama_komponen ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>