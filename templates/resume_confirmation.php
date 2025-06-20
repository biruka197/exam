<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Exam - Kuru Exam</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800%3B900&family=Noto+Sans%3Awght%40400%3B500%3B600%3B700%3B800%3B900">
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <main id="layout-content-container" class="w-full max-w-lg transition-opacity duration-300">
        <div id="confirmation-dialog" class="bg-white p-8 rounded-xl shadow-lg text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Welcome Back!</h1>
            <p class="text-slate-600 mb-4">You have an exam in progress for course <strong class="text-slate-800"><?php echo htmlspecialchars($_SESSION['exam_code'] ?? 'N/A'); ?></strong>.</p>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-6">
                <p class="text-sm text-slate-700">Your progress: <strong class="text-slate-900"><?php echo count(array_filter($_SESSION['answers'] ?? [])); ?> of <?php echo $_SESSION['num_questions'] ?? 'N/A'; ?></strong> questions answered.</p>
            </div>
            <p class="mb-8 text-slate-600">Do you want to continue where you left off?</p>
            <div class="flex gap-4 justify-center">
                <button onclick="startOver()" class="w-full rounded-md border border-slate-300 bg-white px-6 py-3 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-all">Start Over</button>
                <button onclick="resumeQuiz()" class="w-full rounded-md border border-transparent bg-green-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-green-700 transition-all">Yes, Continue</button>
            </div>
        </div>
    </main>
    <script src="assets/scripts.js"></script>
    <script>
        function startOver() {
            if (confirm("Are you sure? Your previous exam progress will be lost.")) {
                // The main container ID is now layout-content-container
                document.getElementById('layout-content-container').style.opacity = '0';
                sendAjaxRequest('exit_exam', {});
            }
        }

        function resumeQuiz() {
            const mainContainer = document.getElementById('layout-content-container');
            mainContainer.style.opacity = '0';
            
            setTimeout(() => {
                 // Set a loading message while waiting for the quiz content
                 mainContainer.innerHTML = '<div class="w-full max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg text-center text-slate-600">Loading your exam...</div>';
                 mainContainer.style.opacity = '1';
                 // Use 'navigate_to_question' to fetch the quiz HTML for the current question.
                 sendAjaxRequest('navigate_to_question', { navigate_to: <?php echo $_SESSION['current_question'] ?? 0; ?> });
            }, 300); // Wait for fade out
        }
    </script>
</body>
</html>