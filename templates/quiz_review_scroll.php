<?php
// This file is included from ajax_handlers.php, so $_SESSION is available.
?>
<div class="space-y-6">
    <div
        class="flex flex-col md:flex-row justify-between items-center gap-4 p-4 bg-white rounded-lg shadow sticky top-0 z-10">
        <div class="text-lg font-bold text-slate-800">
            Review Mode: <span class="text-green-600"><?php echo htmlspecialchars($_SESSION['exam_code']); ?></span>
        </div>
    <button type="button" onclick="showExitModal()" class="w-full mt-4 text-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-200">Exit Exam</button>
    </div>

    <?php foreach ($_SESSION['questions'] as $index => $question): ?>
        <div class="bg-white p-6 md:p-8 rounded-lg shadow-md">
            <p class="text-base font-semibold text-green-600 mb-2">Question <?php echo $index + 1; ?></p>
            <p class="text-lg text-slate-800 font-medium mb-4"><?php echo htmlspecialchars($question['question']); ?></p>

            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
                <p class="font-bold text-green-800">Correct Answer: <span
                        class="font-medium"><?php echo htmlspecialchars($question['options'][$question['correct_answer']] ?? 'N/A'); ?></span>
                </p>

                <button onclick="toggleExplanation(this)"
                    class="text-sm text-green-600 hover:underline mt-2 cursor-pointer">Show Explanation</button>

                <div class="review-explanation-wrapper hidden mt-2">
                    <p class="text-sm text-green-700"><strong>Explanation:</strong>
                        <span><?php echo htmlspecialchars($question['explanation'] ?? 'No explanation provided.'); ?></span>
                    </p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div id="exit-modal"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden transition-opacity duration-300 opacity-0">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
        <h3 class="text-lg font-medium text-slate-900 mb-2">Confirm Exit</h3>
        <p class="text-sm text-slate-600 mb-6">Are you sure you want to exit the exam? All your current progress will be
            lost.</p>
        <div class="flex gap-4">
            <button id="exit-modal-cancel-btn"
                class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Stay</button>
            <button id="exit-modal-confirm-btn"
                class="w-full rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Exit
                Exam</button>
        </div>
    </div>
</div>