<?php
// Ensure the user is an admin
if (!isset($_SESSION['admin_logged_in'])) {
    exit('You do not have permission to access this page.');
}

// Include our Gemini API function
// Make sure this path is correct for your file structure
require_once __DIR__ . '/../../includes/utils.php';

$ai_response = '';
$prompt = '';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    $prompt = trim($_POST['prompt']);

    if (!empty($prompt)) {
        // Call the Gemini API
        $responseJson = callGeminiAPI($prompt);
        $responseData = json_decode($responseJson, true);

        // Extract the AI's response text
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            // Handle potential errors from the API
            $ai_response = "Error: Could not get a valid response from the AI. Please check your API key and configuration.";
            if (isset($responseData['error'])) {
                 $ai_response .= "\nAPI Error: " . print_r($responseData['error'], true);
            }
        }
    } else {
        $ai_response = "Please enter a prompt.";
    }
}
?>

<div class="page-header">
    <h1>AI Assistant</h1>
    <p>Ask the AI anything. You can use it to generate exam questions, explain topics, or draft content.</p>
</div>

<div class="ai-assistant-container">
    <div class="card">
        <div class="card-header">
            <h3>Submit your Prompt</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="?page=ai_assistant">
                <div class="form-group">
                    <label for="prompt">Your Prompt:</label>
                    <textarea id="prompt" name="prompt" rows="5" class="form-control" placeholder="e.g., 'Create 5 multiple-choice questions about World War II'"><?php echo htmlspecialchars($prompt); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Generate Response</button>
            </form>
        </div>
    </div>

    <?php if (!empty($ai_response)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h3>AI Response</h3>
        </div>
        <div class="card-body">
            <div class="ai-response-box"><?php echo nl2br(htmlspecialchars($ai_response)); ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.ai-assistant-container .card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}
.ai-assistant-container .card-header {
    background-color: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
}
.ai-assistant-container .card-body {
    padding: 1.5rem;
}
.ai-response-box {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    padding: 1rem;
    min-height: 100px;
    white-space: pre-wrap; /* Ensures that the formatting is respected */
}
</style>