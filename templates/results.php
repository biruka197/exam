<div>
    <div class="bg-white p-6 md:p-8 rounded-lg shadow-md text-center space-y-4">
    <h2 class="text-3xl font-bold text-slate-800">Exam Complete!</h2>
    <p class="text-slate-600">You scored:</p>
    <p class="text-5xl font-bold text-sky-600"><?php echo $score; ?> / <?php echo $total; ?></p>
    <div class="w-full bg-slate-200 rounded-full h-4">
        <div class="bg-sky-600 h-4 rounded-full text-xs font-medium text-sky-100 text-center p-0.5 leading-none"
            style="width: <?php echo ($total > 0) ? round(($score / $total) * 100, 2) : 0; ?>%">
            <?php echo ($total > 0) ? round(($score / $total) * 100, 2) : 0; ?>%
        </div>
    </div>
</div>

<div class="mt-8">
    <h3 class="text-xl font-bold text-slate-800 mb-4">Review Incorrect Answers</h3>
    <?php if (empty($incorrect_questions)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg">
            <p class="font-bold">Excellent!</p>
            <p>You answered all questions correctly.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($incorrect_questions as $q_review): ?>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p class="text-sm font-medium text-slate-800 mb-2"><?php echo htmlspecialchars($q_review['question']); ?>
                    </p>
                    <?php
                    $original_index = array_search($q_review['question'], array_column($_SESSION['questions'], 'question'));
                    $user_answer_key = ($original_index !== false && isset($answers[$original_index])) ? $answers[$original_index] : null;
                    $user_answer_text = ($user_answer_key !== null && isset($q_review['options'][$user_answer_key])) ? $q_review['options'][$user_answer_key] : 'Not answered';
                    ?>
                    <p class="text-sm p-2 rounded bg-red-100 text-red-800"><strong>Your Answer:</strong>
                        <?php echo htmlspecialchars($user_answer_text); ?></p>
                    <p class="text-sm p-2 mt-2 rounded bg-green-100 text-green-800"><strong>Correct Answer:</strong>
                        <?php echo htmlspecialchars($q_review['options'][$q_review['correct_answer']] ?? 'N/A'); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div>
    <div class="flex flex-col md:flex-row gap-4 pt-8">
        <button type="button" onclick="restartQuiz()"
            class="w-full md:w-auto justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50">Restart
            Quiz</button>
        <?php if (!empty($incorrect_questions)): ?>
            <button type="button" onclick="retakeIncorrect()"
                class="w-full md:w-auto flex-grow justify-center rounded-md border border-transparent bg-sky-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-sky-700">Retake
                Incorrect Answers</button>
        <?php endif; ?>
    </div>
</div>
</div>