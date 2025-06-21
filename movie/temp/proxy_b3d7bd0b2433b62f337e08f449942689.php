<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/7925a1845e3e5bfc43c4bd3288e9aed3.html');