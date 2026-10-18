<?php

require_once __DIR__ . '/src/openlibrary-books.php';
require_once __DIR__ . '/src/render.php';

Kirby::plugin('mirthe/bookblock', [
    'options' => [
        'cache' => true,
        'email' => option('openlibrary.email'),
    ],
    'translations' => [
        'nl' => [
            'mirthe.bookblock.published' => 'Gepubliceerd',
            'mirthe.bookblock.pages' => '{{count}} pagina\'s',
            'mirthe.bookblock.publisher' => 'Uitgever',
            'mirthe.bookblock.series' => 'Reeks',
            'mirthe.bookblock.format' => 'Formaat',
            'mirthe.bookblock.place' => 'Plaats',
            'mirthe.bookblock.error' => 'Fout in Bookblock - ISBN {{isbn}}'
        ],
        'en' => [
            'mirthe.bookblock.published' => 'Published',
            'mirthe.bookblock.pages' => '{{count}} pages',
            'mirthe.bookblock.publisher' => 'Publisher',
            'mirthe.bookblock.series' => 'Series',
            'mirthe.bookblock.format' => 'Format',
            'mirthe.bookblock.place' => 'Place',
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