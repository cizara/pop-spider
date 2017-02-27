<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'help',
            'default'    => true
        ],
        'crawl [--dir=] [--tags=] <url>' => [
            'controller' => 'PopSpider\Controller\ConsoleController',
            'action'     => 'crawl'
        ]
    ]
];