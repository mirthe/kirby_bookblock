<?php Kirby::plugin('mirthe/bookblock', [
    'options' => [
        'cache' => true
    ],
    'tags' => [
        'bookblock' => [
            'attr' =>[
                'tmdb'
            ],
            'html' => function($tag) {

                $isbn = $tag->isbn;
                
                $url = "https://openlibrary.org/isbn/0593135202.json"; // deze redirect naar onderstaande, grr.
                $url = "https://openlibrary.org/books/OL30036715M.json";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $rawdata = curl_exec($ch);
                curl_close($ch);
                $bookinfo = json_decode($rawdata,true);
                // print_r($bookinfo); exit(); 

                // $url = "https://api.themoviedb.org/3/movie/". $tmdbid ."/credits?api_key=" . $api_key;
                // $ch = curl_init($url);
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                // $rawdata_credits = curl_exec($ch);
                // curl_close($ch);
                // $credits = json_decode($rawdata_credits,true);
                
                $mijnoutput = '<div class="well" style="overflow: auto;">';
                $mijnoutput .= '<img src="http://covers.openlibrary.org/b/isbn/'.$bookinfo['isbn_10'][0].'-M.jpg" alt="" class="floatleft" style="margin-right: 1rem;">';
                $mijnoutput .= '<p><a href="https://openlibrary.org/'.$bookinfo['key'].'">'.$bookinfo['title']."</a><br>Published ". $bookinfo['publish_date']."</p>";
                //$mijnoutput .= '<p><em>'.$bookinfo['tagline']."</em></p>";
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