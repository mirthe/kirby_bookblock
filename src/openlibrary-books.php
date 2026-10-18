
<?php

if (!function_exists('mirthe_bookblock_get_volume_info')) {
    function mirthe_bookblock_get_volume_info(string $isbn): array
    {
        $cache = kirby()->cache('mirthe.bookblock');

        // Normaliseer de ISBN voor de aanvraag en cache.
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        $cacheKey = 'open-library-v2-' . $isbn;

        $completeBookInfo = $cache->get($cacheKey);
        $failureReason = 'Open Library returned no matching edition.';

        if ($completeBookInfo === null) {
            $url = 'https://openlibrary.org/isbn/'
                . urlencode($isbn)
                . '.json';

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_USERAGENT      => kirby()->site()->title()
                    . ' (Kirby book metadata plugin; contact: me@mirthe.org)',
            ]);

            $rawData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            $data = null;

            if ($rawData === false) {
                $failureReason = 'Open Library request failed: '
                    . $curlError;
            } elseif ($httpCode < 200 || $httpCode >= 300) {
                $failureReason = 'Open Library returned HTTP '
                    . $httpCode;
            } else {
                $data = json_decode($rawData, true);

                if (!is_array($data)) {
                    $failureReason = 'Open Library returned invalid JSON.';
                }
            }

            
            if (is_array($data) && !empty($data['title'])) {
                // Haal aanvullende gegevens op van het bijbehorende werk.
                $workData = [];

                $workKey = $data['works'][0]['key'] ?? null;

                if (
                    is_string($workKey)
                    && preg_match('#^/works/OL[0-9]+W$#', $workKey)
                ) {
                    $workUrl = 'https://openlibrary.org' . $workKey . '.json';

                    $workCh = curl_init($workUrl);

                    curl_setopt_array($workCh, [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 15,
                        CURLOPT_USERAGENT      => kirby()->site()->title()
                            . ' (Kirby book metadata plugin; contact: me@mirthe.org)',
                    ]);

                    $workRaw = curl_exec($workCh);
                    $workCode = curl_getinfo($workCh, CURLINFO_HTTP_CODE);

                    curl_close($workCh);

                    if ($workRaw !== false && $workCode >= 200 && $workCode < 300) {
                        $decodedWork = json_decode($workRaw, true);

                        if (is_array($decodedWork)) {
                            $workData = $decodedWork;
                        }
                    }
                }

                // Gebruik editiegegevens als basis en vul ontbrekende gegevens aan
                // vanuit het bijbehorende werk.
                $description = $data['description']
                    ?? $workData['description']
                    ?? '';

                if (is_array($description)) {
                    $description = $description['value'] ?? '';
                }

                // Work authors are references; edition authors can be names or references.
                $authorKeys = [];
                $fallbackAuthors = [];
                $workAuthors = $workData['authors'] ?? [];
                $editionAuthors = $data['authors'] ?? [];

                if ($editionAuthors === []) {
                    $editionAuthors = $data['author'] ?? [];
                }

                foreach ([$workAuthors, $editionAuthors] as $authorList) {
                    if (!is_array($authorList)) {
                        $authorList = [$authorList];
                    }

                    foreach ($authorList as $author) {
                        if (is_string($author)) {
                            $fallbackAuthors[] = $author;
                            continue;
                        }

                        if (!is_array($author)) {
                            continue;
                        }

                        $authorKey = $author['author']['key']
                            ?? $author['key']
                            ?? null;

                        if (
                            is_string($authorKey)
                            && preg_match('#^/authors/OL[0-9]+A$#', $authorKey)
                        ) {
                            $authorKeys[] = $authorKey;
                        }
                    }
                }

                $authorNames = [];

                foreach (array_unique($authorKeys) as $authorKey) {
                    $authorCh = curl_init(
                        'https://openlibrary.org' . $authorKey . '.json'
                    );

                    curl_setopt_array($authorCh, [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_USERAGENT      => kirby()->site()->title()
                            . ' (Kirby book metadata plugin; contact: me@mirthe.org)',
                    ]);

                    $authorRaw = curl_exec($authorCh);
                    $authorCode = curl_getinfo($authorCh, CURLINFO_HTTP_CODE);
                    curl_close($authorCh);

                    if (
                        $authorRaw !== false
                        && $authorCode >= 200
                        && $authorCode < 300
                    ) {
                        $authorData = json_decode($authorRaw, true);

                        if (is_array($authorData) && !empty($authorData['name'])) {
                            $authorNames[] = $authorData['name'];
                        }
                    }
                }

                if ($authorNames === []) {
                    $authorNames = $fallbackAuthors;
                }

                // Onderwerpen kunnen bij het werk of de editie staan.
                $subjects = array_values(array_unique(array_merge(
                    is_array($data['subjects'] ?? null) ? $data['subjects'] : [],
                    is_array($workData['subjects'] ?? null) ? $workData['subjects'] : []
                )));

                $pageCount = $data['number_of_pages'] ?? null;

                if (
                    (!is_numeric($pageCount) || (int) $pageCount < 1)
                    && is_string($data['pagination'] ?? null)
                    && preg_match('/(\d+)\s*p(?:ages?)?\b/i', $data['pagination'], $matches)
                ) {
                    $pageCount = (int) $matches[1];
                }

                // Zoek een bruikbare omslag.
                $coverId = null;

                foreach (array_merge(
                    is_array($data['covers'] ?? null) ? $data['covers'] : [],
                    is_array($workData['covers'] ?? null) ? $workData['covers'] : []
                ) as $id) {
                    if (is_numeric($id) && (int) $id > 0) {
                        $coverId = (int) $id;
                        break;
                    }
                }

                $completeBookInfo = [
                    'title' => $data['title']
                        ?? $workData['title']
                        ?? '',

                    'authors' => $authorNames,

                    'publishedDate' => $data['publish_date']
                        ?? $data['copyright_date']
                        ?? '',

                    'pageCount' => is_numeric($pageCount) ? (int) $pageCount : 0,

                    'publishers' => is_array($data['publishers'] ?? null)
                        ? $data['publishers']
                        : [],

                    'series' => is_array($data['series'] ?? null)
                        ? $data['series']
                        : [],

                    'physicalFormat' => $data['physical_format'] ?? '',

                    'publishPlaces' => is_array($data['publish_places'] ?? null)
                        ? $data['publish_places']
                        : [],

                    'description' => is_string($description)
                        ? $description
                        : '',

                    'categories' => $subjects,

                    'canonicalVolumeLink' => !empty($data['key'])
                        ? 'https://openlibrary.org' . $data['key']
                        : 'https://openlibrary.org/isbn/' . $isbn,

                    'imageLinks' => $coverId !== null
                        ? [
                            'thumbnail' =>
                                'https://covers.openlibrary.org/b/id/'
                                . $coverId . '-M.jpg',
                        ]
                        : [],
                ];

                $cache->set($cacheKey, $completeBookInfo, 604800);
            }
            elseif (is_array($data) && empty($data['title'])) {
                $failureReason = 'Open Library returned no usable book title.';
            }
        }

        return [
            'volumeInfo' => is_array($completeBookInfo)
                && !empty($completeBookInfo['title'])
                    ? $completeBookInfo
                    : null,
            'failureReason' => $failureReason,
        ];
    }
}
