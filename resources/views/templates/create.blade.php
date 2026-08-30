<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload Template Dokumen Baru') }}
            </h2>
            <a href="{{ route('templates.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Ada kesalahan input:</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('templates.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="nama_template" class="block text-sm font-medium text-gray-700">Nama Template *</label>
                            <input type="text" name="nama_template" id="nama_template" value="{{ old('nama_template') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: Template Format KAK Honor 2026">
                        </div>

                        <div>
                            <label for="kategori" class="block text-sm font-medium text-gray-700">Kategori Dokumen *</label>
                            <select name="kategori" id="kategori" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($kategoriList as $kat)
                                    <option value="{{ $kat }}" {{ old('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="versi" class="block text-sm font-medium text-gray-700">Versi Dokumen</label>
                            <input type="text" name="versi" id="versi" value="{{ old('versi', 'v1.0') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="Contoh: v1.0 / 2026">
                        </div>

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">File Template Dokumen *</label>
                            <input type="file" name="file" id="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-1">Maksimal 20MB. Format: PDF, DOCX, XLSX, PPTX, ZIP</p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="status_aktif" id="status_aktif" value="1" {{ old('status_aktif', true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="status_aktif" class="ml-2 text-sm text-gray-700 font-medium">Status Aktif (dapat digunakan)</label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <a href="{{ route('templates.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                            Batal
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded text-sm">
                            Upload Template
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

