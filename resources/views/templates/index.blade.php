<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Repository Template Dokumen') }}
            </h2>
            <a href="{{ route('templates.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                + Upload Template Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Filter Kategori & Search -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form action="{{ route('templates.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="kategori" class="block text-xs font-semibold text-gray-600 uppercase">Kategori Dokumen</label>
                        <select name="kategori" id="kategori" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Kategori</option>
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat }}" {{ request('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="search" class="block text-xs font-semibold text-gray-600 uppercase">Nama Template</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Cari nama template..." class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded text-sm flex-1">
                            Cari
                        </button>
                        <a href="{{ route('templates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- List Template -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">No</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Nama Template</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Kategori</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Versi</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Status</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-600 uppercase text-xs">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($templates as $index => $tpl)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $templates->firstItem() + $index }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        {{ $tpl->nama_template }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            {{ $tpl->kategori }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $tpl->versi ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($tpl->status_aktif)
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Non-Aktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        @if($tpl->file)
                                            <a href="{{ route('templates.download', $tpl->id) }}" class="inline-flex items-center text-xs font-semibold text-green-700 bg-green-50 px-2.5 py-1.5 rounded border border-green-300 hover:bg-green-100">
                                                ⬇ Download
                                            </a>
                                        @endif
                                        <a href="{{ route('templates.edit', $tpl->id) }}" class="inline-flex items-center text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-1.5 rounded border border-indigo-300 hover:bg-indigo-100">
                                            Edit
                                        </a>
                                        <form action="{{ route('templates.destroy', $tpl->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus template ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center text-xs font-semibold text-red-700 bg-red-50 px-2.5 py-1.5 rounded border border-red-300 hover:bg-red-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                        Belum ada template dokumen yang tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($templates->hasPages())
                    <div class="p-4 border-t">
                        {{ $templates->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
