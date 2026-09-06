<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Master SK Rate Perjalanan') }}
            </h2>
            <x-ui.btn variant="primary" :href="route('sk-rates.create')">
                + Tambah SK Rate
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

            <!-- Search -->
            <x-ui.card class="sticky-search p-4">
                <form action="{{ route('sk-rates.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div class="sm:col-span-2">
                        <label for="search" class="block text-xs font-semibold text-gray-600 uppercase">Cari Kecamatan / Ibukota / Keterangan</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Cari kecamatan atau ibukota..." class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded text-sm flex-1">
                            Cari
                        </button>
                        <a href="{{ route('sk-rates.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-3 rounded text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </x-ui.card>

            <!-- List SK Rate -->
            <x-ui.card class="overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="sticky-thead bg-gray-50">
                            <tr>
                                <x-ui.th>No</x-ui.th>
                                <x-ui.th>Kecamatan</x-ui.th>
                                <x-ui.th>Ibukota Kecamatan</x-ui.th>
                                <x-ui.th>Besaran Biaya Transport</x-ui.th>
                                <x-ui.th>Keterangan</x-ui.th>
                                <x-ui.th align="right">Aksi</x-ui.th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($rates as $index => $rate)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $rates->firstItem() + $index }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">{{ $rate->kecamatan }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $rate->ibukota_kecamatan }}</td>
                                    <td class="px-6 py-4 text-gray-800 whitespace-nowrap">Rp {{ number_format($rate->besaran_biaya_transport, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $rate->keterangan ?: '-' }}</td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        <x-ui.btn variant="edit" size="sm" :href="route('sk-rates.edit', $rate->id)">
                                            Edit
                                        </x-ui.btn>
                                        <form action="{{ route('sk-rates.destroy', $rate->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SK Rate ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.btn variant="danger" size="sm" type="submit">
                                                Hapus
                                            </x-ui.btn>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <x-ui.state kind="empty">Belum ada data SK Rate Perjalanan.</x-ui.state>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rates->hasPages())
                    <div class="p-4 border-t">
                        {{ $rates->links() }}
                    </div>
                @endif
            </x-ui.card>

        </div>
    </div>
</x-app-layout>
