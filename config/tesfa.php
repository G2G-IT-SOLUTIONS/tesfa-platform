<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Fees
    |--------------------------------------------------------------------------
    */
    'fees' => [
        'platform_percent' => env('PLATFORM_FEE_PERCENT', 2.0),
        'agent_percent' => env('AGENT_FEE_PERCENT', 0.5),
        'concierge_percent' => env('CONCIERGE_FEE_PERCENT', 0.25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Escrow Configuration
    |--------------------------------------------------------------------------
    */
    'escrow' => [
        'dispute_window_hours' => 336, // 14 days
        'auto_refund_days' => 30,
        'min_deposit_percent' => 10,
        'supported_currencies' => ['ETB', 'USD', 'USDT', 'USDC'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rent-to-Own Configuration
    |--------------------------------------------------------------------------
    */
    'rent_to_own' => [
        'min_credit_score' => 580,
        'max_financing_months' => 60,
        'min_down_payment_percent' => 5,
        'late_payment_fee_percent' => 2.0,
        'grace_period_days' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Short-Term Rental Configuration
    |--------------------------------------------------------------------------
    */
    'short_term' => [
        'min_nights' => 1,
        'max_nights' => 30,
        'default_cleaning_fee' => 500,
        'default_security_deposit' => 2000,
        'cancellation_policy' => 'moderate', // flexible, moderate, strict
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Configuration
    |--------------------------------------------------------------------------
    */
    'verification' => [
        'base_fee' => 300,
        'distance_bonus_per_km' => 10,
        'urgency_bonus_percent' => 25,
        'quality_bonus' => 100,
        'passport_validity_months' => 12,
        'max_verification_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'avm' => [
            'model_version' => 'avm_v1.0',
            'cache_ttl' => 3600,
            'comparable_radius_km' => 2.0,
            'min_comparables' => 3,
            'confidence_threshold' => 0.6,
        ],
        'fraud' => [
            'model_version' => 'fraud_v1.0',
            'auto_approve_threshold' => 0.3,
            'review_threshold' => 0.7,
            'block_threshold' => 0.9,
        ],
        'credit' => [
            'model_version' => 'credit_v1.0',
            'base_score' => 450,
            'min_score' => 300,
            'max_score' => 850,
            'weights' => [
                'payment_history' => 0.35,
                'amounts_owed' => 0.30,
                'credit_history_length' => 0.15,
                'credit_mix' => 0.10,
                'new_credit' => 0.10,
            ],
        ],
        'demand' => [
            'model_version' => 'demand_v1.0',
            'forecast_days' => 30,
            'training_days' => 90,
        ],
        'pricing' => [
            'model_version' => 'pricing_v1.0',
            'max_surge' => 2.0,
            'min_discount' => 0.7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Health Thresholds
    |--------------------------------------------------------------------------
    */
    'health' => [
        'warning' => [
            'response_time' => 500,
            'error_rate' => 1,
            'cpu_usage' => 70,
            'memory_usage' => 80,
            'db_connections' => 80,
            'queue_size' => 100,
            'disk_space' => 80,
        ],
        'critical' => [
            'response_time' => 2000,
            'error_rate' => 5,
            'cpu_usage' => 90,
            'memory_usage' => 95,
            'db_connections' => 95,
            'queue_size' => 1000,
            'disk_space' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ethiopian Cities & Neighborhoods
    |--------------------------------------------------------------------------
    */
    'locations' => [
        'Addis_Ababa' => [
            'sub_cities' => [
                'Bole' => ['Bole', 'CMC', 'Megenagna', 'Gerji', 'Summit'],
                'Kirkos' => ['Kazanchis', 'Meskel Flower', 'Gotera'],
                'Arada' => ['Piassa', 'Merkato', 'Arat Kilo'],
                'Gulele' => ['Shiro Meda', 'Entoto'],
                'Lideta' => ['Lideta', 'Torhailoch'],
                'Yeka' => ['Mekanisa', 'Sarbet', 'Ayat'],
                'Nifas_Silk_Lafto' => ['Lafto', 'Jemo', 'Ayat'],
                'Akaki_Kality' => ['Kality', 'Akaki'],
                'Addis_Ketema' => ['Addis Ketema'],
                'Kolfe_Keranio' => ['Kolfe', 'Keranio'],
            ],
        ],
        'Adama' => ['Adama'],
        'Bahir_Dar' => ['Bahir Dar'],
        'Hawassa' => ['Hawassa'],
        'Mekelle' => ['Mekelle'],
        'Dire_Dawa' => ['Dire Dawa'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    */
    'document_types' => [
        'lease_certificate' => 'Lease Certificate',
        'building_permit' => 'Building Permit',
        'ownership_document' => 'Ownership Document',
        'id_document' => 'ID Document',
        'tax_record' => 'Tax Record',
        'utility_bill' => 'Utility Bill',
        'bank_statement' => 'Bank Statement',
        'employment_letter' => 'Employment Letter',
        'reference_letter' => 'Reference Letter',
        'contract' => 'Contract',
    ],

    /*
    |--------------------------------------------------------------------------
    | Property Types
    |--------------------------------------------------------------------------
    */
    'property_types' => [
        'apartment' => 'Apartment',
        'villa' => 'Villa',
        'townhouse' => 'Townhouse',
        'land' => 'Land',
        'commercial' => 'Commercial',
        'mixed_use' => 'Mixed Use',
    ],

    /*
    |--------------------------------------------------------------------------
    | Property Conditions
    |--------------------------------------------------------------------------
    */
    'property_conditions' => [
        'new' => 'New',
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'needs_renovation' => 'Needs Renovation',
    ],
];