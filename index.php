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
                $completebookinfo = json_decode($rawdata,true);
                // print_r($completebookinfo); exit();
                $bookinfo = $completebookinfo['items'][0]['volumeInfo'];
                
                $mijnoutput = '<div class="well" style="overflow: auto;">';
                $mijnoutput .= '<img src="'.$bookinfo['imageLinks']['thumbnail'].'" alt="" class="floatleft" style="margin-right: 1rem;">';
                $mijnoutput .= '<p><a href="'.$bookinfo['canonicalVolumeLink'].'">'.$bookinfo['title']."</a> - ".$bookinfo['authors'][0]."<br>";
                $mijnoutput .= 'Gepubliceerd '. $bookinfo['publishedDate'] ." &bull; ";
                $mijnoutput .= $bookinfo['pageCount']." pagina's</p>";
                $mijnoutput .= '<p>'.$bookinfo['description']."</p>";

                // $i = 0;
                // $mijnoutput .= "<ul class=\"genres\">";
                // foreach ($bookinfo['categories'] as $genre) {
                //     $mijnoutput .= '<li>'. $genre . "</li>";
                //     if (++$i == 5) break;
                // }
                // $mijnoutput .= "</ul>";

                $mijnoutput .= '</div>';
               
                return $mijnoutput;
            }
        ]
    ]
]);

?>