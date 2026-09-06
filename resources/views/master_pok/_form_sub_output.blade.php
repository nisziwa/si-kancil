<div>
    <label for="output_id" class="block text-sm font-medium text-gray-700">Output *</label>
    <select name="output_id" id="output_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Output --</option>
        @foreach($parents['output'] as $o)
            <option value="{{ $o->id }}" @selected(old('output_id', $row->output_id ?? '') == $o->id)>{{ $o->kode_output }} - {{ $o->nama_output }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="kode_sub_output" class="block text-sm font-medium text-gray-700">Kode Sub Output *</label>
    <input type="text" name="kode_sub_output" id="kode_sub_output" value="{{ old('kode_sub_output', $row->kode_sub_output ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>
<div>
    <label for="nama_sub_output" class="block text-sm font-medium text-gray-700">Nama Sub Output *</label>
    <input type="text" name="nama_sub_output" id="nama_sub_output" value="{{ old('nama_sub_output', $row->nama_sub_output ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
</div>