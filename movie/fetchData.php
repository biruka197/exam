<?php
require_once 'config.php';

// Function to fetch data using cURL with multi-threading
function fetchData($urls, $cacheKeys = [])
{
    global $CACHE_DIR, $CACHE_EXPIRY;

    $multiCurl = [];
    $result = [];
    $urlsToFetch = [];
    $urlMap = [];

    // Check cache first if cache keys provided
    if (!empty($cacheKeys)) {
        foreach ($urls as $i => $url) {
            $cacheFile = $CACHE_DIR . '/' . md5($cacheKeys[$i]) . '.json';

            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $CACHE_EXPIRY)) {
                $result[$i] = json_decode(file_get_contents($cacheFile), true);
            } else {
                $urlsToFetch[] = $url;
                $urlMap[count($urlsToFetch) - 1] = $i;
            }
        }
    } else {
        $urlsToFetch = $urls;
        $urlMap = array_flip(range(0, count($urls) - 1));
    }

    // If all results from cache, return early
    if (empty($urlsToFetch)) {
        return $result;
    }

    // Set up multi cURL for remaining URLs
    $mh = curl_multi_init();

    foreach ($urlsToFetch as $i => $url) {
        $multiCurl[$i] = curl_init();
        curl_setopt($multiCurl[$i], CURLOPT_URL, $url);
        curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($multiCurl[$i], CURLOPT_TIMEOUT, 5); // Add timeout
        curl_setopt($multiCurl[$i], CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout
        curl_setopt($multiCurl[$i], CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($multiCurl[$i], CURLOPT_ENCODING, ""); // Accept gzip encoding
        curl_multi_add_handle($mh, $multiCurl[$i]);
    }

    // Execute multi cURL with better error handling
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh); // This helps prevent CPU hogging
    } while ($running > 0);

    // Process results
    foreach ($multiCurl as $i => $ch) {
        $originalIndex = $urlMap[$i];
        $content = curl_multi_getcontent($ch);

        if (!empty($content)) {
            $result[$originalIndex] = json_decode($content, true);

            // Cache the result if cache keys provided
            if (!empty($cacheKeys)) {
                $cacheFile = $CACHE_DIR . '/' . md5($cacheKeys[$originalIndex]) . '.json';
                file_put_contents($cacheFile, $content);
            }
        } else {
            // Handle failed requests
            $result[$originalIndex] = null;
        }

        curl_multi_remove_handle($mh, $ch);
    }

    curl_multi_close($mh);
    return $result;
}
?>
