<?php

return [
    'github' => [
        'token'    => env('GITHUB_TOKEN'),
        'base_url' => 'https://api.github.com',

        // OAuth App — login "Entrar com GitHub"
        'client_id'     => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect'      => env('GITHUB_REDIRECT_URI', env('APP_URL', 'http://localhost:8000') . '/auth/github/callback'),
    ],
];
