<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned TV show page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/a4af3d19010bbe2e8f9c7b647f23ea2a.html');