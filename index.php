<?php Kirby::plugin('mirthe/bookblock', [
    'options' => [
        'cache' => true
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
                
                $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:".$isbn;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $rawdata = curl_exec($ch);
                curl_close($ch);
                $completebookinfo = json_decode($rawdata, true);
                // print_r($completebookinfo); exit();
                if (isset($completebookinfo['items'])) {
                    $bookinfo = $completebookinfo['items'][0]['volumeInfo'];

                    // TODO offer both links?
                    // $booklink = $bookinfo['canonicalVolumeLink'];
                    $booklink = "https://www.goodreads.com/search?q=". $isbn;

                    $mijnoutput = '<div class="well">';
                    if (array_key_exists('imageLinks', $bookinfo)) {
                        $mijnoutput .= '<div class="well-img"><img src="'.str_replace('http://', 'https://', $bookinfo['imageLinks']['thumbnail']).'" alt="" width="128"></div>';
                    }
                    $mijnoutput .= '<div class="well-body">';
                    $mijnoutput .= '<p><a href="'.$booklink.'">'.$bookinfo['title']."</a> - ".$bookinfo['authors'][0]."<br>";
                    $mijnoutput .= 'Gepubliceerd '. $bookinfo['publishedDate'] ." &bull; ";
                    $mijnoutput .= $bookinfo['pageCount']." pagina's</p>";

                    if (array_key_exists('description', $bookinfo)) {
                        $mijnoutput .= '<p>'.mb_strimwidth($bookinfo['description'], 0, 350, '&#8230;')."</p>";
                        // TODO add collapse if text is longer
                    }

                    if (array_key_exists('categories', $bookinfo)) {
                        $i = 0;
                        $mijnoutput .= "<ul class=\"genres\">";
                        foreach ($bookinfo['categories'] as $genre) {
                            $mijnoutput .= '<li>'. $genre . "</li>";
                            if (++$i == 5) {
                                break;
                            }
                        }
                        $mijnoutput .= "</ul>";
                    }

                    $mijnoutput .= '</div></div>';
                } else {
                    $mijnoutput = '<p>Error in Bookblock..</p>';
                }
               
                return $mijnoutput;
            }
        ]
    ]
]);

?>