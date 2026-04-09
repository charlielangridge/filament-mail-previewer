<?php

use Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer;

// config for charlielangridge/FilamentMailPreviewer
return [
    'laravel_mail_previewer_facade' => LaravelMailPreviewer::class,

    'authorization' => [
        'mode' => 'none',

        'gate_ability' => 'viewFilamentMailPreviewer',

        'policy' => [
            'model' => null,
            'ability' => 'viewAny',
        ],

        'callback' => null,
    ],
];
