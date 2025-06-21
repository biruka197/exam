<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/8b5f9c6da8a655eec0b7ffc7efa63aac.html');