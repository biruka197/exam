document.addEventListener('DOMContentLoaded', () => {
    // Hamburger menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
    // New function call for battery status
    updateBatteryStatus();
});

// --- NEW BATTERY STATUS FUNCTION ---
async function updateBatteryStatus() {
    const batteryStatusEl = document.getElementById('battery-status');
    const batteryIconEl = document.getElementById('battery-icon');
    const batteryLevelEl = document.getElementById('battery-level');

    if (!navigator.getBattery || !batteryStatusEl) {
        // Battery API not supported or element not found
        return;
    }

    try {
        const battery = await navigator.getBattery();
        batteryStatusEl.style.display = 'flex'; // Show the element

        function updateAll() {
            updateLevelInfo();
            updateChargeInfo();
        }
        updateAll();

        // Add event listeners to update automatically
        battery.addEventListener('chargingchange', updateAll);
        battery.addEventListener('levelchange', updateAll);

        function updateChargeInfo() {
            batteryIconEl.classList.toggle('fa-bolt', battery.charging);
        }

        function updateLevelInfo() {
            const level = Math.round(battery.level * 100);
            batteryLevelEl.textContent = `${level}%`;

            // Update icon based on level
            batteryIconEl.className = 'fas'; // Reset classes
            if (level > 95) {
                batteryIconEl.classList.add('fa-battery-full');
            } else if (level > 75) {
                batteryIconEl.classList.add('fa-battery-three-quarters');
            } else if (level > 50) {
                batteryIconEl.classList.add('fa-battery-half');
            } else if (level > 25) {
                batteryIconEl.classList.add('fa-battery-quarter');
            } else {
                batteryIconEl.classList.add('fa-battery-empty');
            }

            // Add charging icon if applicable
            if (battery.charging) {
                batteryIconEl.classList.add('fa-bolt');
            }
        }

    } catch (err) {
        batteryStatusEl.style.display = 'none'; // Hide if there's an error
        console.error("Battery Status API error:", err);
    }
}


function filterCourses() {
    const input = document.getElementById("course-search-input");
    const filter = input.value.toLowerCase();
    const courseContainer = document.getElementById("course-grid-container");
    const courses = courseContainer.getElementsByClassName("course-card-item");
    const noResultsMessage = document.getElementById("no-search-results");
    let visibleCount = 0;
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
    if (noResultsMessage) {
        noResultsMessage.style.display = visibleCount === 0 ? "block" : "none";
    }
}

let timeLeft = 0,
    timerOn = true,
    timerInterval;

function updateTimer() {
    const timer = document.getElementById("timer");
    if (!timer) return;
    if (!timerOn) {
        timer.textContent = "--:--";
        return;
    }
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timer.textContent = "00:00";
        if (document.getElementById("quiz-form")) {
            alert("Time out");
        }
        return;
    }
    let minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;
    timer.textContent = `${String(minutes).padStart(2, "0")}:${String(
        seconds
    ).padStart(2, "0")}`;
    timeLeft--;
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    if (typeof timeLeft !== "number" || isNaN(timeLeft) || timeLeft < 0)
        timeLeft = 0;
    timerInterval = setInterval(updateTimer, 1000);
}

async function sendAjaxRequest(action, bodyParams) {
    const contentContainer = document.getElementById("layout-content-container");
    document.body.classList.add("cursor-wait");
    try {
        const formData = new URLSearchParams(bodyParams);
        formData.append("action", action);
        const response = await fetch("index.php", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: formData,
        });
        if (!response.ok) throw new Error(`Network error: ${response.statusText}`);
        const data = await response.json();
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        if (data.html) {
            if (contentContainer) contentContainer.innerHTML = data.html;
        } else if (!data.success && data.error) {
            if (contentContainer)
                contentContainer.innerHTML = `<div class="m-4 p-4 text-red-800 bg-red-100 border border-red-300 rounded-lg">${data.error}</div>`;
        }
        if (data.remaining_time !== undefined) {
            timeLeft = data.remaining_time;
            timerOn = data.timer_on !== false;
            if (timerOn) startTimer();
            else clearInterval(timerInterval);
        }
    } catch (error) {
        console.error("Fetch failed:", error);
        if (contentContainer)
            contentContainer.innerHTML = `<div class="m-4 p-4 text-red-800 bg-red-100 border border-red-300 rounded-lg">Request failed: ${error.message}</div>`;
    } finally {
        document.body.classList.remove("cursor-wait");
    }
}

function showCustomConfirm(message, onConfirm) {
    const modal = document.getElementById("custom-confirm-modal");
    const messageEl = document.getElementById("custom-confirm-message");
    const confirmBtn = document.getElementById("custom-confirm-btn");
    const cancelBtn = document.getElementById("custom-cancel-btn");
    if (!modal) {
        if (confirm(message)) onConfirm();
        return;
    }
    messageEl.textContent = message;
    modal.classList.remove("hidden", "opacity-0");
    const confirmHandler = () => {
        onConfirm();
        closeModal();
    };
    const cancelHandler = () => closeModal();
    const closeModal = () => {
        modal.classList.add("opacity-0");
        setTimeout(() => modal.classList.add("hidden"), 300);
        confirmBtn.removeEventListener("click", confirmHandler);
        cancelBtn.removeEventListener("click", cancelHandler);
    };
    confirmBtn.addEventListener("click", confirmHandler);
    cancelBtn.addEventListener("click", cancelHandler);
}

