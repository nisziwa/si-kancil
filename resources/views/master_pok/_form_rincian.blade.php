<div>
    <label for="cascade-program" class="block text-sm font-medium text-gray-700">Program *</label>
    <select name="program_id" id="cascade-program" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Program --</option>
        @foreach($parents['program'] as $p)
            <option value="{{ $p->id }}" @selected(old('program_id', $row->program_id ?? '') == $p->id)>{{ $p->kode_program }} - {{ $p->nama_program }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="cascade-kegiatan" class="block text-sm font-medium text-gray-700">Kegiatan *</label>
    <select name="kegiatan_id" id="cascade-kegiatan" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Kegiatan --</option>
        @foreach($parents['kegiatan'] as $k)
            <option value="{{ $k->id }}" data-parent="{{ $k->program_id }}" @selected(old('kegiatan_id', $row->kegiatan_id ?? '') == $k->id)>{{ $k->kode_kegiatan }} - {{ $k->nama_kegiatan }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="cascade-output" class="block text-sm font-medium text-gray-700">Output *</label>
    <select name="output_id" id="cascade-output" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Output --</option>
        @foreach($parents['output'] as $o)
            <option value="{{ $o->id }}" data-parent="{{ $o->kegiatan_id }}" @selected(old('output_id', $row->output_id ?? '') == $o->id)>{{ $o->kode_output }} - {{ $o->nama_output }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="cascade-subOutput" class="block text-sm font-medium text-gray-700">Sub Output *</label>
    <select name="sub_output_id" id="cascade-subOutput" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Sub Output --</option>
        @foreach($parents['subOutput'] as $so)
            <option value="{{ $so->id }}" data-parent="{{ $so->output_id }}" @selected(old('sub_output_id', $row->sub_output_id ?? '') == $so->id)>{{ $so->kode_sub_output }} - {{ $so->nama_sub_output }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="cascade-komponen" class="block text-sm font-medium text-gray-700">Komponen *</label>
    <select name="komponen_id" id="cascade-komponen" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Komponen --</option>
        @foreach($parents['komponen'] as $c)
            <option value="{{ $c->id }}" data-parent="{{ $c->sub_output_id }}" @selected(old('komponen_id', $row->komponen_id ?? '') == $c->id)>{{ $c->kode_komponen }} - {{ $c->nama_komponen }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="cascade-akun" class="block text-sm font-medium text-gray-700">Akun *</label>
    <select name="akun_id" id="cascade-akun" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
        <option value="">-- Pilih Akun --</option>
        @foreach($parents['akun'] as $a)
            <option value="{{ $a->id }}" @selected(old('akun_id', $row->akun_id ?? '') == $a->id)>{{ $a->kode_akun }} - {{ $a->nama_akun }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="rincian" class="block text-sm font-medium text-gray-700">Rincian POK *</label>
    <textarea name="rincian" id="rincian" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('rincian', $row->rincian ?? '') }}</textarea>
    <p class="mt-1 text-xs text-gray-500">Duplikasi tidak diperbolehkan pada kombinasi Program/Kegiatan/Output/Sub Output/Komponen/Akun yang sama.</p>
</div>

<script>
(function () {
    const selects = {
        program: document.getElementById('cascade-program'),
        kegiatan: document.getElementById('cascade-kegiatan'),
        output: document.getElementById('cascade-output'),
        subOutput: document.getElementById('cascade-subOutput'),
        komponen: document.getElementById('cascade-komponen'),
        akun: document.getElementById('cascade-akun'),
    };
    const tierOrder = ['kegiatan', 'output', 'subOutput', 'komponen'];
    const parentOf = { kegiatan: 'program', output: 'kegiatan', subOutput: 'output', komponen: 'subOutput' };

    function refresh(tier) {
        const parentValue = selects[parentOf[tier]].value;
        [...selects[tier].options].forEach((opt) => {
            opt.hidden = !(opt.value === '' || parentValue === '' || opt.dataset.parent === parentValue);
        });
        const selected = selects[tier].selectedOptions[0];
        if (selected && selected.value !== '' && selected.hidden) {
            selects[tier].value = '';
        }
    }

    function resetBelow(tier) {
        const i = tierOrder.indexOf(tier);
        if (i === -1) return;
        for (let j = i + 1; j < tierOrder.length; j++) {
            selects[tierOrder[j]].value = '';
            refresh(tierOrder[j]);
        }
    }

    tierOrder.forEach((tier) => {
        refresh(tier);
        selects[tier].addEventListener('change', () => {
            refresh(tier);
            resetBelow(tier);
        });
    });
})();
</script>