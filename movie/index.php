<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Configuration
require_once 'config.php';
require_once 'fetchData.php';
require_once 'cleanAndBlockPopups.php';
require_once 'fetchPopularMovies.php';
require_once 'fetchPopularTv.php';
require_once 'getMovieDetails.php';
require_once 'getTvDetails.php';

// Route handling
$route = $_GET['route'] ?? '';

if ($route == "popular-movies" && isset($_GET['page']) && isset($_GET['query'])) {
    fetchPopularMovies(intval($_GET['page']), $_GET['query']); // ✅ Fixed function call
} elseif ($route == "movie" && isset($_GET['id'])) {
    getMovieDetails(intval($_GET['id']));
} elseif ($route == "tv" && isset($_GET['page']) && isset($_GET['query'])) {
    fetchPopularTv(intval($_GET['page']), $_GET['query']); // ✅ Fixed function call
} elseif ($route == "watch-tv" && isset($_GET['id'])) {
    getTvDetails(intval($_GET['id']));
} else {
    echo json_encode(["error" => "Invalid route"]);
}
?>