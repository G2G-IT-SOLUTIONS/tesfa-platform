<div x-data="chatbot()" x-init="init()" class="fixed bottom-24 md:bottom-6 right-4 md:right-6 z-50">
    <!-- Chat toggle button -->
    <button 
        @click="open = !open" 
        x-show="!open"
        class="bg-[#078930] text-white rounded-full p-4 shadow-lg hover:bg-[#067a28] transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </button>

    <!-- Chat window -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        class="bg-white rounded-2xl shadow-2xl w-80 md:w-96 h-[500px] flex flex-col">
        
        <!-- Header -->
        <div class="bg-[#078930] text-white p-4 rounded-t-2xl flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm">Tesfa Assistant</p>
                    <p class="text-xs opacity-80">Online</p>
                </div>
            </div>
            <button @click="open = false" class="text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Messages -->
        <div x-ref="messages" class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'justify-end' : 'justify-start'" class="flex">
                    <div :class="msg.role === 'user' 
                            ? 'bg-[#078930] text-white rounded-br-none' 
                            : 'bg-gray-100 text-gray-800 rounded-bl-none'"
                         class="max-w-[80%] px-3 py-2 rounded-2xl text-sm">
                        <p x-text="msg.content"></p>
                    </div>
                </div>
            </template>

            <!-- Typing indicator -->
            <div x-show="loading" class="flex justify-start">
                <div class="bg-gray-100 rounded-2xl rounded-bl-none px-4 py-2">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suggestions -->
        <div x-show="suggestions.length > 0 && !loading" class="px-4 pb-2">
            <div class="flex flex-wrap gap-2">
                <template x-for="suggestion in suggestions" :key="suggestion">
                    <button @click="sendMessage(suggestion)"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full transition-colors"
                            x-text="suggestion"></button>
                </template>
            </div>
        </div>

        <!-- Input -->
        <div class="p-4 border-t">
            <form @submit.prevent="sendMessage(input)" class="flex items-center space-x-2">
                <input 
                    x-model="input"
                    type="text" 
                    placeholder="Type your message..."
                    class="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-[#078930]"
                    :disabled="loading">
                <button 
                    type="submit"
                    :disabled="loading || !input.trim()"
                    class="bg-[#078930] text-white rounded-full p-2 hover:bg-[#067a28] disabled:opacity-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function chatbot() {
    return {
        open: false,
        input: '',
        loading: false,
        messages: [],
        suggestions: [],
        sessionId: localStorage.getItem('chat_session') || this.generateSessionId(),

        init() {
            localStorage.setItem('chat_session', this.sessionId);
            this.messages.push({
                id: 1,
                role: 'assistant',
                content: 'Hello! I\'m Tesfa Assistant. How can I help you find your dream property today?'
            });
            this.suggestions = [
                'Search for apartments in Bole',
                'How does verification work?',
                'Tell me about escrow'
            ];
        },

        generateSessionId() {
            return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        async sendMessage(message) {
            if (!message.trim() || this.loading) return;

            this.messages.push({
                id: Date.now(),
                role: 'user',
                content: message
            });

            this.input = '';
            this.loading = true;
            this.suggestions = [];
            this.scrollToBottom();

            try {
                const response = await fetch('/api/v1/chatbot/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: this.sessionId,
                    }),
                });

                const data = await response.json();

                this.messages.push({
                    id: Date.now() + 1,
                    role: 'assistant',
                    content: data.message
                });

                this.suggestions = data.suggestions || [];
            } catch (error) {
                this.messages.push({
                    id: Date.now() + 2,
                    role: 'assistant',
                    content: 'Sorry, I encountered an error. Please try again.'
                });
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            });
        }
    }
}
</script>
@endpush