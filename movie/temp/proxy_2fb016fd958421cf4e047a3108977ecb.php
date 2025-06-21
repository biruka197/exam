<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned TV show page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/81c1fe74b2b00c40fa4a5dab571c9b2d.html');