
<?php

if (!function_exists('mirthe_bookblock_render')) {
    function mirthe_bookblock_render(string $isbn): string
    {
        $result = mirthe_bookblock_get_volume_info($isbn);
        $bookInfo = $result['volumeInfo'] ?? null;

        if ($bookInfo === null) {
            if (function_exists('errorMonitorLog')) {
                errorMonitorLog(
                    'BOOKBLOCK',
                    'Open Library lookup failed for ISBN ' . $isbn . ': '
                        . ($result['failureReason'] ?? 'Unknown error'),
                    __FILE__,
                    __LINE__
                );
            }

            return '<p><small>'
                . tt('mirthe.bookblock.error', [
                    'isbn' => htmlspecialchars(
                        $isbn,
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                ])
                . '</small></p>';
        }

        // Boeklink
        $bookLink = $bookInfo['canonicalVolumeLink']
            ?? 'https://openlibrary.org/isbn/' . urlencode($isbn);

        $bookLinkHtml = htmlspecialchars(
            $bookLink,
            ENT_QUOTES,
            'UTF-8'
        );

        // Omslag
        $thumbnail = $bookInfo['imageLinks']['thumbnail']
            ?? $bookInfo['imageLinks']['smallThumbnail']
            ?? '';

        $thumbnailHtml = htmlspecialchars(
            $thumbnail,
            ENT_QUOTES,
            'UTF-8'
        );

        // Titel en auteur(s)
        $title = $bookInfo['title'] ?? '';
        $authors = $bookInfo['authors'] ?? [];

        if (!is_array($authors)) {
            $authors = [$authors];
        }

        $authors = array_values(array_filter(
            $authors,
            static fn ($author): bool => is_string($author) && $author !== ''
        ));

        // Publicatiedatum en aantal pagina's
        $published = (string) ($bookInfo['publishedDate'] ?? '');
        $pageCount = $bookInfo['pageCount'] ?? '';

        $publishedHtml = htmlspecialchars(
            $published,
            ENT_QUOTES,
            'UTF-8'
        );

        $pageCountHtml = htmlspecialchars(
            (string) $pageCount,
            ENT_QUOTES,
            'UTF-8'
        );

        // Beschrijving
        $description = $bookInfo['description'] ?? '';

        if (is_array($description)) {
            $description = $description['value'] ?? '';
        }

        if (!is_string($description)) {
            $description = '';
        }

        $description = trim(strip_tags($description));

        // Genres / onderwerpen
        $categories = $bookInfo['categories'] ?? [];

        if (!is_array($categories)) {
            $categories = [];
        }

        $details = [
            'mirthe.bookblock.series' => $bookInfo['series'] ?? [],
        ];

        // HTML opbouwen
        $output = '<div class="well">';

        if ($thumbnail !== '') {
            $output .= '<div class="well-img">';
            $output .= '<a href="' . $bookLinkHtml
                . '" target="_blank" rel="noopener noreferrer">';
            $output .= '<img src="' . $thumbnailHtml
                . '" alt="'
                . htmlspecialchars(
                    $title,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '" width="128">';
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '<div class="well-body">';

        $output .= '<p>';
        $output .= '<a href="' . $bookLinkHtml . '">'
            . htmlspecialchars(
                $title,
                ENT_QUOTES,
                'UTF-8'
            )
            . '</a>';

        if ($authors !== []) {
            $output .= ' - '
                . htmlspecialchars(
                    implode(', ', $authors),
                    ENT_QUOTES,
                    'UTF-8'
                );
        }

        $output .= '<br>';

        $output .= t('mirthe.bookblock.published');

        if ($published !== '') {
            $output .= ' ' . $publishedHtml;
        }

        if (
            $published !== ''
            && $pageCount !== ''
            && (int) $pageCount > 0
        ) {
            $output .= ' &bull; ';
        }

        if ($pageCount !== '' && (int) $pageCount > 0) {
            $output .= ' ' . tt(
                'mirthe.bookblock.pages',
                ['count' => $pageCountHtml]
            );
        }

        $output .= '</p>';

        $detailItems = '';

        foreach ($details as $label => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            $values = array_values(array_filter(
                $values,
                static fn ($value): bool => is_string($value) && $value !== ''
            ));

            if ($values === []) {
                continue;
            }

            $detailItems .= '<li>'
                . htmlspecialchars(t($label), ENT_QUOTES, 'UTF-8')
                . ': '
                . htmlspecialchars(
                    implode(', ', $values),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</li>';
        }

        if ($detailItems !== '') {
            $output .= '<ul class="book-details">'
                . $detailItems
                . '</ul>';
        }

        // Beschrijving maximaal 300 tekens
        if ($description !== '') {
            $description = mb_strimwidth(
                $description,
                0,
                300,
                '…',
                'UTF-8'
            );

            $output .= '<p>'
                . htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</p>';
        }

        // Maximaal vier genres / onderwerpen
        if (!empty($categories)) {
            $output .= '<ul class="genres">';

            foreach (array_slice($categories, 0, 4) as $genre) {
                if (
                    !is_string($genre)
                    || $genre === ''
                    || preg_match('/^[a-z0-9_-]+:[^=]+=.+$/i', $genre)
                ) {
                    continue;
                }

                $output .= '<li>'
                    . htmlspecialchars(
                        $genre,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . '</li>';
            }

            $output .= '</ul>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }
}
