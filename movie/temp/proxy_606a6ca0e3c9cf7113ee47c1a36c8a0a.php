<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/62d93c485efff201b466f5e135e1bbe7.html');