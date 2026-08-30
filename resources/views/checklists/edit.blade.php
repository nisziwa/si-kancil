<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Checklist') }} - {{ $checklist->nama_dokumen }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form action="{{ route('checklists.update', $checklist->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama Dokumen</label>
                        <p class="mt-1 text-gray-900 font-semibold">{{ $checklist->nama_dokumen }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                        <select name="status" id="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="Belum Ada" {{ old('status', $checklist->status) == 'Belum Ada' ? 'selected' : '' }}>Belum Ada</option>
                            <option value="Belum Lengkap" {{ old('status', $checklist->status) == 'Belum Lengkap' ? 'selected' : '' }}>Belum Lengkap</option>
                            <option value="Lengkap" {{ old('status', $checklist->status) == 'Lengkap' ? 'selected' : '' }}>Lengkap</option>
                            <option value="Perlu Perbaikan" {{ old('status', $checklist->status) == 'Perlu Perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="catatan" id="catatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('catatan', $checklist->catatan) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('requests.show', $checklist->request_id) }}" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">Batal</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Perubahan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

