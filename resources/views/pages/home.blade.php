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