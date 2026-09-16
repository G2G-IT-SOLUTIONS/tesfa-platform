<?php
/**
 * Generate minimal Blade views for Tesfa.
 */

$base = __DIR__ . '/resources/views';
@mkdir("$base/layouts", 0755, true);
@mkdir("$base/pages", 0755, true);
@mkdir("$base/pages/properties", 0755, true);
@mkdir("$base/pages/escrow", 0755, true);
@mkdir("$base/pages/rent-to-own", 0755, true);
@mkdir("$base/pages/short-term", 0755, true);
@mkdir("$base/pages/agent", 0755, true);
@mkdir("$base/pages/market-intel", 0755, true);
@mkdir("$base/pages/notifications", 0755, true);
@mkdir("$base/pages/legal", 0755, true);
@mkdir("$base/pages/auth", 0755, true);
@mkdir("$base/components", 0755, true);

$views = [

'layouts/app.blade.php' => <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tesfa — Verified Property Platform')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-green-700">Tesfa</a>
            <nav class="space-x-4">
                <a href="/properties" class="text-gray-700 hover:text-green-700">Properties</a>
                <a href="/market-intelligence" class="text-gray-700 hover:text-green-700">Market</a>
                @auth
                    <a href="/dashboard" class="text-gray-700 hover:text-green-700">Dashboard</a>
                @else
                    <a href="/login" class="text-gray-700 hover:text-green-700">Login</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 text-red-800 p-4 rounded mb-4">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 py-6 text-sm text-gray-500 text-center">
            &copy; {{ date('Y') }} Tesfa Platform. All rights reserved.
        </div>
    </footer>
</body>
</html>
BLADE,

'pages/home.blade.php' => <<<'BLADE'
@extends('layouts.app')

@section('title', 'Tesfa — Verified Property Platform')

@section('content')
<div class="space-y-8">
    <section class="text-center py-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Find Verified Properties in Ethiopia</h1>
        <p class="text-lg text-gray-600 mb-6">Buy, rent, or rent-to-own with escrow protection and certified agent verification.</p>
        <a href="/properties" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
            Browse Properties
        </a>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-2xl font-bold text-green-700">{{ $stats['verified_properties'] }}</div>
            <div class="text-sm text-gray-500">Verified Properties</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-2xl font-bold text-green-700">{{ $stats['certified_agents'] }}</div>
            <div class="text-sm text-gray-500">Certified Agents</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-2xl font-bold text-green-700">{{ $stats['transactions'] }}</div>
            <div class="text-sm text-gray-500">Transactions</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-2xl font-bold text-green-700">{{ $stats['avm_accuracy'] }}%</div>
            <div class="text-sm text-gray-500">AVM Accuracy</div>
        </div>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-4">Featured Properties</h2>
        @if ($featuredProperties->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($featuredProperties as $property)
                    <div class="bg-white rounded shadow overflow-hidden">
                        <div class="p-4">
                            <h3 class="font-semibold text-lg">
                                <a href="{{ url('/properties/' . $property->slug) }}" class="text-gray-900 hover:text-green-700">
                                    {{ $property->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500">{{ $property->neighborhood }}, {{ $property->city }}</p>
                            <p class="text-green-700 font-bold mt-2">{{ number_format($property->price) }} {{ $property->price_currency }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">No featured properties yet. Check back soon.</p>
        @endif
    </section>

    @if (!empty($marketHighlights))
        <section>
            <h2 class="text-2xl font-semibold mb-4">Market Highlights</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach ($marketHighlights as $m)
                    <div class="bg-white p-4 rounded shadow">
                        <div class="font-semibold">{{ $m['neighborhood'] }}</div>
                        <div class="text-sm text-gray-500">Avg: {{ number_format($m['avg_price_per_sqm']) }} ETB/sqm</div>
                        <div class="text-sm {{ $m['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $m['change'] >= 0 ? '+' : '' }}{{ $m['change'] }}%
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
BLADE,

'pages/about.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'About — Tesfa')
@section('content')
<div class="prose max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">About Tesfa</h1>
    <p>Tesfa is Ethiopia's verified property platform. Every listing is checked by certified agents, and every transaction is protected by escrow.</p>
</div>
@endsection
BLADE,

'pages/contact.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Contact — Tesfa')
@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Contact Us</h1>
    <p class="text-gray-600">Email us at support@tesfa.et — we respond within 24 hours.</p>
</div>
@endsection
BLADE,

'pages/privacy.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Privacy Policy — Tesfa')
@section('content')
<div class="prose max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Privacy Policy</h1>
    <p>We take your privacy seriously. This page describes how we handle your data.</p>
</div>
@endsection
BLADE,

'pages/terms.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Terms — Tesfa')
@section('content')
<div class="prose max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Terms of Service</h1>
    <p>By using Tesfa you agree to our terms of service.</p>
</div>
@endsection
BLADE,

'pages/properties/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Properties — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Properties</h1>
@if ($properties->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($properties as $property)
            <div class="bg-white rounded shadow p-4">
                <h3 class="font-semibold">
                    <a href="{{ url('/properties/' . $property->slug) }}" class="hover:text-green-700">{{ $property->title }}</a>
                </h3>
                <p class="text-sm text-gray-500">{{ $property->neighborhood }}, {{ $property->city }}</p>
                <p class="text-green-700 font-bold mt-2">{{ number_format($property->price) }} {{ $property->price_currency }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $properties->links() }}</div>
@else
    <p class="text-gray-500">No properties found.</p>
@endif
@endsection
BLADE,

'pages/properties/show.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', $property->title . ' — Tesfa')
@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">{{ $property->title }}</h1>
    <p class="text-gray-500 mb-6">{{ $property->address }}, {{ $property->neighborhood }}, {{ $property->city }}</p>
    <div class="bg-white rounded shadow p-6 space-y-4">
        <div class="text-2xl font-bold text-green-700">{{ number_format($property->price) }} {{ $property->price_currency }}</div>
        <div class="grid grid-cols-3 gap-4">
            <div><span class="text-gray-500">Type:</span> {{ ucfirst($property->type) }}</div>
            <div><span class="text-gray-500">Bedrooms:</span> {{ $property->bedrooms ?? '—' }}</div>
            <div><span class="text-gray-500">Bathrooms:</span> {{ $property->bathrooms ?? '—' }}</div>
        </div>
        <p>{{ $property->description }}</p>
    </div>
</div>
@endsection
BLADE,

'pages/properties/create.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'List a Property — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">List a Property</h1>
<p class="text-gray-500">Property submission form goes here.</p>
@endsection
BLADE,

'pages/properties/edit.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Edit Property — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Edit Property</h1>
<p class="text-gray-500">Edit form goes here.</p>
@endsection
BLADE,

'pages/properties/my-properties.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'My Properties — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">My Properties</h1>
<p class="text-gray-500">Your listings will appear here.</p>
@endsection
BLADE,

'pages/dashboard.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Dashboard — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="/my-properties" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">My Properties</div>
        <div class="text-gray-500 text-sm">Manage your listings</div>
    </a>
    <a href="/escrow" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">Escrow</div>
        <div class="text-gray-500 text-sm">Active transactions</div>
    </a>
    <a href="/credit-score" class="bg-white p-6 rounded shadow hover:shadow-md">
        <div class="font-semibold text-lg">Credit Score</div>
        <div class="text-gray-500 text-sm">View your score</div>
    </a>
</div>
@endsection
BLADE,

'pages/credit-score.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Credit Score — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Your Credit Score</h1>
<p class="text-gray-500">Credit score details appear here.</p>
@endsection
BLADE,

'pages/profile.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Profile — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Your Profile</h1>
<p class="text-gray-500">Profile details go here.</p>
@endsection
BLADE,

'pages/settings.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Settings — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Settings</h1>
<p class="text-gray-500">Account settings go here.</p>
@endsection
BLADE,

'pages/escrow/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Escrow — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Escrow Transactions</h1>
<p class="text-gray-500">Your escrow transactions will appear here.</p>
@endsection
BLADE,

'pages/escrow/create.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'New Escrow — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">New Escrow</h1>
<p class="text-gray-500">Escrow creation form goes here.</p>
@endsection
BLADE,

'pages/escrow/show.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Escrow — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Escrow Details</h1>
<p class="text-gray-500">Escrow details go here.</p>
@endsection
BLADE,

'pages/rent-to-own/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Rent-to-Own — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Rent-to-Own Contracts</h1>
<p class="text-gray-500">Your contracts will appear here.</p>
@endsection
BLADE,

'pages/rent-to-own/create.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Start Rent-to-Own — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Start Rent-to-Own</h1>
<p class="text-gray-500">Application form goes here.</p>
@endsection
BLADE,

'pages/rent-to-own/show.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Rent-to-Own Contract — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Contract Details</h1>
<p class="text-gray-500">Contract details go here.</p>
@endsection
BLADE,

'pages/short-term/booking.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Book a Stay — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Book Your Stay</h1>
<p class="text-gray-500">Booking form goes here.</p>
@endsection
BLADE,

'pages/short-term/my-bookings.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'My Bookings — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">My Bookings</h1>
<p class="text-gray-500">Your bookings will appear here.</p>
@endsection
BLADE,

'pages/agent/dashboard.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Agent Dashboard — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Agent Dashboard</h1>
<p class="text-gray-500">Your verifications and earnings go here.</p>
@endsection
BLADE,

'pages/agent/jobs.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Agent Jobs — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Available Jobs</h1>
<p class="text-gray-500">Job listings go here.</p>
@endsection
BLADE,

'pages/agent/earnings.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Agent Earnings — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Your Earnings</h1>
<p class="text-gray-500">Earnings breakdown goes here.</p>
@endsection
BLADE,

'pages/agent/profile.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Agent Profile — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Agent Profile</h1>
<p class="text-gray-500">Your agent profile goes here.</p>
@endsection
BLADE,

'pages/market-intel/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Market Intelligence — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Market Intelligence</h1>
<p class="text-gray-500">Neighborhood trends and analytics go here.</p>
@endsection
BLADE,

'pages/market-intel/neighborhood.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Neighborhood — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">{{ $neighborhood }}</h1>
<p class="text-gray-500">Neighborhood data goes here.</p>
@endsection
BLADE,

'pages/notifications/index.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Notifications — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Notifications</h1>
<p class="text-gray-500">Your notifications appear here.</p>
@endsection
BLADE,

'pages/legal/consent.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Consent — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Consent</h1>
<p class="text-gray-500">Your consent preferences go here.</p>
@endsection
BLADE,

'pages/legal/data-request.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Data Request — Tesfa')
@section('content')
<h1 class="text-3xl font-bold mb-6">Data Subject Request</h1>
<p class="text-gray-500">Submit a data access, correction, or deletion request here.</p>
@endsection
BLADE,

