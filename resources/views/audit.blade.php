@extends('layouts.app')

@section('content')
@include('vendor.uconfig._internal_navbar')
<div class="container mx-auto py-6">
    <h2 class="text-2xl font-bold mb-6">{{ __('uconfig::uconfig.audit.for_config', ['key' => $config->key]) }}</h2>
    <table class="min-w-full bg-white border border-gray-300">
        <thead>
            <tr class="w-full bg-gray-100">
                <th class="py-2 px-4 border-b">{{ __('uconfig::uconfig.audit.date') }}</th>
                <th class="py-2 px-4 border-b">{{ __('uconfig::uconfig.audit.old_value') }}</th>
                <th class="py-2 px-4 border-b">{{ __('uconfig::uconfig.audit.new_value') }}</th>
                <th class="py-2 px-4 border-b">{{ __('uconfig::uconfig.audit.action') }}</th>
                <th class="py-2 px-4 border-b">{{ __('uconfig::uconfig.audit.user') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audits as $audit)
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-b">{{ $audit->created_at }}</td>
                <td class="py-4 px-6 border-b text-gray-600">{{ $audit->old_value !== null ? $audit->old_value : 'N/A' }}</td>
                <td class="py-4 px-6 border-b text-gray-600">{{ $audit->new_value !== null ? $audit->new_value : 'N/A' }}</td>
                <td class="py-2 px-4 border-b">{{ $audit->action }}</td>
                <td class="py-2 px-4 border-b">{{ $audit->user ? $audit->user->name : __('uconfig::uconfig.audit.unknown_user') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-6">
        <a href="{{ route('uconfig.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">{{ __('uconfig::uconfig.nav.dashboard') }}</a>
    </div>
</div>
@include('vendor.uconfig.footer')
@endsection