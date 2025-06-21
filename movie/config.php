<?php
$BASE_URL = "https://api.themoviedb.org/3";
$MOVIE_URL = "https://autoembed.pro/embed/movie/";
$TV_URL = "https://moviesapi.club/tv/";
$API_KEY = "9afdb4d9d68076a4bb2394ee1b0a6424"; // Replace with your API Key
$CACHE_DIR = __DIR__ . "/cache";
$CACHE_EXPIRY = 3600; // Cache expiry in seconds (1 hour)

// Create cache directory if it doesn't exist
if (!is_dir($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}
?>
