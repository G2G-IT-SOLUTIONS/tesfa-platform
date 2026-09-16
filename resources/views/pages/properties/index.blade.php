<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="flex justify-between mb-6">
            <h1 class="text-3xl font-bold">Properties</h1>
            @auth
                <a href="{{ route('properties.create') }}" class="bg-green-600 text-white px-4 py-2 rounded">+ Add Property</a>
            @endauth
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse ($properties as $property)
                <a href="{{ route('properties.show', $property) }}" class="bg-white rounded-lg shadow hover:shadow-lg p-4 block">
                    <h3 class="font-bold text-lg">{{ $property->title }}</h3>
                    <p class="text-gray-600 text-sm">{{ $property->neighborhood }}, {{ $property->city }}</p>
                    <p class="text-green-700 font-bold mt-2">{{ number_format($property->price) }} {{ $property->price_currency }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $property->bedrooms }} bed · {{ $property->bathrooms }} bath · {{ $property->area_sqm }} m²</p>
                </a>
            @empty
                <p class="text-gray-500">No properties yet. Add one to get started!</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $properties->links() }}</div>
    </div>
</x-app-layout>