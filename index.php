<?php Kirby::plugin('mirthe/bookblock', [
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

                $isbn = $tag->isbn;

                // not sure if Google API is (and remains) free. But Open Library doesn't offer description..
                // $url = "https://openlibrary.org/api/books?bibkeys=ISBN:9780980200447&jscmd=details&format=json";
                // $url = "https://openlibrary.org/isbn/".$isbn.".json";
                // TODO Try others from https://blog.hubspot.com/website/api-books

                $cache = kirby()->cache('mirthe.bookblock');
                $cacheKey = 'google-books-' . $isbn;

                $completebookinfo = $cache->get($cacheKey);

                if ($completebookinfo === null) {
                    $url = "https://www.googleapis.com/books/v1/volumes?";
                    $url .= "q=isbn:" . $isbn;
                    $apiKey = option('googlebooks.apiKey');
                    if (!empty($apiKey)) {
                        $url .= "&key=" . $apiKey;
                    }

                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_USERAGENT      => kirby()->site()->title(),
                        CURLOPT_FAILONERROR    => true,
                    ]);
                    $rawdata = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    $completebookinfo = json_decode($rawdata, true);

                    if (is_array($completebookinfo) && !empty($completebookinfo['items'])) {
                        $cache->set($cacheKey, $completebookinfo, 604800);
                    }
                }

                if (!empty($completebookinfo['items'][0]['volumeInfo']) && is_array($completebookinfo['items'][0]['volumeInfo'])) {
                    $bookinfo = $completebookinfo['items'][0]['volumeInfo'];
                    $booklink = $bookinfo['canonicalVolumeLink'] ?? 'https://www.goodreads.com/search?q=' . urlencode($isbn);
                    $thumbnail = $bookinfo['imageLinks']['thumbnail'] ?? $bookinfo['imageLinks']['smallThumbnail'] ?? '';
                    $thumbnail = str_replace('http://', 'https://', $thumbnail);
                    $authors = $bookinfo['authors'] ?? [];

                    $mijnoutput = '<div class="well">';
                    if ($thumbnail !== '') {
                        $mijnoutput .= '<div class="well-img"><a href="'.$booklink.'" target="_blank"><img src="'.$thumbnail.'" alt="'.htmlspecialchars($bookinfo['title'] ?? '', ENT_QUOTES).'" width="128"></a></div>';
                    }
                    $mijnoutput .= '<div class="well-body">';
                    $mijnoutput .= '<p><a href="'.$booklink.'">'.htmlspecialchars($bookinfo['title'] ?? '', ENT_QUOTES)."</a> - ".htmlspecialchars($authors[0] ?? '', ENT_QUOTES)."<br>";
                    $published = htmlspecialchars($bookinfo['publishedDate'] ?? '', ENT_QUOTES);
                    $pageCount = htmlspecialchars($bookinfo['pageCount'] ?? '', ENT_QUOTES);
                    $mijnoutput .= t('mirthe.bookblock.published');
                    if ($published !== '') {
                        $mijnoutput .= ' ' . $published;
                    }
                    if ($published !== '' && $pageCount !== '' && $pageCount !== '0') {
                        $mijnoutput .= ' &bull; ';
                    }
                    if ($pageCount !== '' && $pageCount !== '0') {
                        $mijnoutput .= ' ' . t('mirthe.bookblock.pages', ['count' => $pageCount]);
                    }
                    $mijnoutput .= '</p>';

                    if (!empty($bookinfo['description'])) {
                        $mijnoutput .= '<p>'.mb_strimwidth($bookinfo['description'], 0, 350, '&#8230;')."</p>";
                    }

                    if (!empty($bookinfo['categories']) && is_array($bookinfo['categories'])) {
                        $i = 0;
                        $mijnoutput .= "<ul class=\"genres\">";
                        foreach ($bookinfo['categories'] as $genre) {
                            $mijnoutput .= '<li>'.htmlspecialchars($genre, ENT_QUOTES)."</li>";
                            if (++$i === 5) {
                                break;
                            }
                        }
                        $mijnoutput .= "</ul>";
                    }

                    $mijnoutput .= '</div></div>';
                } else {
                    $mijnoutput = '<p><small>'.t('mirthe.bookblock.error', ['isbn' => htmlspecialchars($isbn, ENT_QUOTES)]).'</small></p>';
                }

                return $mijnoutput;
            }
        ]
    ]
]);

?>