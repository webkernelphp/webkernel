<?php declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    | The Laravel guard used by webkernel()->auth().
    | Defaults to 'web'. Set to the Filament panel guard if needed.
    */
    'guard' => env('WEBKERNEL_AUTH_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | AI Agent Field Sensitivity Map
    |--------------------------------------------------------------------------
    | Defines which model fields AI agents may read.
    |
    | Sensitivity levels (ascending restriction):
    |   public      — always readable by all agents and API consumers
    |   internal    — readable by authenticated agents only (DEFAULT)
    |   restricted  — readable only by privileged / human-confirmed agents
    |   sensitive   — PII, financial, medical — NEVER exposed to AI agents
    |   critical    — credentials, secrets — NEVER stored in agent context
    |
    | Structure: [ ModelClass::class => [ field => level ] ]
    |
    | You can also define global field-name defaults (no model class key):
    |   'email' => 'sensitive'
    |
    | Example:
    |
    |   \App\Models\Customer::class => [
    |       'id'            => 'public',
    |       'name'          => 'internal',
    |       'email'         => 'sensitive',
    |       'phone'         => 'sensitive',
    |       'notes'         => 'public',       // agent can annotate
    |       'stripe_id'     => 'critical',
    |       'credit_score'  => 'sensitive',
    |       'address'       => 'restricted',
    |   ],
    */
    'field_sensitivity' => [

        // Global defaults by field name (applies when no model-specific entry exists)
        'password'            => 'critical',
        'remember_token'      => 'critical',
        'two_factor_secret'   => 'critical',
        'two_factor_codes'    => 'critical',
        'stripe_id'           => 'critical',
        'api_token'           => 'critical',
        'email'               => 'sensitive',
        'phone'               => 'sensitive',
        'phone_number'        => 'sensitive',
        'birth_date'          => 'sensitive',
        'date_of_birth'       => 'sensitive',
        'ssn'                 => 'sensitive',
        'tax_id'              => 'sensitive',
        'credit_card'         => 'sensitive',
        'address'             => 'restricted',
        'ip_address'          => 'restricted',
        'name'                => 'internal',
        'first_name'          => 'internal',
        'last_name'           => 'internal',
        'id'                  => 'public',
        'uuid'                => 'public',
        'created_at'          => 'public',
        'updated_at'          => 'public',
        'notes'               => 'public',
        'tags'                => 'public',
        'status'              => 'public',

    ],

];
