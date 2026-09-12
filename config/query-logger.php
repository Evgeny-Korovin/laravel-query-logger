<?php

return [
    'ai' => [
        'provider' => env('QUERY_LOGGER_AI_PROVIDER', 'opencode'),
        'model'    => env('QUERY_LOGGER_AI_MODEL'),

        // Providers use the OpenAI-compatible chat completions format.
        'providers' => [
            'opencode' => [
                'url'     => env('OPENCODE_API_URL', 'https://opencode.ai/zen/v1/chat/completions'),
                'key'     => env('OPENCODE_API_KEY'),
                'model'   => env('OPENCODE_MODEL', 'big-pickle'),
                'headers' => [],
            ],
            'openai' => [
                'url'     => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
                'key'     => env('OPENAI_API_KEY'),
                'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'headers' => [],
            ],
        ],
    ],
];
