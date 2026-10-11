<?php

if (!function_exists('mirthe_bookblock_get_volume_info')) {
    function mirthe_bookblock_get_volume_info(string $isbn): array
    {
        $cache = kirby()->cache('mirthe.bookblock');
        $cacheKey = 'google-books-' . $isbn;
        $completeBookInfo = $cache->get($cacheKey);
        $failureReason = 'Google Books returned no matching volume.';

        if ($completeBookInfo === null) {
            $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . urlencode($isbn);
            $apiKey = option('googlebooks.apiKey');
            if (!empty($apiKey)) {
                $url .= '&key=' . urlencode($apiKey);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => kirby()->site()->title(),
            ]);
            $rawData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($rawData === false) {
                $failureReason = 'Google Books request failed (HTTP ' . $httpCode . '): ' . $curlError;
            } elseif ($httpCode < 200 || $httpCode >= 300) {
                $failureReason = 'Google Books returned HTTP ' . $httpCode;
            }

            $completeBookInfo = json_decode($rawData, true);

            if (!empty($completeBookInfo['error']['message'])) {
                $failureReason = 'Google Books API error (HTTP ' . $httpCode . '): ' . $completeBookInfo['error']['message'];
            }

            if (is_array($completeBookInfo) && !empty($completeBookInfo['items'])) {
                $cache->set($cacheKey, $completeBookInfo, 604800);
            }
        }

        $volumeInfo = $completeBookInfo['items'][0]['volumeInfo'] ?? null;

        return [
            'volumeInfo' => is_array($volumeInfo) ? $volumeInfo : null,
            'failureReason' => $failureReason
        ];
    }
}