function submitAnswer(event) {
    event.preventDefault();
    const questionHeaderEl = document.getElementById("question-header");
    const progressTextEl = document.getElementById("progress-text");
    let proceed = true;
    const form = document.getElementById("quiz-form");
    const selectedOption = form
        ? form.querySelector('input[name="option"]:checked')
        : null;
    const params = selectedOption ? { option: selectedOption.value } : {};

    const executeSubmit = () => sendAjaxRequest("submit_answer", params);

    if (questionHeaderEl && progressTextEl) {
        const headerParts = questionHeaderEl.textContent.trim().split(" ");
        const currentQuestionNum = parseInt(headerParts[1], 10);
        const totalQuestions = parseInt(headerParts[3], 10);
        const progressParts = progressTextEl.textContent.trim().split(" ");
        const answeredCount =
            parseInt(progressParts[0], 10) + (selectedOption ? 1 : 0);
        if (currentQuestionNum === totalQuestions) {
            if (answeredCount < totalQuestions) {
                showCustomConfirm(
                    "You have unanswered questions. Are you sure you want to submit?",
                    executeSubmit
                );
                proceed = false;
            }
        }
    }
    if (proceed) {
        executeSubmit();
    }
}

function selectCourse(courseName) {
    sendAjaxRequest("select_course", { course: courseName });
}
function proceedToExam(examCode) {
    sendAjaxRequest("proceed_to_exam", { exam_code: examCode });
}
function submitSettings(event) {
    event.preventDefault();
    sendAjaxRequest(
        "submit_settings",
        new URLSearchParams(new FormData(event.target))
    );
}
function navigateToQuestion(index) {
    sendAjaxRequest("navigate_to_question", { navigate_to: index });
}
function toggleAnswer() {
    const answerBox = document.querySelector(".answer-box");
    if (answerBox) answerBox.classList.toggle("show");
    fetch("index.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: new URLSearchParams({ action: "toggle_answer" }),
    });
}
function exitExam() {
    showExitModal();
}
function retakeIncorrect() {
    sendAjaxRequest("retake_incorrect", {});
}
function restartQuiz() {
    sendAjaxRequest("restart_quiz", {});
}
function toggleExplanation(button) {
    const wrapper = button.nextElementSibling;
    if (wrapper && wrapper.classList.contains("review-explanation-wrapper")) {
        wrapper.classList.toggle("hidden");
        button.textContent = wrapper.classList.contains("hidden")
            ? "Show Explanation"
            : "Hide Explanation";
    }
}

// -- Modal and Toast Functions --
function showExitModal() {
    const modal = document.getElementById("exit-modal");
    if (modal) modal.classList.remove("hidden", "opacity-0");
    document.getElementById("exit-modal-confirm-btn").onclick = proceedWithExit;
    document.getElementById("exit-modal-cancel-btn").onclick = hideExitModal;
}
function hideExitModal() {
    const modal = document.getElementById("exit-modal");
    if (modal) {
        modal.classList.add("opacity-0");
        setTimeout(() => modal.classList.add("hidden"), 300);
    }
}
function proceedWithExit() {
    const btn = document.getElementById("exit-modal-confirm-btn");
    btn.disabled = true;
    btn.textContent = "Exiting...";
    sendAjaxRequest("exit_exam", {});
}
function showReportModal(questionNumber) {
    const modal = document.getElementById("report-modal");
    const confirmBtn = document.getElementById("modal-confirm-btn");
    if (modal && confirmBtn) {
        confirmBtn.dataset.questionNumber = questionNumber;
        modal.classList.remove("hidden", "opacity-0");
        confirmBtn.onclick = handleReportConfirmation;
        document.getElementById("modal-cancel-btn").onclick = hideReportModal;
    }
}
function hideReportModal() {
    const modal = document.getElementById("report-modal");
    if (modal) {
        modal.classList.add("opacity-0");
        setTimeout(() => modal.classList.add("hidden"), 300);
    }
}
function handleReportConfirmation() {
    const confirmBtn = document.getElementById("modal-confirm-btn");
    const questionNumber = confirmBtn.dataset.questionNumber;
    confirmBtn.disabled = true;
    confirmBtn.textContent = "Reporting...";
    fetch("index.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: new URLSearchParams({
            action: "report_question",
            question_number: questionNumber,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            hideReportModal();
            if (data.success) {
                showToast(
                    data.status === "already_reported"
                        ? "This question has already been reported."
                        : "Question reported. Thank you!"
                );
                const mainReportBtn = document.getElementById("report-btn");
                if (mainReportBtn) {
                    mainReportBtn.textContent = "✓ Reported";
                    mainReportBtn.disabled = true;
                }
            } else {
                showToast(
                    "Error: " + (data.error || "Could not report question."),
                    true
                );
            }
        })
        .catch((err) => {
            hideReportModal();
            showToast("An error occurred. Please try again.", true);
        })
        .finally(() => {
            confirmBtn.disabled = false;
            confirmBtn.textContent = "Yes, Report";
        });
}
function showToast(message, isError = false) {
    const toast = document.getElementById("toast-notification");
    if (toast) {
        toast.textContent = message;
        toast.classList.toggle("bg-red-600", isError);
        toast.classList.toggle("bg-slate-900", !isError);
        toast.classList.remove("opacity-0", "translate-y-16");
        setTimeout(() => toast.classList.add("opacity-0", "translate-y-16"), 3000);
    }
}
function proceedToStudyExam(moduleCode, lesson) {
    sendAjaxRequest("proceed_to_study_exam", {
        module_code: moduleCode,
        lesson: lesson,
    });
}

function startAllQuestionsMode(courseName) {
    sendAjaxRequest('start_all_questions_exam', { course: courseName });
}

function submitExamNow() {
    if (confirm("Are you sure you want to submit your exam? You will be scored only on the questions you have answered.")) {
        sendAjaxRequest('submit_exam_now', {});
    }
}