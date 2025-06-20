<div class="space-y-4">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4 bg-white rounded-lg shadow">
        <div class="text-lg font-bold text-slate-800">
            Exam: <span class="text-green-600"><?php echo htmlspecialchars($current_exam_code); ?></span>
        </div>
        <div class="flex items-center gap-4">
            <div id="timer" class="text-lg font-semibold tabular-nums px-3 py-1 bg-slate-100 text-slate-700 rounded-md">
                <?php echo $timer_on ? gmdate("i:s", $remaining_time) : '--:--'; ?>
            </div>
            <label for="timer-toggle" class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" id="timer-toggle" class="sr-only peer" onchange="toggleTimer()" <?php echo $timer_on ? 'checked' : ''; ?>>
              <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-green-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
            </label>
        </div>
    </div>

    <div>
        <div class="flex justify-between mb-1">
            <span class="text-base font-medium text-slate-700">Progress</span>
            <span id="progress-text" class="text-sm font-medium text-slate-700"><?php echo count(array_filter($_SESSION['answers'] ?? [])) . ' of ' . $_SESSION['num_questions']; ?> Answered</span>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-2.5">
            <div id="progress-bar-fill" class="bg-green-600 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo ($_SESSION['num_questions'] > 0) ? (count(array_filter($_SESSION['answers'] ?? [])) / $_SESSION['num_questions']) * 100 : 0; ?>%"></div>
        </div>
    </div>
    
    <div id="question-box" class="bg-white p-6 md:p-8 rounded-lg shadow-md transition-opacity duration-300">
        <p id="question-header" class="text-base font-semibold text-green-600 mb-2">Question <?php echo $current_question_index + 1; ?> of <?php echo $_SESSION['num_questions']; ?></p>
        <p id="question-text" class="text-lg text-slate-800 font-medium mb-6"><?php echo htmlspecialchars($question['question']); ?></p>

        <form id="quiz-form" onsubmit="submitAnswer(event)" class="space-y-3">
            <div id="options-container">
                <?php foreach ($question['options'] as $key => $option): ?>
                    <label class="flex items-center p-4 rounded-lg border border-slate-200 has-[:checked]:bg-green-50 has-[:checked]:border-green-500 cursor-pointer transition-all">
                        <input type="radio" name="option" value="<?php echo htmlspecialchars($key); ?>" class="h-4 w-4 text-green-600 border-slate-300 focus:ring-green-500" <?php echo (isset($_SESSION['answers'][$current_question_index]) && $_SESSION['answers'][$current_question_index] === $key) ? 'checked' : ''; ?>>
                        <span class="ml-3 text-sm font-medium text-slate-700"><?php echo htmlspecialchars($option); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <div id="answer-box-container" class="answer-box <?php echo $show_answer ? 'show' : ''; ?> bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg transition-all max-h-0 opacity-0 overflow-hidden [&.show]:max-h-60 [&.show]:opacity-100">
        <p class="font-bold text-green-800">Correct Answer: <span id="correct-answer-text" class="font-medium"><?php echo htmlspecialchars($question['options'][$question['correct_answer']] ?? 'N/A'); ?></span></p>
        <p class="text-sm text-green-700 mt-2"><strong>Explanation:</strong> <span id="explanation-text"><?php echo htmlspecialchars($question['explanation'] ?? 'No explanation provided.'); ?></span></p>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-4">
        <button id="toggle-answer-btn" type="button" onclick="toggleAnswer()" class="toggle-answer w-full md:w-auto justify-center rounded-md border border-slate-300 bg-yellow-400 px-4 py-2 text-sm font-medium text-yellow-900 shadow-sm hover:bg-yellow-500"><?php echo $show_answer ? 'Hide Answer' : 'Show Answer'; ?></button>
        <div class="flex gap-4 w-full md:w-auto">
            <button id="prev-btn" type="button" onclick="navigateToQuestion(<?php echo $current_question_index - 1; ?>)" class="w-full justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" <?php echo $current_question_index === 0 ? 'disabled' : ''; ?>>Previous</button>
            <button id="next-btn" type="submit" form="quiz-form" class="w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700">Next</button>
        </div>
    </div>
   <button type="button" onclick="exitExam(); alert('You are about to exit the exam.')" class="w-full mt-4 text-center rounded-md border border-transparent bg-red-100 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-200">Exit Exam</button>

</div>