'auth/login.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Login — Tesfa')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <form method="POST" action="/login" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full border rounded p-2" required>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">Sign In</button>
    </form>
</div>
@endsection
BLADE,

'auth/register.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Register — Tesfa')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Register</h1>
    <p class="text-gray-500">Registration form goes here.</p>
</div>
@endsection
BLADE,

'auth/forgot-password.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Forgot Password — Tesfa')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Reset Password</h1>
    <p class="text-gray-500">Password reset form goes here.</p>
</div>
@endsection
BLADE,

'auth/reset-password.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Reset Password — Tesfa')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Set New Password</h1>
    <p class="text-gray-500">New password form goes here.</p>
</div>
@endsection
BLADE,

'auth/verify-otp.blade.php' => <<<'BLADE'
@extends('layouts.app')
@section('title', 'Verify OTP — Tesfa')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Verify Your Phone</h1>
    <p class="text-gray-500">OTP verification form goes here.</p>
</div>
@endsection
BLADE,

];

$created = 0;
foreach ($views as $path => $content) {
    $file = "$base/$path";
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (file_exists($file) && filesize($file) > 200) {
        echo "SKIP: $path\n";
        continue;
    }
    file_put_contents($file, $content);
    echo "WROTE: $path\n";
    $created++;
}

echo "\nDone. Created $created views.\n";
