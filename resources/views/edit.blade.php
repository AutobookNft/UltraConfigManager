@extends('layouts.app')

@section('content')
@include('vendor.uconfig._internal_navbar')
<div class="container mx-auto py-10">
    <h2 class="text-3xl font-bold mb-6 text-gray-800">{{ __('uconfig::uconfig.pages.edit') }}</h2>
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">{{ __('uconfig::uconfig.error.title') }}</strong>
            <span class="block sm:inline">{{ $errors->first('error') }}</span>
        </div>
    @endif
    <div class="bg-white p-8 rounded-lg shadow-md">
        <form method="POST" action="{{ route('uconfig.update', $config->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="key" class="block text-lg font-semibold text-gray-700 mb-2">{{ __('uconfig::uconfig.form.key') }}</label>
                <input type="text" id="key" name="key" class="form-input mt-1 block w-full bg-gray-200 rounded-md border border-gray-300" value="{{ $config->key }}" readonly>
            </div>

            <div class="mb-6">
                <label for="value" class="block text-lg font-semibold text-gray-700 mb-2">{{ __('uconfig::uconfig.form.value') }}</label>
                <input type="text" id="value" name="value" class="form-input mt-1 block w-full rounded-md border border-gray-300" value="{{ old('value', $config->value) }}">
                @error('value')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6">
                <label for="category" class="block text-lg font-semibold text-gray-700 mb-2">{{ __('uconfig::uconfig.form.category') }}</label>
                <select id="category" name="category" class="form-input mt-1 block w-full rounded-md border border-gray-300">
                    <option value="">{{ __('uconfig::uconfig.form.no_category') }}</option>
                    @foreach(\Ultra\UltraConfigManager\Enums\CategoryEnum::translatedOptions() as $value => $label)
                        <option value="{{ $value }}" {{ old('category', $config->category?->value) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md">{{ __('uconfig::uconfig.actions.update') }}</button>
        </form>
    </div>

    <h3 class="text-2xl font-bold mt-10 mb-6 text-gray-800">{{ __('uconfig::uconfig.audit.title') }}</h3>
    <div class="overflow-hidden rounded-lg shadow-md">
        <table class="min-w-full bg-white border-collapse">
            <thead>
                <tr class="bg-gradient-to-r from-gray-100 to-gray-200 text-left text-sm uppercase tracking-wider text-gray-600">
                    <th class="py-4 px-6 border-b font-semibold">{{ __('uconfig::uconfig.audit.date') }}</th>
                    <th class="py-4 px-6 border-b font-semibold">{{ __('uconfig::uconfig.audit.old_value') }}</th>
                    <th class="py-4 px-6 border-b font-semibold">{{ __('uconfig::uconfig.audit.new_value') }}</th>
                    <th class="py-4 px-6 border-b font-semibold">{{ __('uconfig::uconfig.audit.action') }}</th>
                    <th class="py-4 px-6 border-b font-semibold">{{ __('uconfig::uconfig.audit.user') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($audits as $audit)
                <tr class="hover:bg-gray-50 transition duration-200">
                    <td class="py-4 px-6 border-b text-gray-800">{{ $audit->created_at }}</td>
                    <td class="py-4 px-6 border-b text-gray-600">{{ $audit->old_value }}</td>
                    <td class="py-4 px-6 border-b text-gray-600">{{ $audit->new_value }}</td>
                    <td class="py-4 px-6 border-b text-gray-600">{{ $audit->action }}</td>
                    <td class="py-4 px-6 border-b text-gray-800">{{ $audit->user ? $audit->user->name : __('uconfig::uconfig.audit.unknown_user') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@include('vendor.uconfig.footer')
@endsection