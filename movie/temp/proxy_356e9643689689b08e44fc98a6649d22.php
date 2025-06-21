<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=UTF-8");

// Serve the cleaned movie page
echo file_get_contents('C:\xampphttp\htdocs\backend/cache/pages/a95633ad3a8b0e1254a13566d417da9c.html');