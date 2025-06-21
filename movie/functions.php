<?php
require_once 'config.php';

// Function to fetch data using cURL with multi-threading and caching
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

    // Process results and update cache
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

// Function to block popups and clean HTML content
function cleanAndBlockPopups($html)
{
    // ...existing code...

    // Return the cleaned HTML content
    return $html;
}

// Function to fetch popular movies
function fetchPopularMovies($page)
{
    global $BASE_URL, $API_KEY, $CACHE_DIR, $CACHE_EXPIRY;

    $cacheKeys = [
        "tmdb_popular_$page",
        "moviesapi_discover_$page"
    ];

    $urls = [
        "$BASE_URL/movie/popular?api_key=$API_KEY&language=en-US&page=$page",
        "https://moviesapi.club/api/discover/movie?direction=desc&page=$page"
    ];

    $api = fetchData($urls, $cacheKeys);
    $tmdbMovies = $api[0] ?? [];
    $moviesAPI = $api[1] ?? [];
    $movies = $moviesAPI['data'] ?? [];
    $results = [];

    if (empty($movies)) {
        echo json_encode(["error" => "No movies found"], JSON_PRETTY_PRINT);
        exit;
    }

    $detailUrls = [];
    $detailCacheKeys = [];

    foreach ($movies as $movie) {
        if (!isset($movie['tmdbid']))
            continue;

        $detailUrls[] = "$BASE_URL/movie/{$movie['tmdbid']}?api_key=$API_KEY&language=en-US";
        $detailCacheKeys[] = "movie_detail_{$movie['tmdbid']}";
    }

    $details = fetchData($detailUrls, $detailCacheKeys);

    foreach ($movies as $index => $movie) {
        if (!isset($movie['tmdbid']) || !isset($details[$index]))
            continue;

        $detailsData = $details[$index];

        if (empty($detailsData))
            continue;

        $results[] = [
            "id" => $movie['tmdbid'],
            "orig_title" => $movie['orig_title'] ?? '',
            "tmdbid" => $movie['tmdbid'],
            "year" => $movie['year'] ?? '',
            "quality" => $movie['quality'] ?? '',
            "image" => $detailsData['poster_path'] ?? '',
            "type" => $movie['type'] ?? '',
            "overview" => $detailsData['overview'] ?? '',
            "release_date" => $detailsData['release_date'] ?? '',
        ];
    }

    echo json_encode($results, JSON_PRETTY_PRINT);
}

function fetchPopularTv($page)
{
    global $BASE_URL, $API_KEY, $CACHE_DIR, $CACHE_EXPIRY;

    $cacheKeys = [
        "tmdb_popular_$page",
        "moviesapi_discover_$page"
    ];

    $urls = [
        "$BASE_URL/tv/popular?api_key=$API_KEY&language=en-US&page=$page",
        "https://moviesapi.club/api/discover/tv?direction=desc&page=$page"
    ];

    $api = fetchData($urls, $cacheKeys);
    $tmdbMovies = $api[0] ?? [];
    $moviesAPI = $api[1] ?? [];
    $movies = $moviesAPI['data'] ?? [];
    $results = [];

    if (empty($movies)) {
        echo json_encode(["error" => "No movies found"], JSON_PRETTY_PRINT);
        exit;
    }

    $detailUrls = [];
    $detailCacheKeys = [];

    foreach ($movies as $movie) {
        if (!isset($movie['tmdbid']))
            continue;

        $detailUrls[] = "$BASE_URL/tv/{$movie['tmdbid']}?api_key=$API_KEY&language=en-US";
        $detailCacheKeys[] = "movie_detail_{$movie['tmdbid']}";
    }

    $details = fetchData($detailUrls, $detailCacheKeys);

    foreach ($movies as $index => $movie) {
        if (!isset($movie['tmdbid']) || !isset($details[$index]))
            continue;

        $detailsData = $details[$index];

        if (empty($detailsData))
            continue;

        if (isset($detailsData['number_of_seasons'])) {
            $season = ($detailsData['number_of_seasons'] === 1) ? 'Season 1' : 'Season 1 upto ' . $detailsData['number_of_seasons'];
        } else {
            $season = 'Season 11';
        }  

        $results[] = [
            "id" => $movie['tmdbid'],
            "orig_title" => $movie['orig_title'] ?? '',
            "tmdbid" => $movie['tmdbid'],
            "year" => $movie['year'] ?? '',
            "quality" =>$season,
            "image" => $detailsData['poster_path'] ?? '',
            "type" => $movie['type'] ?? '',
            "overview" => $detailsData['overview'] ?? '',
            "release_date" => $detailsData['release_date'] ?? '',
        ];
    }

    echo json_encode($results, JSON_PRETTY_PRINT);
}

// Function to fetch popular movies

// Function to get movie details and similar movies

