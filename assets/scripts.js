function filterCourses() {
  const input = document.getElementById("course-search-input");
  const filter = input.value.toLowerCase();
  const courseContainer = document.getElementById("course-grid-container");
  const courses = courseContainer.getElementsByClassName("course-card-item");
  const noResultsMessage = document.getElementById("no-search-results");

  let visibleCount = 0;

  // Loop through all course cards, and hide those who don't match the search query
  for (let i = 0; i < courses.length; i++) {
    const titleElement = courses[i].querySelector(".course-title");
    if (titleElement) {
      const title = titleElement.textContent || titleElement.innerText;
      if (title.toLowerCase().indexOf(filter) > -1) {
        courses[i].style.display = "";
        visibleCount++;
      } else {
        courses[i].style.display = "none";
      }
    }
  }

  // Show or hide the "no results" message
  if (noResultsMessage) {
    if (visibleCount === 0) {
      noResultsMessage.style.display = "block";
    } else {
      noResultsMessage.style.display = "none";
    }
  }
}

let timeLeft = 0; // Stores remaining time in seconds
let timerOn = true; // Flag to indicate if the timer is running
let timerInterval; // Holds the interval ID for the timer

// Function to update the timer display
function updateTimer() {
  const timer = document.getElementById("timer");
  if (!timer) return; // Exit if timer element is not found

  if (!timerOn) {
    // If timer is off, display static '--:--'
    timer.textContent = "--:--";
    return;
  }

  if (timeLeft <= 0) {
    // If time runs out, stop the timer and automatically submit
    clearInterval(timerInterval);
    timer.textContent = "00:00";
    const quizForm = document.getElementById("quiz-form");
    if (quizForm) {
      submitAnswer(new Event("submit")); // Trigger form submission
    }
    return;
  }

  // Calculate minutes and seconds
  let minutes = Math.floor(timeLeft / 60);
  let seconds = timeLeft % 60;
  // Format and display the time
  timer.textContent = `${minutes.toString().padStart(2, "0")}:${seconds
    .toString()
    .padStart(2, "0")}`;

  timeLeft--; // Decrease time left by 1 second
}

// Function to start or resume the timer
function startTimer() {
  if (timerInterval) clearInterval(timerInterval); // Clear any existing timer
  if (typeof timeLeft !== "number" || isNaN(timeLeft) || timeLeft < 0) {
    timeLeft = 0;
  }
  timerInterval = setInterval(updateTimer, 1000);
}

// Function to attach click listeners to option divs
function attachOptionClickListeners() {
  document.querySelectorAll(".option").forEach((optionDiv) => {
    optionDiv.addEventListener("click", function () {
      const radioButton = this.querySelector('input[type="radio"]');
      if (radioButton) {
        radioButton.checked = true;
        // Add a visual indicator class to the selected option div
        document
          .querySelectorAll(".option")
          .forEach((div) => div.classList.remove("selected"));
        this.classList.add("selected");
      }
    });
  });
}

// Central function to handle AJAX requests and update the UI
// async function sendAjaxRequest(action, bodyParams) {
//     const container = document.getElementById('main-container');
//     container.classList.add('loading');

//     try {
//         const formData = new URLSearchParams(bodyParams);
//         formData.append('action', action);

//         const response = await fetch('index.php', {
//             method: 'POST',
//             headers: { 'X-Requested-With': 'XMLHttpRequest' },
//             body: formData
//         });

//         if (!response.ok) {
//             throw new Error(`Network response was not ok: ${response.statusText}`);
//         }

//         const data = await response.json();

//         container.classList.remove('loading');

//         if (data.redirect) {
//             window.location.href = data.redirect;
//             return;
//         }

//         if (data.success) {
//             // Determine the correct container to update
//             const targetContainer = (action === 'select_course') ? document.getElementById('exam-list-container') : container;

