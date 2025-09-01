<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#5A8F45">
    <meta name="description" content="AI-powered weather forecasting assistant with natural language interface">

    <title>{{ config('app.name', 'AI Weather Agent') }}</title>

    <!-- Manifest and PWA support -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @production
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        @endphp
        @if(isset($manifest['resources/css/app.css']['file']))
            <link rel="stylesheet" href="{{ asset('build/'.$manifest['resources/css/app.css']['file']) }}">
        @endif
        @if(isset($manifest['resources/js/app.js']['file']))
            <script src="{{ asset('build/'.$manifest['resources/js/app.js']['file']) }}" defer></script>
        @endif
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endproduction
</head>

<body>
    <div id="app"></div>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(error => {
                        console.log('SW registration failed: ', error);
                    });
            });
        }
    </script>
</body>

</html>