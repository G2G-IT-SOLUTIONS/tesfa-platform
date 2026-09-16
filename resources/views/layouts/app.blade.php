<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#078930">
    <meta name="description" content="@yield('meta_description', 'Find, verify, and buy properties in Ethiopia with confidence.')">

    @hasSection('property_id')
        <meta name="property-id" content="@yield('property_id')">
    @endif

    <title>@yield('title', 'Tesfa — Verified Properties in Ethiopia')</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#F5F0E8] min-h-screen pb-20 md:pb-0 antialiased text-gray-800">

    @include('partials.header')

    <main class="min-h-[60vh]">
        @if (session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.mobile-nav')

    <!-- @include('components.chatbot-widget') -->

    {{-- Toast notifications --}}
    <div x-data="{ toasts: [] }"
         @toast.window="toasts.push({ id: Date.now(), ...$event.detail }); setTimeout(() => toasts.shift(), 3500)"
         class="fixed top-20 right-4 z-[60] space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div :class="toast.type === 'success' ? 'bg-[#078930]' : (toast.type === 'error' ? 'bg-red-600' : 'bg-gray-800')"
                 class="text-white px-4 py-3 rounded-lg shadow-lg text-sm max-w-xs animate-slide-up pointer-events-auto">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @stack('scripts')
</body>
</html>
