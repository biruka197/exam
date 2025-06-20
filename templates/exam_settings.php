<div class="bg-white p-6 md:p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-slate-800 mb-2">Examination Settings</h2>
    <p class="text-sm text-slate-500 mb-6">File: <?php echo $loaded_exam_file_display; ?></p>

    <form id="settings-form" onsubmit="submitSettings(event)" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="num_questions" class="block text-sm font-medium text-slate-700">Number of Questions</label>
                <input type="number" id="num_questions" name="num_questions"
                    value="<?php echo min($total_questions_in_file, $total_questions_in_file); ?>" min="1"
                    max="<?php echo $total_questions_in_file; ?>" required
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm">
            </div>
            <div>
                <label for="range_start" class="block text-sm font-medium text-slate-700">Question Range Start</label>
                <input type="number" id="range_start" name="range_start" value="1" min="1"
                    max="<?php echo $total_questions_in_file; ?>" required
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm">
            </div>
            <div>
                <label for="range_end" class="block text-sm font-medium text-slate-700">Question Range End</label>
                <input type="number" id="range_end" name="range_end" value="<?php echo $total_questions_in_file; ?>"
                    min="1" max="<?php echo $total_questions_in_file; ?>" required
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm">
                <p class="text-xs text-slate-500 mt-1">Total questions in file: <?php echo $total_questions_in_file; ?>
                </p>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-700">Question Order</span>
            <div class="flex items-center gap-2">
                <label for="order-random" class="text-sm">Random</label>
                <input type="checkbox" id="order-random" name="order" value="random"
                    class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 pt-4">
            <button type="button" onclick="window.location.reload()"
                class="w-full md:w-auto justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50">Back
                to Home</button>
            <button type="submit"
                class="w-full md:w-auto flex-grow justify-center rounded-md border border-transparent bg-sky-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-sky-700">Begin
                Examination</button>
        </div>
    </form>
</div>