//             if (data.html) {
//                 targetContainer.innerHTML = data.html;
//                 // Only add footer if we are replacing the whole container's content
//                 if (action !== 'select_course') {
//                    targetContainer.innerHTML += '<div class="footer">© 2025 Kuru Exam</div>';
//                 }
//             }

//             // Handle timer updates
//             if (data.remaining_time !== undefined) {
//                 timeLeft = data.remaining_time;
//                 timerOn = data.timer_on !== false;
//                 if (timerOn) {
//                     startTimer();
//                 } else {
//                     clearInterval(timerInterval);
//                     const timer = document.getElementById('timer');
//                     if (timer) timer.textContent = '--:--';
//                 }
//             }

//             // Handle script execution
//             if (data.script === 'attachOptionClickListeners();') {
//                 attachOptionClickListeners();
//             }
//         } else {
//             // Display error message
//             const errorContainer = (action === 'select_course') ? document.getElementById('exam-list-container') : container;
//             errorContainer.innerHTML = `<p class="error">${data.error || 'An unknown error occurred.'}</p>`;
//         }
//     } catch (error) {
//         console.error('Fetch failed:', error);
//         container.classList.remove('loading');
//         container.innerHTML = `<p class="error">An error occurred while communicating with the server: ${error.message}</p>`;
//     }
// }
// ... (all the existing functions like updateTimer, startTimer, etc. remain the same) ...

// The central AJAX function is the only one that needs a change.

const confirmBeforeUnload = (event) => {
    // Standard way to trigger the browser's native confirmation prompt.
    event.preventDefault();
    // Required by modern browsers.
    event.returnValue = '';
    return '';
};

// --- EXISTING FUNCTIONS (some are now updated) ---
// ... (filterCourses, updateTimer, startTimer, attachOptionClickListeners are unchanged) ...

