<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned TV show page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/6d8d2e2fae10464d3e5398a97f97110f.html');