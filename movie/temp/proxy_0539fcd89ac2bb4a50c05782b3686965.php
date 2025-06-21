<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned TV show page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/f2fbc5a83d5b91331921d462af06c1cb.html');