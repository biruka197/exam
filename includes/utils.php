<?php
/**
 * Shuffles the options of a given question array and updates the correct_answer key.
 *
 * @param array &$question The question array, passed by reference.
 */
function shuffleQuestionOptions(&$question) {
    // Check if options exist and is a non-empty array
    if (!isset($question['options']) || !is_array($question['options']) || empty($question['options'])) {
        error_log("Invalid or empty options array in question: " . json_encode($question));
        return; // Exit the function to prevent errors
    }

    $option_keys = array_keys($question['options']);
    $original_correct_answer_key = $question['correct_answer'];

    // Check if the correct answer key actually exists in the options
    if (!isset($question['options'][$original_correct_answer_key])) {
        error_log("Correct answer key '$original_correct_answer_key' not found in options for question: " . json_encode($question));
        return;
    }

    // Shuffle the keys of the options array
    shuffle($option_keys);

    $shuffled_options = [];
    $new_correct_answer_key = null; // To hold the new key for the correct answer

    // Rebuild the options array based on shuffled keys
    foreach ($option_keys as $new_key) {
        if (isset($question['options'][$new_key])) {
             $shuffled_options[$new_key] = $question['options'][$new_key];
             // If this shuffled key is the original correct answer, store it
             if ($new_key === $original_correct_answer_key) {
                 $new_correct_answer_key = $new_key;
             }
        } else {
            error_log("Key '$new_key' from shuffled keys not found in original options for question: " . json_encode($question));
        }
    }

    // Update the question's options with the shuffled array
    $question['options'] = $shuffled_options;

    // Update the correct_answer key if it was found and shuffled
    if ($new_correct_answer_key !== null) {
         $question['correct_answer'] = $new_correct_answer_key;
    } else {
        error_log("Could not determine new correct answer key after shuffling for question: " . json_encode($question));
    }
}