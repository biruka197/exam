<?php
// Generates a unique course ID
function generateCourseId($course_name) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    return $prefix . '_' . time();
}

// Generates a unique exam code
function generateExamCode($course_name, $pdo) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    
    $stmt = $pdo->prepare("SELECT exam_code FROM course WHERE exam_code LIKE ? ORDER BY exam_code DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_code = $stmt->fetchColumn();
    
    $number = $last_code ? intval(substr($last_code, strlen($prefix))) + 1 : 1;
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}
 
function callGeminiAPI($prompt) {
    // Ensure the API key is defined in your main config file
    if (!defined('GEMINI_API_KEY')) {
        return json_encode(['error' => 'API key is not configured.']);
    }

    $apiKey = GEMINI_API_KEY;
    $model = 'gemini-1.5-flash-latest'; // Or another model you prefer
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ]
    ];

    $jsonData = json_encode($data);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        return json_encode(['error' => 'API call failed with status code: ' . $httpcode, 'response' => $response]);
    }

    return $response;
}
?>