<?php

namespace App\Http\Controllers\Api;

use App\Services\AI\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(
        private ChatbotService $chatbotService
    ) {}

    /**
     * Send message to chatbot
     */
    public function message(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $userId = auth()->id();

        $response = $this->chatbotService->processMessage(
            $userId,
            $validated['session_id'],
            $validated['message'],
            $validated['context'] ?? []
        );

        return response()->json($response);
    }

    /**
     * Rate chatbot response
     */
    public function rate(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:chatbot_conversations,id',
            'helpful' => 'required|boolean',
        ]);

        $conversation = \App\Models\ChatbotConversation::findOrFail($validated['conversation_id']);

        // Only the user who owns the conversation can rate
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $this->chatbotService->rateResponse($conversation, $validated['helpful']);

        return response()->json(['success' => true]);
    }

    /**
     * Get chat history
     */
    public function history(string $sessionId)
    {
        $conversations = \App\Models\ChatbotConversation::where('session_id', $sessionId)
            ->where('user_id', auth()->id())
            ->orderBy('created_at')
            ->get();

        return response()->json($conversations);
    }
}