<x-app-layout>
    <div class="max-w-2xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold mb-6">Add Property</h1>
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul>@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('properties.store') }}" class="bg-white p-6 rounded shadow space-y-4">
            @csrf
            <input name="title" placeholder="Title" class="w-full border p-2 rounded" required>
            <textarea name="description" placeholder="Description" class="w-full border p-2 rounded"></textarea>
            <select name="type" class="w-full border p-2 rounded" required>
                <option value="apartment">Apartment</option>
                <option value="villa">Villa</option>
                <option value="townhouse">Townhouse</option>
                <option value="land">Land</option>
            </select>
            <select name="purpose" class="w-full border p-2 rounded" required>
                <option value="sale">For Sale</option>
                <option value="rent">For Rent</option>
                <option value="short_term">Short Term</option>
                <option value="rent_to_own">Rent to Own</option>
            </select>
            <input name="price" type="number" placeholder="Price (ETB)" class="w-full border p-2 rounded" required>
            <input name="bedrooms" type="number" placeholder="Bedrooms" class="w-full border p-2 rounded">
            <input name="bathrooms" type="number" placeholder="Bathrooms" class="w-full border p-2 rounded">
            <input name="area_sqm" type="number" step="0.01" placeholder="Area (m²)" class="w-full border p-2 rounded">
            <input name="address" placeholder="Address" class="w-full border p-2 rounded" required>
            <input name="neighborhood" placeholder="Neighborhood" class="w-full border p-2 rounded" required>
            <button class="bg-green-600 text-white px-4 py-2 rounded">Save</button>
        </form>
    </div>
</x-app-layout>