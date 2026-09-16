<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $knowledge = [
            [
                'category' => 'faq',
                'question' => 'How does verification work?',
                'answer' => 'Every property on Tesfa undergoes physical verification by certified agents. They visit the property, verify ownership documents, and confirm the condition.',
                'keywords' => ['verify', 'verification', 'how does verification', 'trust'],
            ],
            [
                'category' => 'escrow_guide',
                'question' => 'How does escrow work?',
                'answer' => 'Our escrow service holds your funds securely until the property is verified, all documents are in order, and you confirm the transaction. There is a 14-day dispute window.',
                'keywords' => ['escrow', 'safe payment', 'secure payment', 'funds'],
            ],
            [
                'category' => 'rent_to_own_guide',
                'question' => 'What is rent-to-own?',
                'answer' => 'Rent-to-own lets you move into a property with a down payment and pay the rest in monthly installments. Your credit score determines the terms.',
                'keywords' => ['rent to own', 'installment', 'payment plan'],
            ],
            [
                'category' => 'policy',
                'question' => 'What are the platform fees?',
                'answer' => 'The platform charges a 2% fee on completed sales, with an additional 0.5% agent fee where applicable.',
                'keywords' => ['fees', 'commission', 'price', 'cost'],
            ],
        ];

        foreach ($knowledge as $item) {
            ChatbotKnowledge::create($item);
        }

        $this->command->info('Created ' . count($knowledge) . ' chatbot knowledge entries.');
    }
}