async function sendAjaxRequest(action, bodyParams) {
    const contentContainer = document.getElementById('layout-content-container');
    const mainContentArea = document.getElementById('main-content-area');
    document.body.classList.add('cursor-wait');

    // --- ADDED: Logic to add/remove the page reload listener ---
    // If the action will start or continue a quiz, add the listener.
    if (action === 'submit_settings' || action === 'navigate_to_question' || action === 'retake_incorrect') {
        window.addEventListener('beforeunload', confirmBeforeUnload);
    } 
    // If the action will end the quiz, remove the listener.
    else if (action === 'exit_exam' || action === 'restart_quiz') {
         window.removeEventListener('beforeunload', confirmBeforeUnload);
    }

    try {
        const formData = new URLSearchParams(bodyParams);
        formData.append('action', action);

        const response = await fetch('index.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);

        const data = await response.json();
        
        if (data.redirect) {
            window.removeEventListener('beforeunload', confirmBeforeUnload);
            window.location.href = data.redirect;
            return;
        }

        if (data.success) {
            if (action === 'select_course') {
                const examListContainer = document.getElementById('exam-list-container');
                if (examListContainer) {
                    if (mainContentArea) mainContentArea.style.display = 'none';
                    examListContainer.innerHTML = data.html;
                    const animatedElement = examListContainer.querySelector('.exam-list-animated');
                    if (animatedElement) {
                        setTimeout(() => {
                            animatedElement.classList.remove('opacity-0', 'translate-y-4');
                        }, 10);
                    }
                }
            } else if (data.html) {
                if (contentContainer) contentContainer.innerHTML = data.html;
                // --- ADDED: Remove listener if the results page is now showing ---
                // The results page has a specific H2 title we can check for.
                if (contentContainer.querySelector('h2')?.textContent.includes('Exam Complete!')) {
                    window.removeEventListener('beforeunload', confirmBeforeUnload);
                }
            }

            if (data.remaining_time !== undefined) {
                timeLeft = data.remaining_time;
                timerOn = data.timer_on !== false;
                if (timerOn) startTimer(); else clearInterval(timerInterval);
            }

            if (data.script === 'attachOptionClickListeners();') {
                attachOptionClickListeners();
            }
        } else {
            if (contentContainer) contentContainer.innerHTML = `<div class="m-4 p-4 text-red-800 bg-red-100 border border-red-300 rounded-lg">${data.error || 'An unknown error occurred.'}</div>`;
        }
    } catch (error) {
        console.error('Fetch failed:', error);
        if (contentContainer) contentContainer.innerHTML = `<div class="m-4 p-4 text-red-800 bg-red-100 border border-red-300 rounded-lg">An error occurred: ${error.message}</div>`;
    } finally {
        document.body.classList.remove('cursor-wait');
    }
}

// ... (selectCourse, proceedToExam, submitSettings are unchanged) ...

// --- UPDATED: Confirmation added before final submission ---
async function submitAnswer(event) {
    event.preventDefault();

    const questionHeaderEl = document.getElementById('question-header');
    const totalQuestions = parseInt(questionHeaderEl.textContent.split(' of ')[1]);
    const currentQuestion = parseInt(questionHeaderEl.textContent.split(' ')[1]);

    if (currentQuestion === totalQuestions) {
        if (!confirm('This is your last question. Are you sure you want to submit and finish the exam?')) {
            return;
        }
    }

    const form = document.getElementById('quiz-form');
    const selectedOption = form ? form.querySelector('input[name="option"]:checked') : null;
    const params = selectedOption ? { option: selectedOption.value } : {};

    const questionBox = document.getElementById('question-box');
    
    // Animate out
    questionBox.style.opacity = '0';
    questionBox.style.transform = 'translateY(20px)';

    try {
        const formData = new URLSearchParams(params);
        formData.append('action', 'submit_answer');
        
        const response = await fetch('index.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        if (!response.ok) throw new Error('Server responded with an error');
        
        const data = await response.json();

        if (data.success) {
            if (data.examFinished) {
                // Exam is over, render the results page
                const contentContainer = document.getElementById('layout-content-container');
                contentContainer.innerHTML = data.resultsHtml;
                window.removeEventListener('beforeunload', confirmBeforeUnload);
            } else {
                // Update the UI with the next question's data
                updateQuizUI(data.nextQuestion, data.progress);
                // Animate in
                setTimeout(() => {
                    questionBox.style.opacity = '1';
                    questionBox.style.transform = 'translateY(0)';
                }, 100); // This timeout should match the CSS transition duration
            }
        } else {
            throw new Error(data.error || 'An unknown error occurred');
        }

    } catch (error) {
        console.error("Submission failed:", error);
        alert("Could not submit answer. Please check your connection and try again.");
        // Restore visibility if submission fails
        questionBox.style.opacity = '1';
        questionBox.style.transform = 'translateY(0)';
    }
}
function updateQuizUI(questionData, progressData) {
    // Update progress
    document.getElementById('progress-text').textContent = `${progressData.answered} of ${progressData.total} Answered`;
    const progressPercent = (progressData.answered / progressData.total) * 100;
    document.getElementById('progress-bar-fill').style.width = `${progressPercent}%`;

    // Update question text and header
    document.getElementById('question-header').textContent = `Question ${questionData.index + 1} of ${questionData.total}`;
    document.getElementById('question-text').textContent = questionData.questionText;

    // Rebuild options
    const optionsContainer = document.getElementById('options-container');
    optionsContainer.innerHTML = ''; // Clear old options
    for (const [key, optionText] of Object.entries(questionData.options)) {
        const label = document.createElement('label');
        label.className = 'flex items-center p-4 rounded-lg border border-slate-200 has-[:checked]:bg-green-50 has-[:checked]:border-green-500 cursor-pointer transition-all';
        label.innerHTML = `
            <input type="radio" name="option" value="${key}" class="h-4 w-4 text-green-600 border-slate-300 focus:ring-green-500">
            <span class="ml-3 text-sm font-medium text-slate-700">${optionText}</span>
        `;
        optionsContainer.appendChild(label);
    }
    
    // Update answer box (hidden by default)
    const answerBox = document.getElementById('answer-box-container');
    answerBox.classList.remove('show');
    document.getElementById('correct-answer-text').textContent = questionData.correctAnswer;
    document.getElementById('explanation-text').textContent = questionData.explanation;
    document.getElementById('toggle-answer-btn').textContent = 'Show Answer';

    // Update navigation buttons
    const prevBtn = document.getElementById('prev-btn');
    prevBtn.disabled = questionData.index === 0;
    prevBtn.onclick = () => navigateToQuestion(questionData.index - 1); // Update the index for the prev button
}

// ... (all other event-driven functions like selectCourse, proceedToExam, etc. remain the same) ...

// --- Event-driven Functions ---

function selectCourse(courseName) {
  sendAjaxRequest("select_course", { course: courseName });
}

function proceedToExam(examCode) {
  sendAjaxRequest("proceed_to_exam", { exam_code: examCode });
}

function submitSettings(event) {
  event.preventDefault();
  const form = document.getElementById("settings-form");
  const formData = new FormData(form);
  sendAjaxRequest("submit_settings", new URLSearchParams(formData));
}

function submitAnswer(event) {
  event.preventDefault();
  const form = document.getElementById("quiz-form");
  const selectedOption = form
    ? form.querySelector('input[name="option"]:checked')
    : null;
  const params = selectedOption ? { option: selectedOption.value } : {};
  sendAjaxRequest("submit_answer", params);
}

function navigateToQuestion(index) {
  sendAjaxRequest("navigate_to_question", { navigate_to: index });
}

async function toggleTimer() {
  const timerElement = document.getElementById("timer");
  const currentTimeString = timerElement ? timerElement.textContent : "00:00";
  const timeParts = currentTimeString.split(":");
  const currentRemainingTime =
    parseInt(timeParts[0] || "0", 10) * 60 + parseInt(timeParts[1] || "0", 10);

  // Send the toggle request; the server is the source of truth for time.
  const response = await fetch("index.php", {
    method: "POST",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    body: new URLSearchParams({
      action: "toggle_timer",
      remaining_time: currentRemainingTime,
    }),
  });
  const data = await response.json();
  if (data.success) {
    timerOn = data.timer_on;
    if (timerOn) {
      // The server will have updated the session, a full UI refresh will sync the time.
      // For a smoother experience, we can start the client timer immediately.
      startTimer();
    } else {
      clearInterval(timerInterval);
      if (timerElement) timerElement.textContent = "--:--";
    }
  }
}
function toggleAnswer() {
    const answerBox = document.querySelector('.answer-box');
    const toggleButton = document.querySelector('.toggle-answer');

    if (!answerBox || !toggleButton) {
        console.error('Could not find answer box or toggle button.');
        return;
    }

    // 1. Instantly toggle the UI for a fast user experience
    const isNowVisible = answerBox.classList.toggle('show');

    // 2. Update the button text immediately
    toggleButton.textContent = isNowVisible ? 'Hide Answer' : 'Show Answer';

    // 3. Send a request to the server in the background to save the state.
    // We don't wait for the response ('fire and forget'), so the UI is not blocked.
    // This ensures that if the user navigates away and comes back, the state is remembered.
    fetch('index.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ action: 'toggle_answer' })
    }).catch(error => console.error('Failed to sync answer visibility state:', error));
}

function exitExam() {
  sendAjaxRequest("exit_exam", {});
}

function retakeIncorrect() {
  sendAjaxRequest("retake_incorrect", {});
}

function restartQuiz() {
  sendAjaxRequest("restart_quiz", {});
}

// Initial listener attachment in case some elements are present on page load.
document.addEventListener("DOMContentLoaded", () => {
  attachOptionClickListeners();
});
// ... (all the existing functions like updateTimer, startTimer, etc. remain the same) ...

// The central AJAX function is the only one that needs a change.
