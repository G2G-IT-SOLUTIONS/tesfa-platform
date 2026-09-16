<footer class="bg-white border-t mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="h-7 w-7 text-[#078930]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3 2 11h3v9h6v-6h2v6h6v-9h3z"/>
                    </svg>
                    <span class="text-lg font-bold text-[#078930]">Tesfa</span>
                </div>
                <p class="text-sm text-gray-500">
                    Ethiopia's verified property marketplace. Buy, rent, or rent-to-own with trust.
                </p>
            </div>

            <div>
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">Explore</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('properties.index') }}" class="hover:text-[#078930]">Browse Properties</a></li>
                    <li><a href="{{ route('market.index') }}" class="hover:text-[#078930]">Market Intelligence</a></li>
                    <li><a href="{{ route('about') }}" class="hover:text-[#078930]">About Us</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">Company</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('about') }}" class="hover:text-[#078930]">About</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-[#078930]">Contact</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-[#078930]">Privacy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-[#078930]">Terms</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">Contact</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li>Addis Ababa, Ethiopia</li>
                    <li><a href="mailto:hello@tesfa.et" class="hover:text-[#078930]">hello@tesfa.et</a></li>
                    <li>+251 911 000 000</li>
                </ul>
            </div>
        </div>

        <div class="border-t mt-8 pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-400">
            <p>© {{ date('Y') }} Tesfa Platform. All rights reserved.</p>
            <p>Built with ❤️ in Addis Ababa</p>
        </div>
    </div>
</footer>
