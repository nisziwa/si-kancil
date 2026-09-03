<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Permintaan / FPA') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                    <strong>Catatan:</strong> Untuk kegiatan translok atau kegiatan non perjalanan dinas, Surat Tugas dapat dibuat sebelum FPA memiliki nomor. Nomor FPA dapat diisi kemudian.
                </div>

                <form action="{{ route('requests.store') }}" method="POST" id="fpa-form">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nomor FPA (opsional) -->
                        <div>
                            <label for="nomor_fpa" class="block text-sm font-medium text-gray-700">Nomor FPA <span class="text-gray-400">(opsional)</span></label>
                            <input type="text" name="nomor_fpa" id="nomor_fpa" value="{{ old('nomor_fpa', request('nomor_fpa')) }}" placeholder="Kosongkan jika belum ada" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p id="nomor-fpa-feedback" class="hidden mt-1 text-sm font-semibold"></p>
                        </div>

                        <!-- Jenis Pengeluaran -->
                        <div>
                            <label for="jenis_pengeluaran_id" class="block text-sm font-medium text-gray-700">Jenis Pengeluaran *</label>
                            <select name="jenis_pengeluaran_id" id="jenis_pengeluaran_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">-- Pilih Jenis --</option>
                                @foreach($expenseTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('jenis_pengeluaran_id', request('jenis_pengeluaran_id')) == $type->id ? 'selected' : '' }}>
                                        {{ $type->nama }} ({{ $type->kode }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Deskripsi -->
                        <div class="md:col-span-2">
                            <label for="deskripsi_permintaan" class="block text-sm font-medium text-gray-700">Deskripsi Permintaan *</label>
                            <textarea name="deskripsi_permintaan" id="deskripsi_permintaan" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('deskripsi_permintaan') }}</textarea>
                        </div>

                        <!-- Periode (Pilihan Tombol) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Periode Kegiatan *</label>
                            <div id="periode-buttons" class="mt-2 flex flex-wrap gap-2">
                                @foreach(\App\Models\Request::PERIOD_LIST as $period)
                                    <button type="button" data-value="{{ $period }}"
                                        class="periode-btn px-4 py-2 rounded-md border text-sm font-medium
                                        {{ old('periode') === $period ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200' }}">
                                        {{ $period }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="periode" id="periode" value="{{ old('periode', \App\Models\Request::PERIOD_LIST[0]) }}">
                            @error('periode') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', request('tanggal_mulai')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Gunakan date picker. Akan ditampilkan dalam format Indonesia, contoh: 02 September 2026.</p>
                        </div>

                        <!-- Tanggal Selesai -->
                        <div>
                            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', request('tanggal_selesai')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Gunakan date picker. Akan ditampilkan dalam format Indonesia, contoh: 02 September 2026.</p>
                        </div>

                        <!-- Deadline SPJ (auto = akhir kegiatan + 3 hari, tetap bisa diubah) -->
                        <div>
                            <label for="deadline_spj" class="block text-sm font-medium text-gray-700">Deadline SPJ</label>
                            <input type="date" name="deadline_spj" id="deadline_spj" value="{{ old('deadline_spj', request('deadline_spj')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Gunakan date picker. Otomatis = tanggal akhir kegiatan + 3 hari. Akan ditampilkan dalam format Indonesia, contoh: 05 September 2026.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('requests.index') }}" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">Batal</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan FPA</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tombol pilihan periode
            const buttons = document.querySelectorAll('.periode-btn');
            const periodeInput = document.getElementById('periode');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(b => {
                        b.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                        b.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-300');
                    });
                    btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
                    btn.classList.remove('bg-gray-100', 'text-gray-700', 'border-gray-300');
                    periodeInput.value = btn.dataset.value;
                });
            });

            // Auto deadline = tanggal selesai + 3 hari
            const endInput = document.getElementById('tanggal_selesai');
            const deadlineInput = document.getElementById('deadline_spj');
            endInput.addEventListener('change', function () {
                if (!deadlineInput.value || deadlineInput.dataset.manual !== '1') {
                    if (endInput.value) {
                        const d = new Date(endInput.value);
                        d.setDate(d.getDate() + 3);
                        deadlineInput.value = d.toISOString().split('T')[0];
                    }
                }
            });
            deadlineInput.addEventListener('change', function () {
                deadlineInput.dataset.manual = '1';
            });

            // Pengecekan nomor FPA secara langsung
            const fpaInput = document.getElementById('nomor_fpa');
            const feedback = document.getElementById('nomor-fpa-feedback');
            let timer;

            fpaInput.addEventListener('input', function () {
                clearTimeout(timer);
                const value = fpaInput.value.trim();
                if (value === '') {
                    feedback.classList.add('hidden');
                    return;
                }
                timer = setTimeout(function () {
                    fetch('{{ route("requests.check-nomor-fpa") }}?nomor=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        feedback.classList.remove('hidden');
                        if (data.available) {
                            feedback.textContent = '';
                            feedback.className = 'hidden mt-1 text-sm font-semibold';
                        } else {
                            feedback.textContent = data.message;
                            feedback.className = 'mt-1 text-sm font-semibold text-red-600';
                        }
                    })
                    .catch(err => console.error(err));
                }, 300);
            });

            // Validasi submit: nomor FPA tidak boleh duplikat
            document.getElementById('fpa-form').addEventListener('submit', function (e) {
                const value = fpaInput.value.trim();
                if (value === '') return;
                e.preventDefault();
                fetch('{{ route("requests.check-nomor-fpa") }}?nomor=' + encodeURIComponent(value), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.available) {
                        feedback.textContent = data.message;
                        feedback.className = 'mt-1 text-sm font-semibold text-red-600';
                        return;
                    }
                    e.target.submit();
                })
                .catch(() => e.target.submit());
            });
        });
    </script>
</x-app-layout>
