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
                
                $url = "https://openlibrary.org/isbn/".$isbn.".json";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $rawdata = curl_exec($ch);
                curl_close($ch);
                $bookinfo = json_decode($rawdata,true);
                // print_r($bookinfo); exit(); 

                // TODO loop by authors[]
                $authorkey = $bookinfo['authors'][0]['key'];
                $url = "https://openlibrary.org".$authorkey.".json";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $rawdata_credits = curl_exec($ch);
                curl_close($ch);
                $author = json_decode($rawdata_credits,true);
                // print_r($author); exit();
                
                $mijnoutput = '<div class="well" style="overflow: auto;">';
                $mijnoutput .= '<img src="http://covers.openlibrary.org/b/isbn/'.$bookinfo['isbn_10'][0].'-M.jpg" alt="" class="floatleft" style="margin-right: 1rem;">';
                $mijnoutput .= '<p><a href="https://openlibrary.org/'.$bookinfo['key'].'">'.$bookinfo['title']."</a> - ".$author['name']."<br>";
                $mijnoutput .= 'Verschenen '. $bookinfo['publish_date'] ." &bull; ";
                $mijnoutput .= $bookinfo['number_of_pages']." pagina's</p>";
                // $mijnoutput .= '<p><em>'.$bookinfo['tagline']."</em></p>";
                // $mijnoutput .= '<p>'.$bookinfo['overview']."</p>";

                // $i = 0;
                // $mijnoutput .= "<ul class=\"cast\">";
                // foreach ($credits['cast'] as $genre) {
                //     $mijnoutput .= '<li>'. $genre['name'] . "</li>";
                //     if (++$i == 5) break;
                // }
                // $mijnoutput .= "</ul>";

                // $mijnoutput .= "<ul class=\"genres\">";
                // foreach ($bookinfo['genres'] as $genre) {
                //     $mijnoutput .= '<li>'. $genre['name'] . "</li>";
                // }
                // $mijnoutput .= "</ul>";

                $mijnoutput .= '</div>';
               
                return $mijnoutput;
            }
        ]
    ]
]);

?>