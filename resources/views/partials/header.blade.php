<header class="bg-white shadow-sm sticky top-0 z-40" x-data="{ mobileMenu: false, userMenu: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- Logo --}}
            <div class="flex items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <svg class="h-8 w-8 text-[#078930]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3 2 11h3v9h6v-6h2v6h6v-9h3z"/>
                    </svg>
                    <span class="text-xl font-bold text-[#078930]">Tesfa</span>
                </a>
            </div>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center space-x-6">
                <a href="{{ route('properties.index') }}" class="text-gray-600 hover:text-[#078930] transition-colors">Properties</a>
                <a href="{{ route('market.index') }}" class="text-gray-600 hover:text-[#078930] transition-colors">Market Intel</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-[#078930] transition-colors">Dashboard</a>
                @endauth
            </nav>

            {{-- Right side --}}
            <div class="flex items-center space-x-3">
                @guest
                    <a href="{{ route('login') }}" class="hidden sm:inline-block text-gray-600 hover:text-[#078930]">Login</a>
                    <a href="{{ route('register') }}" class="bg-[#078930] text-white px-4 py-2 rounded-lg hover:bg-[#067a28] transition-colors text-sm font-medium">Register</a>
                @else
                    <div class="relative">
                        <button @click="userMenu = !userMenu" class="flex items-center space-x-2 focus:outline-none">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                                 class="h-9 w-9 rounded-full object-cover border border-gray-200">
                            <span class="hidden md:block text-sm font-medium">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-gray-500 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="userMenu" x-cloak @click.outside="userMenu = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-1 ring-1 ring-black/5 z-50">
                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Dashboard</a>
                            <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                            <a href="{{ route('my-properties') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">My Properties</a>
                            <a href="{{ route('credit-score') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Credit Score</a>
                            <a href="{{ route('settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Settings</a>
                            @if(auth()->user()->is_admin)
                                <a href="/admin" class="block px-4 py-2 text-sm text-[#078930] font-medium hover:bg-gray-50">Admin Panel</a>
                            @endif
                            <div class="border-t mt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</header>
