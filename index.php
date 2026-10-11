<?php

require_once __DIR__ . '/src/google-books.php';
require_once __DIR__ . '/src/render.php';

Kirby::plugin('mirthe/bookblock', [
    'options' => [
        'cache' => true
    ],
    'translations' => [
        'nl' => [
            'mirthe.bookblock.published' => 'Gepubliceerd',
            'mirthe.bookblock.pages' => '{{count}} pagina\'s',
            'mirthe.bookblock.error' => 'Fout in Bookblock - ISBN {{isbn}}'
        ],
        'en' => [
            'mirthe.bookblock.published' => 'Published',
            'mirthe.bookblock.pages' => '{{count}} pages',
            'mirthe.bookblock.error' => 'Error in Bookblock - ISBN {{isbn}}'
        ]
    ],
    'tags' => [
        'bookblock' => [
            'attr' =>[
                'isbn'
            ],
            'html' => function($tag) {
                return mirthe_bookblock_render((string) $tag->isbn);
            }
        ]
    ]
]);

?>