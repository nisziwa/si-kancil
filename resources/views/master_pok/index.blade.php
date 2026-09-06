<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Master POK') }}
            </h2>
            <x-ui.btn variant="primary" :href="route('master-pok.create', ['tab' => $tab])">
                + Tambah {{ $config['label'] }}
            </x-ui.btn>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            @endif
            @if(session('error'))
                <x-ui.alert type="danger">{{ session('error') }}</x-ui.alert>
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
            <x-ui.card class="sticky-search p-4">
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
            </x-ui.card>

            <!-- Tabel data -->
            <x-ui.card class="overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="sticky-thead bg-gray-50">
                            <tr>
                                <x-ui.th>No</x-ui.th>
                                @foreach($config['columns'] as $col)
                                    <x-ui.th>{{ $col['label'] }}</x-ui.th>
                                @endforeach
                                <x-ui.th>Status</x-ui.th>
                                <x-ui.th align="right">Aksi</x-ui.th>
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
                                            <x-ui.badge variant="success">Aktif</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger">Tidak Aktif</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        <x-ui.btn variant="edit" size="sm" :href="route('master-pok.edit', ['tab' => $tab, 'id' => $row->id])">
                                            Edit
                                        </x-ui.btn>
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
                                                <x-ui.btn variant="warn" size="sm" type="submit">Nonaktifkan</x-ui.btn>
                                            @else
                                                <x-ui.btn variant="success" size="sm" type="submit">Aktifkan kembali</x-ui.btn>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($config['columns']) + 3 }}">
                                        <x-ui.state kind="empty">{{ 'Belum ada data '.$config['label'].'.' }}</x-ui.state>
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
            </x-ui.card>

        </div>
    </div>
</x-app-layout>