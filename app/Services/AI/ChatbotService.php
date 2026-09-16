<?php

namespace App\Services\AI;

use App\Models\ChatbotConversation;
use App\Models\ChatbotKnowledge;
use App\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private const MODEL = 'gpt-4';
    private const MAX_TOKENS = 500;
    private const TEMPERATURE = 0.7;

    /**
     * Process user message
     */
    public function processMessage(?string $userId, string $sessionId, string $message, array $context = []): array
    {
        // Log user message
        $userMessage = ChatbotConversation::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message_role' => 'user',
            'message_content' => $message,
            'context_property_id' => $context['property_id'] ?? null,
            'context_search_query' => $context['search_query'] ?? null,
        ]);

        // Detect intent
        $intent = $this->detectIntent($message);

        // Get knowledge base matches
        $knowledge = $this->searchKnowledge($message);

        // Generate response
        $response = $this->generateResponse($message, $intent, $knowledge, $context);

        // Log assistant response
        $assistantMessage = ChatbotConversation::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message_role' => 'assistant',
            'message_content' => $response['content'],
            'detected_intent' => $intent['name'],
            'intent_confidence' => $intent['confidence'],
            'model_used' => self::MODEL,
            'prompt_tokens' => $response['prompt_tokens'],
            'completion_tokens' => $response['completion_tokens'],
            'response_time_ms' => $response['response_time_ms'],
            'cost_usd' => $this->calculateCost($response['prompt_tokens'], $response['completion_tokens']),
        ]);

        return [
            'message' => $response['content'],
            'intent' => $intent['name'],
            'suggestions' => $this->getSuggestions($intent['name']),
            'handoff_to_human' => $intent['confidence'] < 0.5,
        ];
    }

    /**
     * Detect user intent
     */
    private function detectIntent(string $message): array
    {
        $message = strtolower($message);

        $intents = [
            'greeting' => ['hello', 'hi', 'hey', 'selam', 'tena yistilign'],
            'property_search' => ['find', 'search', 'looking for', 'show me', 'properties in'],
            'property_details' => ['details', 'more info', 'tell me about', 'price of'],
            'escrow_question' => ['escrow', 'how does escrow', 'safe payment', 'secure payment'],
            'verification_question' => ['verify', 'verification', 'how do you verify', 'trust'],
            'rent_to_own_question' => ['rent to own', 'rent-to-own', 'installment', 'payment plan'],
            'booking_question' => ['book', 'reservation', 'short term', 'nightly'],
            'pricing_question' => ['price', 'cost', 'how much', 'fees', 'commission'],
            'agent_question' => ['agent', 'how to become', 'certification', 'verify properties'],
            'complaint' => ['problem', 'issue', 'not working', 'broken', 'wrong'],
            'goodbye' => ['bye', 'goodbye', 'thanks', 'thank you', 'dehna hun'],
        ];

        $bestIntent = 'unknown';
        $bestScore = 0;

        foreach ($intents as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIntent = $intent;
            }
        }

        $confidence = min($bestScore / 3, 1.0);

        return [
            'name' => $bestIntent,
            'confidence' => $confidence,
        ];
    }

    /**
     * Search knowledge base
     */
    private function searchKnowledge(string $message): array
    {
        $keywords = explode(' ', strtolower($message));

        return ChatbotKnowledge::where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 3) {
                        $query->orWhere('question', 'LIKE', "%{$keyword}%")
                            ->orWhere('answer', 'LIKE', "%{$keyword}%")
                            ->orWhereJsonContains('keywords', $keyword);
                    }
                }
            })
            ->orderBy('helpful_count', 'desc')
            ->limit(3)
            ->get()
            ->toArray();
    }

    /**
     * Generate response using OpenAI
     */
    private function generateResponse(string $message, array $intent, array $knowledge, array $context): array
    {
        $startTime = microtime(true);

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($intent, $knowledge, $context);

        // Call OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
        ]);

        $responseTime = (microtime(true) - $startTime) * 1000;

        if (!$response->successful()) {
            Log::error('OpenAI API error', ['response' => $response->body()]);
            return $this->getFallbackResponse($intent);
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? 'I apologize, but I could not generate a response.',
            'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            'response_time_ms' => round($responseTime),
        ];
    }

    /**
     * Build system prompt
     */
    private function buildSystemPrompt(array $intent, array $knowledge, array $context): string
    {
        $basePrompt = "You are Tesfa, a helpful AI assistant for an Ethiopian property platform. "
            . "You help users find properties, understand escrow, learn about verification, and answer questions "
            . "about buying, selling, and renting properties in Ethiopia. "
            . "Be friendly, professional, and concise. If you don't know something, admit it and offer to connect "
            . "the user with a human agent. Always prioritize user safety and platform trust.";

        // Add intent-specific context
        $intentContext = match($intent['name']) {
            'property_search' => "The user is looking for properties. Help them refine their search.",
            'escrow_question' => "Explain our escrow service: funds are held securely until verification is complete, with a 14-day dispute window.",
            'verification_question' => "Explain our verification: certified agents physically visit properties, verify documents, and create a Property Passport.",
            'rent_to_own_question' => "Explain rent-to-own: down payment, monthly installments, and credit score requirements.",
            default => "",
        };

        // Add knowledge base context
        $knowledgeContext = "";
        if (!empty($knowledge)) {
            $knowledgeContext = "\n\nRelevant information:\n";
            foreach ($knowledge as $item) {
                $knowledgeContext .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
            }
        }

        // Add property context
        $propertyContext = "";
        if (!empty($context['property_id'])) {
            $property = Property::find($context['property_id']);
            if ($property) {
                $propertyContext = "\n\nCurrent property context:\n"
                    . "Title: {$property->title}\n"
                    . "Type: {$property->type}\n"
                    . "Price: {$property->price} {$property->price_currency}\n"
                    . "Location: {$property->neighborhood}, {$property->city}\n"
                    . "Bedrooms: {$property->bedrooms}, Bathrooms: {$property->bathrooms}\n"
                    . "Area: {$property->area_sqm} sqm\n";
            }
        }

        return $basePrompt . "\n\n" . $intentContext . $knowledgeContext . $propertyContext;
    }

    /**
     * Get fallback response when API fails
     */
    private function getFallbackResponse(array $intent): array
    {
        $responses = [
            'greeting' => "Hello! Welcome to Tesfa. How can I help you find your dream property today?",
            'property_search' => "I can help you search for properties. What neighborhood and budget are you considering?",
            'escrow_question' => "Our escrow service protects your funds until the property is verified and all documents are in order. Would you like more details?",
            'verification_question' => "Every property on Tesfa undergoes physical verification by certified agents. We verify documents, condition, and ownership.",
            'rent_to_own_question' => "Rent-to-own allows you to move in with a down payment and pay the rest in monthly installments. Your credit score determines the terms.",
            'default' => "I'm here to help with property search, escrow, verification, and rent-to-own questions. What would you like to know?",
        ];

        return [
            'content' => $responses[$intent['name']] ?? $responses['default'],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'response_time_ms' => 0,
        ];
    }

    /**
     * Get suggestions based on intent
     */
    private function getSuggestions(string $intent): array
    {
        return match($intent) {
            'property_search' => [
                'Show me apartments in Bole',
                'Find villas under 10M ETB',
                'Properties with 3+ bedrooms',
            ],
            'escrow_question' => [
                'How long does escrow take?',
                'What if there is a dispute?',
                'What are the escrow fees?',
            ],
            'rent_to_own_question' => [
                'What credit score do I need?',
                'How much is the down payment?',
                'Can I pay off early?',
            ],
            default => [
                'Search for properties',
                'How does verification work?',
                'Tell me about escrow',
            ],
        };
    }

    /**
     * Calculate API cost
     */
    private function calculateCost(int $promptTokens, int $completionTokens): float
    {
        // GPT-4 pricing (as of 2024): $0.03/1K prompt, $0.06/1K completion
        $promptCost = ($promptTokens / 1000) * 0.03;
        $completionCost = ($completionTokens / 1000) * 0.06;

        return round($promptCost + $completionCost, 6);
    }

    /**
     * Rate response helpfulness
     */
    public function rateResponse(ChatbotConversation $conversation, bool $helpful): void
    {
        $conversation->update([
            'was_helpful' => $helpful,
        ]);

        // Update knowledge base helpful count
        if ($conversation->detected_intent) {
            ChatbotKnowledge::where('category', $conversation->detected_intent)
                ->first()
                ?->increment('helpful_count');
        }
    }
}