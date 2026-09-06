<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Master POK') }}
            </h2>
            <a href="{{ route('master-pok.create', ['tab' => $tab]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow">
                + Tambah {{ $config['label'] }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <div class="flex whitespace-nowrap">
                    @foreach([
                        'program' => 'Program',
                        'kegiatan' => 'Kegiatan',
                        'output' => 'Output',
                        'sub-output' => 'Sub Output',
                        'komponen' => 'Komponen',
                        'akun' => 'Akun',
                        'rincian' => 'Rincian POK',
                    ] as $key => $label)
                        <a href="{{ route('master-pok.index', ['tab' => $key]) }}"
                           class="px-4 py-3 text-sm font-semibold border-b-2 {{ $tab === $key ? 'border-blue-600 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Search, filter & tampil nonaktif -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form action="{{ route('master-pok.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="sm:col-span-1">
                        <label for="search" class="block text-xs font-semibold text-gray-600 uppercase">Cari {{ $config['label'] }}</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>

                    @foreach($config['filters'] as $filter)
                        <div class="sm:col-span-1">
                            <label for="{{ $filter['field'] }}" class="block text-xs font-semibold text-gray-600 uppercase">Filter {{ $filter['label'] }}</label>
                            <select name="{{ $filter['field'] }}" id="{{ $filter['field'] }}" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Semua {{ $filter['label'] }} --</option>
                                @foreach($filterOptions[$filter['field']] as $opt)
                                    <option value="{{ $opt->value }}" @selected(request($filter['field']) == $opt->value)>{{ $opt->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <div class="flex gap-2 items-end sm:col-span-1">
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded text-sm flex-1">
                            Cari
                        </button>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 whitespace-nowrap">
                            <input type="checkbox" name="show_inactive" value="1" @checked(request('show_inactive')) class="rounded border-gray-300">
                            Tampilkan data tidak aktif
                        </label>
                    </div>
                </form>
            </div>

            <!-- Tabel data -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">No</th>
                                @foreach($config['columns'] as $col)
                                    <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">{{ $col['label'] }}</th>
                                @endforeach
                                <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase text-xs">Status</th>
                                <th class="px-6 py-3 text-right font-semibold text-gray-600 uppercase text-xs">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rows as $index => $row)
                                <tr class="hover:bg-gray-50 {{ $row->is_active ? '' : 'opacity-70' }}">
                                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $rows->firstItem() + $index }}</td>
                                    @foreach($config['columns'] as $col)
                                        @if(!empty($col['parent']))
                                            <td class="px-6 py-4 text-gray-700">
                                                {{ optional($row->{$col['key']})->{$col['pk']} }}<span class="text-gray-400"> - </span>{{ optional($row->{$col['key']})->{$col['pn']} }}
                                            </td>
                                        @else
                                            <td class="px-6 py-4 text-gray-700">{{ $row->{$col['key']} }}</td>
                                        @endif
                                    @endforeach
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($row->is_active)
                                            <span class="inline-flex items-center text-xs font-semibold text-green-700 bg-green-50 px-2.5 py-1 rounded border border-green-300">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center text-xs font-semibold text-red-700 bg-red-50 px-2.5 py-1 rounded border border-red-300">Tidak Aktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        <a href="{{ route('master-pok.edit', ['tab' => $tab, 'id' => $row->id]) }}" class="inline-flex items-center text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-1.5 rounded border border-indigo-300 hover:bg-indigo-100">
                                            Edit
                                        </a>
                                        <form action="{{ route('master-pok.toggle', ['tab' => $tab, 'id' => $row->id]) }}" method="POST" class="inline"
                                              onsubmit="return confirm('{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} {{ $config['label'] }} ini?')">
                                            @csrf
                                            @method('PATCH')
                                            @if(request('show_inactive'))
                                                <input type="hidden" name="show_inactive" value="1">
                                            @endif
                                            @if(request('search'))
                                                <input type="hidden" name="search" value="{{ request('search') }}">
                                            @endif
                                            @if($row->is_active)
                                                <button type="submit" class="inline-flex items-center text-xs font-semibold text-orange-700 bg-orange-50 px-2.5 py-1.5 rounded border border-orange-300 hover:bg-orange-100">
                                                    Nonaktifkan
                                                </button>
                                            @else
                                                <button type="submit" class="inline-flex items-center text-xs font-semibold text-green-700 bg-green-50 px-2.5 py-1.5 rounded border border-green-300 hover:bg-green-100">
                                                    Aktifkan kembali
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($config['columns']) + 3 }}" class="px-6 py-8 text-center text-gray-500 italic">
                                        Belum ada data {{ $config['label'] }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rows->hasPages())
                    <div class="p-4 border-t">
                        {{ $rows->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>