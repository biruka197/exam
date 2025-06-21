<?php
require_once 'config.php';
require_once 'fetchData.php';
require_once 'cleanAndBlockPopups.php';

// Function to get TV show details and similar shows
function getTvDetails($movieId)
{
    global $BASE_URL, $API_KEY, $TV_URL, $CACHE_DIR;

    $detailsCacheKey = "tv_details_$movieId";
    $similarCacheKey = "tv_similar_$movieId";
    $tvPageCacheKey = "tv_page_$movieId";

    $urls = [
        "$BASE_URL/tv/$movieId?api_key=$API_KEY&language=en-US",
        "$BASE_URL/tv/$movieId/similar?api_key=$API_KEY&language=en-US&page=1",


    ];

    $api = fetchData($urls, [$detailsCacheKey, $similarCacheKey]);
    $details = $api[0] ?? [];
    $similar = $api[1] ?? [];



    if (empty($details)) {
        echo json_encode(["error" => "TV show not found"], JSON_PRETTY_PRINT);
        exit;
    }

    $similarShows = [];

    if (!empty($similar['results'])) {
        foreach ($similar['results'] as $m) {
            $similarShows[] = [
                "orig_title" => $m['name'] ?? '',
                "image" => $m['poster_path'] ?? '',
                "id" => $m['id'] ?? 0,
                "link" => "watchtv",
            ];
        }
    }

    $tvPageUrl = $TV_URL . $movieId;
    $cachedFilePath = $CACHE_DIR . "/pages/" . md5($tvPageCacheKey) . ".html";
    $cacheValid = file_exists($cachedFilePath) && (time() - filemtime($cachedFilePath) < 3600); // 1 hour cache


    if (!$cacheValid) {
        if (!is_dir(dirname($cachedFilePath))) {
            mkdir(dirname($cachedFilePath), 0755, true);
        }

        $ch = curl_init($tvPageUrl);
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
            file_put_contents($cachedFilePath, "<!-- Failed to load TV show page: HTTP $httpCode -->");
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

// Serve the cleaned TV show page
echo file_get_contents('$cachedFilePath');
EOT;

    file_put_contents($proxyFilePath, $proxyContent);

    $proxyUrl = "/temp/proxy_$token.php";

    echo json_encode([
        "title" => $details['name'] ?? '',
        "poster_path" => $details['poster_path'] ?? '',
        "overview" => $details['overview'] ?? '',
        "release_date" => $details['first_air_date'] ?? '',
        "tvId" => $details['id'] ?? 0,
        "tvUrl" => $TV_URL . $movieId,
        "similar" => $similarShows,
        "cleanPageUrl" => $proxyUrl,
        "blocked_popup_page" => $proxyUrl,
        "direct_source" => file_exists($cachedFilePath),
        "seasons" => $details['seasons'] ?? 0,
        "last_episode_to_air" => $details['last_episode_to_air'] ?? '',

    ]);

}
?>