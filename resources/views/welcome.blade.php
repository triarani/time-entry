<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                @import "tailwindcss";
            </style>
        @endif
    </head>
    <body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4">
        <!-- Centered app container with max width 980px -->
        <div id="app" class="w-full"></div>
    </body>
</html>
