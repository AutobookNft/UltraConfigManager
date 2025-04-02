<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'UltraConfig') }}</title>
    
    <!-- Styles (Tailwind CSS or custom) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    @stack('head')
</head>
<body class="bg-gray-100 text-gray-900 font-sans leading-normal tracking-wide min-h-screen">

    <div class="container mx-auto px-4 py-6">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-indigo-600">
                {{ $header ?? 'Ultra Config Manager' }}
            </h1>
        </header>

        <main>
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
