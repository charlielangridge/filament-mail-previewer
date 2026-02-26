<?php

// config for charlielangridge/FilamentMailPreviewer
return [
    'laravel_mail_previewer_facade' => \Charlielangridge\LaravelMailPreviewer\Facades\LaravelMailPreviewer::class,

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
