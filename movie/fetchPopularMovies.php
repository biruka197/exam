<?php
require_once 'config.php';
require_once 'fetchData.php';

// Function to fetch popular movies
function fetchPopularMovies($page, $query)
{
    global $BASE_URL, $API_KEY;

    // Validate required configurations
    if (empty($BASE_URL) || empty($API_KEY)) {
        echo json_encode(["error" => "Missing API configuration"], JSON_PRETTY_PRINT);
        exit;
    }

    // Validate page number
    $page = intval($page);
    if ($page <= 0) {
        echo json_encode(["error" => "Invalid page number"], JSON_PRETTY_PRINT);
        exit;
    }

    // API URLs
    $urls = [
        "$BASE_URL/movie/popular?api_key=$API_KEY&language=en-US&page=$page",
        "https://moviesapi.to/api/discover/movie?direction=desc&page=$page&query=i" . urlencode($query)
    ];

    // Fetch data from APIs
    $api = fetchData($urls);

    $tmdbMovies = $api[0] ?? [];
    $moviesAPI = $api[1] ?? [];
    $movies = $moviesAPI['data'] ?? [];
    //$moviesa = $moviesAPI['last_page'] ?? [];

    if (empty($movies)) {
        echo json_encode(["error" => "No movies found"], JSON_PRETTY_PRINT);
        exit;
    }

    // Collect detail URLs
    $detailUrls = [];

    foreach ($movies as $movie) {
        if (!isset($movie['tmdbid'])) continue;
        $detailUrls[] = "$BASE_URL/movie/{$movie['tmdbid']}?api_key=$API_KEY&language=en-US";
    }

    // Fetch movie details
    $details = fetchData($detailUrls);
    $results = [];

    foreach ($movies as $index => $movie) {
        if (!isset($movie['tmdbid']) || empty($details[$index])) continue;

        $detailsData = $details[$index];

        $results[] = [
            "id" => $movie['tmdbid'],
            "orig_title" => $movie['orig_title'] ?? '',
            "tmdbid" => $movie['tmdbid'],
            "year" => $movie['year'] ?? '',
            "quality" => $movie['quality'] ?? '',
            "image" => $detailsData['poster_path'] ?? '',
            "type" => $movie['type'] ?? '',
            "link" => 'watch',
            // "total_pages" => $moviesa ?? '',
            "overview" => $detailsData['overview'] ?? '',
            "release_date" => $detailsData['release_date'] ?? '',
        ];
    }

    // Return JSON response
    echo json_encode($results, JSON_PRETTY_PRINT);
}
?>
