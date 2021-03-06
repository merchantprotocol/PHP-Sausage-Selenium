<?php

return [
    'base_url' => 'http://127.0.0.1/',
    'browsers' => [
        [
            'browserName' => 'chrome',
            'desiredCapabilities' => [
                'version' => '45.0',
                'platform' => 'OS X 10.10',
            ],
        ],
    ],
    'admin_user' => [
        'login'     => 'merchantprotocol',
        'password'  => 'merchantprotocol123',
    ],
    'adminhtml_link' => '/admin',
    'customer' => [
        'login'     => '',
        'password'  => '',
    ],
    'frontend_links' => [
        '/the-science-research',
        '/testimonials',
        '/faq',
        '/blog',
        '/ingredients',
        '/relieffactor-quickstart-pack.html',
    ],
    'admin_custom_links' => [
        '/enhanced/dashboard',
        '/extension_local',
        '/adminhtml_subscribe',
        '/enhanced_settings',
        '/adminhtml_recurring/processDaily',
        '/adminhtml_cryozonic/check',
    ],
    'orders_count' => 20
];
