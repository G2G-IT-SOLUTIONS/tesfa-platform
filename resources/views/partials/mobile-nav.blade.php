<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t z-40 shadow-[0_-2px_10px_rgba(0,0,0,0.04)]">
    <div class="grid grid-cols-4">
        <a href="{{ url('/') }}"
           class="flex flex-col items-center py-2 {{ request()->is('/') ? 'text-[#078930]' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] mt-0.5">Home</span>
        </a>

        <a href="{{ route('properties.index') }}"
           class="flex flex-col items-center py-2 {{ request()->is('properties*') ? 'text-[#078930]' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="text-[10px] mt-0.5">Search</span>
        </a>

        <a href="{{ auth()->check() ? route('bookings.index') : route('login') }}"
           class="flex flex-col items-center py-2 {{ request()->is('bookings*') ? 'text-[#078930]' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span class="text-[10px] mt-0.5">Bookings</span>
        </a>

        <a href="{{ auth()->check() ? route('profile') : route('login') }}"
           class="flex flex-col items-center py-2 {{ request()->is('profile*') ? 'text-[#078930]' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-[10px] mt-0.5">Profile</span>
        </a>
    </div>
</nav>

<style>
    [x-cloak] { display: none !important; }
</style>
