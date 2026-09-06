<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tambah '.$config['label']) }}
            </h2>
            <x-ui.btn variant="muted" :href="route('master-pok.index', ['tab' => $tab])">
                ← Kembali
            </x-ui.btn>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-ui.card class="p-6">
                @if($errors->any())
                    <x-ui.alert type="danger" class="mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                <form action="{{ route('master-pok.store', ['tab' => $tab]) }}" method="POST" id="master-pok-form">
                    @csrf
                    <div class="space-y-4">
                        @include('master_pok._form_'.str_replace('-', '_', $tab))
                        <div class="flex items-center gap-2 pt-2">
                            <x-ui.btn variant="primary" type="submit">Simpan {{ $config['label'] }}</x-ui.btn>
                            <x-ui.btn variant="secondary" :href="route('master-pok.index', ['tab' => $tab])">Batal</x-ui.btn>
                        </div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>