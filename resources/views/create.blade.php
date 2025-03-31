@extends('layouts.app')

@section('content')
@include('vendor.uconfig._internal_navbar')
<div class="container mx-auto py-10">
    <h2 class="text-3xl font-bold mb-6 text-gray-800">Crea Nuova Configurazione</h2>
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Errore!</strong>
            <span class="block sm:inline">{{ $errors->first('error') }}</span>
        </div>
    @endif
    <div class="bg-white p-8 rounded-lg shadow-md">
        <form method="POST" action="{{ route('uconfig.store') }}">
            @csrf

            <div class="mb-6">
                <label for="key" class="block text-lg font-semibold text-gray-700 mb-2">Chiave</label>
                <input type="text" id="key" name="key" class="form-input mt-1 block w-full rounded-md border border-gray-300" value="{{ old('key') }}" required>
                @error('key')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6">
                <label for="value" class="block text-lg font-semibold text-gray-700 mb-2">Valore</label>
                <input type="text" id="value" name="value" class="form-input mt-1 block w-full rounded-md border border-gray-300" value="{{ old('value') }}" required>
                @error('value')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6">
                <label for="category" class="block text-lg font-semibold text-gray-700 mb-2">Categoria</label>
                <select id="category" name="category" class="form-input mt-1 block w-full rounded-md border border-gray-300">
                    <option value="">Nessuna categoria</option>
                    @foreach(\Ultra\UltraConfigManager\Enums\CategoryEnum::cases() as $category)
                        @if($category !== \Ultra\UltraConfigManager\Enums\CategoryEnum::None)
                            <option value="{{ $category->value }}" {{ old('category') === $category->value ? 'selected' : '' }}>
                                {{ ucfirst($category->value) }}
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('category')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md">Crea</button>
        </form>
    </div>
</div>
@include('vendor.uconfig.footer')
@endsection