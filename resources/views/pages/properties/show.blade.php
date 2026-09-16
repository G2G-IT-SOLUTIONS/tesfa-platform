<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <h1 class="text-3xl font-bold">{{ $property->title }}</h1>
        <p class="text-gray-600">{{ $property->address }}, {{ $property->neighborhood }}</p>
        <p class="text-2xl text-green-700 font-bold mt-4">{{ number_format($property->price) }} {{ $property->price_currency }}</p>
        <div class="grid grid-cols-3 gap-4 mt-6 bg-white p-4 rounded shadow">
            <div><strong>{{ $property->bedrooms }}</strong><br><small>Bedrooms</small></div>
            <div><strong>{{ $property->bathrooms }}</strong><br><small>Bathrooms</small></div>
            <div><strong>{{ $property->area_sqm }} m²</strong><br><small>Area</small></div>
        </div>
        <div class="mt-6 bg-white p-4 rounded shadow">
            <h2 class="font-bold mb-2">Description</h2>
            <p>{{ $property->description ?? 'No description provided.' }}</p>
        </div>
        <div class="mt-4 text-sm text-gray-500">
            Verification status: <span class="font-bold">{{ $property->verification_status }}</span>
        </div>
    </div>
</x-app-layout>