function getMovieDetails($movieId)
{
    global $BASE_URL, $API_KEY, $MOVIE_URL, $CACHE_DIR;

    $detailsCacheKey = "movie_details_$movieId";
    $similarCacheKey = "movie_similar_$movieId";
    $moviePageCacheKey = "movie_page_$movieId";

    $urls = [
        "$BASE_URL/movie/$movieId?api_key=$API_KEY&language=en-US",
        "$BASE_URL/movie/$movieId/similar?api_key=$API_KEY&language=en-US&page=1"
    ];

    $api = fetchData($urls, [$detailsCacheKey, $similarCacheKey]);
    $details = $api[0] ?? [];
    $similar = $api[1] ?? [];

    if (empty($details)) {
        echo json_encode(["error" => "Movie not found"], JSON_PRETTY_PRINT);
        exit;
    }

    $similarMovies = [];

    if (!empty($similar['results'])) {
        foreach ($similar['results'] as $m) {
            $similarMovies[] = [
                "orig_title" => $m['title'] ?? '',
                "image" => $m['poster_path'] ?? '',
                "id" => $m['id'] ?? 0,
            ];
        }
    }

    $moviePageUrl = $MOVIE_URL. $movieId;
    $cachedFilePath = $CACHE_DIR . "/pages/" . md5($moviePageCacheKey) . ".html";
    $cacheValid = file_exists($cachedFilePath) && (time() - filemtime($cachedFilePath) < 3600); // 1 hour cache

    if (!$cacheValid) {
        if (!is_dir(dirname($cachedFilePath))) {
            mkdir(dirname($cachedFilePath), 0755, true);
        }

        $ch = curl_init($moviePageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $htmlContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($htmlContent && $httpCode == 200) {
            $cleanedContent = cleanAndBlockPopups($htmlContent);
            file_put_contents($cachedFilePath, $cleanedContent);
        } else {
            file_put_contents($cachedFilePath, "<!-- Failed to load movie page: HTTP $httpCode -->");
        }
    }

    $tempDir = __DIR__ . "/temp";
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $token = bin2hex(random_bytes(16));
    $proxyFilePath = $tempDir . "/proxy_$token.php";

    $proxyContent = <<<EOT
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('$cachedFilePath');
EOT;

    file_put_contents($proxyFilePath, $proxyContent);

    $proxyUrl = "/temp/proxy_$token.php";

    echo json_encode([
        "title" => $details['title'] ?? '',
        "poster_path" => $details['poster_path'] ?? '',
        "overview" => $details['overview'] ?? '',
        "release_date" => $details['release_date'] ?? '',
        "movieId" => $details['id'] ?? 0,
        "movieUrl" => $MOVIE_URL . $movieId,
        "similar" => $similarMovies,
        "cleanPageUrl" => $proxyUrl,
        "blocked_popup_page" => $proxyUrl,
        "direct_source" => file_exists($cachedFilePath)
    ]);
}

function getTvDetails($movieId)
{
    global $BASE_URL, $API_KEY, $TV_URL, $CACHE_DIR;

    $detailsCacheKey = "movie_details_$movieId";
    $similarCacheKey = "movie_similar_$movieId";
    $moviePageCacheKey = "movie_page_$movieId";

    $urls = [
        "$BASE_URL/tv/$movieId?api_key=$API_KEY&language=en-US",
        "$BASE_URL/tv/$movieId/similar?api_key=$API_KEY&language=en-US&page=1"
    ];

    $api = fetchData($urls, [$detailsCacheKey, $similarCacheKey]);
    $details = $api[0] ?? [];
    $similar = $api[1] ?? [];

    if (empty($details)) {
        echo json_encode(["error" => "Movie not found"], JSON_PRETTY_PRINT);
        exit;
    }

    $similarMovies = [];

    if (!empty($similar['results'])) {
        foreach ($similar['results'] as $m) {
            $similarMovies[] = [
                "orig_title" => $m['title'] ?? '',
                "image" => $m['poster_path'] ?? '',
                "id" => $m['id'] ?? 0,
            ];
        }
    }

    $moviePageUrl = $TV_URL . $movieId;
    $cachedFilePath = $CACHE_DIR . "/pages/" . md5($moviePageCacheKey) . ".html";
    $cacheValid = file_exists($cachedFilePath) && (time() - filemtime($cachedFilePath) < 3600); // 1 hour cache

    if (!$cacheValid) {
        if (!is_dir(dirname($cachedFilePath))) {
            mkdir(dirname($cachedFilePath), 0755, true);
        }

        $ch = curl_init($moviePageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $htmlContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($htmlContent && $httpCode == 200) {
            $cleanedContent = cleanAndBlockPopups($htmlContent);
            file_put_contents($cachedFilePath, $cleanedContent);
        } else {
            file_put_contents($cachedFilePath, "<!-- Failed to load movie page: HTTP $httpCode -->");
        }
    }

    $tempDir = __DIR__ . "/temp";
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $token = bin2hex(random_bytes(16));
    $proxyFilePath = $tempDir . "/proxy_$token.php";

    $proxyContent = <<<EOT
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('$cachedFilePath');
EOT;

    file_put_contents($proxyFilePath, $proxyContent);

    $proxyUrl = "/temp/proxy_$token.php";

    echo json_encode([
        "title" => $details['title'] ?? '',
        "poster_path" => $details['poster_path'] ?? '',
        "overview" => $details['overview'] ?? '',
        "release_date" => $details['release_date'] ?? '',
        "movieId" => $details['id'] ?? 0,
        "movieUrl" => $TV_URL . $movieId,
        "similar" => $similarMovies,
        "cleanPageUrl" => $proxyUrl,
        "blocked_popup_page" => $proxyUrl,
        "direct_source" => file_exists($cachedFilePath)
    ]);
}

