<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned TV show page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/d20bcffbc5e1cc0eaad2a9d45304bb12.html');