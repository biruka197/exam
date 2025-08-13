<div id="edit-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Question</h2>
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="edit-question-form">
            <input type="hidden" id="edit-report-id" name="report_id">
            
            <div class="form-group">
                <button type="button" onclick="analyzeWithAI()" class="btn btn-primary" style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);"><i class="fas fa-robot"></i> Analyze with AI</button>
                <div id="ai-analysis-output" class="ai-response-box" style="display: none; margin-top: 1rem; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 5px; padding: 1rem; min-height: 50px; white-space: pre-wrap;"></div>
            </div>

            <div class="form-group">
                <label for="edit-question-text">Question Text</label>
                <textarea id="edit-question-text" rows="3" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;"></textarea>
            </div>
            <div id="edit-options-container"></div>
            <div class="form-group">
                <label for="edit-correct-answer">Correct Answer (Key)</label>
                <input type="text" id="edit-correct-answer" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label for="edit-explanation">Explanation</label>
                <textarea id="edit-explanation" rows="3" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;"></textarea>
            </div>
            <button type="button" onclick="saveQuestion()" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

    const dropArea = document.getElementById('drop-area'), fileInput = document.getElementById('file-input'), fileNameDisplay = document.getElementById('file-name');
    if (dropArea) {
        const handleFileSelection = (files) => {
            if (fileNameDisplay) {
                if (files && files.length > 1) {
                    fileNameDisplay.textContent = `Selected: ${files.length} files`;
                } else if (files && files.length === 1) {
                    fileNameDisplay.textContent = `Selected: ${files[0].name}`;
                } else {
                    fileNameDisplay.textContent = '';
                }
            }
        };

        dropArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            handleFileSelection(fileInput.files);
        });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
        });
        dropArea.addEventListener('drop', e => {
            fileInput.files = e.dataTransfer.files;
            handleFileSelection(fileInput.files);
        }, false);
    }

    const modal = document.getElementById('edit-modal');
    function openEditModal(reportId) {
        document.getElementById('edit-report-id').value = reportId;
        const formData = new FormData();
        formData.append('action', 'get_question_for_edit');
        formData.append('report_id', reportId);
        fetch('includes/ajax_handler.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
            if (data.success) {
                const q = data.question;
                document.getElementById('edit-question-text').value = q.question;
                document.getElementById('edit-correct-answer').value = q.correct_answer;
                document.getElementById('edit-explanation').value = q.explanation;
                const optionsContainer = document.getElementById('edit-options-container');
                optionsContainer.innerHTML = '';
                for (const key in q.options) {
                    const optionGroup = document.createElement('div');
                    optionGroup.className = 'form-group';
                    optionGroup.innerHTML = `<label>Option ${key.toUpperCase()}</label><input type="text" class="edit-option-input" data-key="${key}" value="${q.options[key]}" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;">`;
                    optionsContainer.appendChild(optionGroup);
                }
                modal.style.display = 'block';
            } else { alert('Error: ' + data.error); }
        });
    }
    function closeEditModal() {
        modal.style.display = 'none';
        document.getElementById('ai-analysis-output').style.display = 'none';
        document.getElementById('ai-analysis-output').innerHTML = '';
    }

    function analyzeWithAI() {
        const analysisOutput = document.getElementById('ai-analysis-output');
        analysisOutput.style.display = 'block';
        analysisOutput.innerHTML = 'Analyzing... <i class="fas fa-spinner fa-spin"></i>';

        const questionText = document.getElementById('edit-question-text').value;
        const explanation = document.getElementById('edit-explanation').value;
        const correctAnswerKey = document.getElementById('edit-correct-answer').value;
        const options = {};
        document.querySelectorAll('.edit-option-input').forEach(input => {
            options[input.dataset.key] = input.value;
        });

        const prompt = `
            A user has reported the following exam question as potentially incorrect.
            Please analyze the question, options, and explanation.

            **Reported Question Data:**
            - Question: "${questionText}"
            - Options: ${JSON.stringify(options, null, 2)}
            - Correct Answer Key: "${correctAnswerKey}"
            - Explanation: "${explanation}"

            **Your Tasks:**
            1.  **Validate the Report:** Determine if the user's report is likely "Valid" or "Invalid". Provide a brief, one-sentence reason for your conclusion.
            2.  **Correct the Content:** If you find any errors (factual, grammatical, or logical) in the question, options, or explanation, provide a corrected version. If the content is already correct, return the original content.

            **Output Format:**
            Please provide your response *only* in the following JSON format. Do not include any text or markdown formatting before or after the JSON block.

            {
              "report_validation": {
                "status": "Valid/Invalid",
                "reason": "Your one-sentence reason here."
              },
              "corrected_content": {
                "question": "Corrected question text.",
                "options": {
                  "a": "Corrected option a.",
                  "b": "Corrected option b.",
                  "c": "Corrected option c."
                },
                "correct_answer": "The new correct answer key (e.g., 'b').",
                "explanation": "The new or improved explanation."
              }
            }
        `;

        const formData = new FormData();
        formData.append('action', 'analyze_question_with_ai');
        formData.append('prompt', prompt);

        fetch('includes/ajax_handler.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.ai_response) {
                    try {
                        let cleanedJsonString = data.ai_response.replace(/\\\`\\\`\\\`json/g, '').replace(/\\\`\\\`\\\`/g, '').replace(/```json/g, '').replace(/```/g, '').trim();
                        const aiData = JSON.parse(cleanedJsonString);
                        const validation = aiData.report_validation;
                        analysisOutput.innerHTML = `<strong>AI Analysis: ${validation.status}</strong><p>${validation.reason}</p>`;
                        const corrected = aiData.corrected_content;
                        if (corrected) {
                            document.getElementById('edit-question-text').value = corrected.question;
                            document.getElementById('edit-explanation').value = corrected.explanation;
                            document.getElementById('edit-correct-answer').value = corrected.correct_answer;
                            document.querySelectorAll('.edit-option-input').forEach(input => {
                                const key = input.dataset.key;
                                if (corrected.options && corrected.options[key]) {
                                    input.value = corrected.options[key];
                                }
                            });
                        }
                    } catch (e) {
                        analysisOutput.innerText = 'Error: Could not parse AI response. ' + e.message;
                        console.error("Raw AI response:", data.ai_response);
                    }
                } else {
                    analysisOutput.innerText = 'Error: ' + (data.error || 'Failed to get AI response.');
                }
            })
            .catch(err => {
                analysisOutput.innerText = 'Request failed: ' + err;
            });
    }

    function saveQuestion() {
        const reportId = document.getElementById('edit-report-id').value, options = {};
        document.querySelectorAll('.edit-option-input').forEach(input => { options[input.dataset.key] = input.value; });
        const questionData = {
            question: document.getElementById('edit-question-text').value,
            options: options,
            correct_answer: document.getElementById('edit-correct-answer').value,
            explanation: document.getElementById('edit-explanation').value,
        };
        const formData = new FormData();
        formData.append('action', 'save_edited_question');
        formData.append('report_id', reportId);
        for(const key in questionData) {
            if(typeof questionData[key] === 'object') {
                for (const subKey in questionData[key]) formData.append(`question_data[${key}][${subKey}]`, questionData[key][subKey]);
            } else { formData.append(`question_data[${key}]`, questionData[key]); }
        }
        fetch('includes/ajax_handler.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
            if (data.success) {
                alert(data.message);
                closeEditModal();
                document.getElementById(`report-row-${reportId}`).remove();
            } else { alert('Error saving question: ' + data.error); }
        });
    }
    window.onclick = function(event) { if (event.target == modal) closeEditModal(); }
</script>
</body>
</html>