<?php

if (!function_exists('mirthe_bookblock_render')) {
    function mirthe_bookblock_render(string $isbn): string
    {
        $result = mirthe_bookblock_get_volume_info($isbn);
        $bookInfo = $result['volumeInfo'];

        if ($bookInfo === null) {
            if (function_exists('errorMonitorLog')) {
                errorMonitorLog(
                    'BOOKBLOCK',
                    'Google Books lookup failed for ISBN ' . $isbn . ': ' . $result['failureReason'],
                    __FILE__,
                    __LINE__
                );
            }

            return '<p><small>' . tt('mirthe.bookblock.error', ['isbn' => htmlspecialchars($isbn, ENT_QUOTES)]) . '</small></p>';
        }

        $bookLink = $bookInfo['canonicalVolumeLink'] ?? 'https://www.goodreads.com/search?q=' . urlencode($isbn);
        $thumbnail = $bookInfo['imageLinks']['thumbnail'] ?? $bookInfo['imageLinks']['smallThumbnail'] ?? '';
        $thumbnail = str_replace('http://', 'https://', $thumbnail);
        $authors = $bookInfo['authors'] ?? [];

        $output = '<div class="well">';
        if ($thumbnail !== '') {
            $output .= '<div class="well-img"><a href="' . $bookLink . '" target="_blank"><img src="' . $thumbnail . '" alt="' . htmlspecialchars($bookInfo['title'] ?? '', ENT_QUOTES) . '" width="128"></a></div>';
        }
        $output .= '<div class="well-body">';
        $output .= '<p><a href="' . $bookLink . '">' . htmlspecialchars($bookInfo['title'] ?? '', ENT_QUOTES) . '</a> - ' . htmlspecialchars($authors[0] ?? '', ENT_QUOTES) . '<br>';
        $published = htmlspecialchars($bookInfo['publishedDate'] ?? '', ENT_QUOTES);
        $pageCount = htmlspecialchars($bookInfo['pageCount'] ?? '', ENT_QUOTES);
        $output .= t('mirthe.bookblock.published');
        if ($published !== '') {
            $output .= ' ' . $published;
        }
        if ($published !== '' && $pageCount !== '' && $pageCount !== '0') {
            $output .= ' &bull; ';
        }
        if ($pageCount !== '' && $pageCount !== '0') {
            $output .= ' ' . tt('mirthe.bookblock.pages', ['count' => $pageCount]);
        }
        $output .= '</p>';

        if (!empty($bookInfo['description'])) {
            $output .= '<p>' . mb_strimwidth($bookInfo['description'], 0, 350, '&#8230;') . '</p>';
        }

        if (!empty($bookInfo['categories']) && is_array($bookInfo['categories'])) {
            $output .= '<ul class="genres">';
            foreach (array_slice($bookInfo['categories'], 0, 5) as $genre) {
                $output .= '<li>' . htmlspecialchars($genre, ENT_QUOTES) . '</li>';
            }
            $output .= '</ul>';
        }

        return $output . '</div></div>';
    }
}