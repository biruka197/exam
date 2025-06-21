<?php
require_once 'config.php';
require_once 'fetchData.php';

// Function to fetch popular TV shows

function filterSeasons($seasons)
{
    return array_values(array_filter($seasons, function ($season) {
        return $season['season_number'] !== 0;
    }));
}
function fetchPopularTv($page, $query)
{
    $q = str_replace(' ', '%', $query);
    global $BASE_URL, $API_KEY, $CACHE_DIR, $CACHE_EXPIRY;

    $cacheKeys = [
        "tmdb_popular_$page",
        "moviesapi_discover_$page"
    ];

    $urls = [
        "$BASE_URL/tv/popular?api_key=$API_KEY&language=en-US&page=$page",
        "https://moviesapi.club/api/discover/tv?direction=desc&page=$page&query=$q"
    ];

    $api = fetchData($urls, $cacheKeys);
    $api = fetchData($urls);
    $tmdbMovies = $api[0] ?? [];
    $moviesAPI = $api[1] ?? [];
    $movies = $moviesAPI['data'] ?? [];
    $moviesa = $moviesAPI['last_page'] ?? [];
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
    $details = fetchData($detailUrls);

    foreach ($movies as $index => $movie) {
        if (!isset($movie['tmdbid']) || !isset($details[$index]))
            continue;

        $detailsData = $details[$index];

        if (empty($detailsData))
            continue;

        $data = $detailsData['seasons'];
        $arr = $detailsData['seasons'][0]['season_number'];

        $season = '';
        if ($arr == 0) {
            unset($data[0]);
        }
        $num = count($data);

          $season = ($num === 1) ? 'Season 1' : 'Season 1 upto ' . $num;
        //$season = "Season " . $num;
        $results[] = [
            "id" => $movie['tmdbid'],
            "orig_title" => $movie['orig_title'] ?? '',
            "tmdbid" => $movie['tmdbid'],
            "year" => $movie['year'] ?? '',
            "quality" => $season,
            "image" => $detailsData['poster_path'] ?? '',
            "type" => $movie['type'] ?? '',
            "total_pages" => $moviesa ?? '',
            "link" => 'watchtv',
            "overview" => $detailsData['overview'] ?? '',
            "release_date" => $detailsData['release_date'] ?? '',
        ];
    }

    echo json_encode($results, JSON_PRETTY_PRINT);
}
?>