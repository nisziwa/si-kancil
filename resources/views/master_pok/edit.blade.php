<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit '.$config['label']) }}
            </h2>
            <a href="{{ route('master-pok.index', ['tab' => $tab]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <strong class="font-bold">Ada kesalahan!</strong>
                        <ul class="list-disc pl-5 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('master-pok.update', ['tab' => $tab, 'id' => $row->id]) }}" method="POST" id="master-pok-form">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        @include('master_pok._form_'.str_replace('-', '_', $tab))
                        <div class="flex items-center gap-2 pt-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Simpan Perubahan
                            </button>
                            <a href="{{ route('master-pok.index', ['tab' => $tab]) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded text-sm">
                                Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>