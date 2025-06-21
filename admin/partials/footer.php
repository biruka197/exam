<div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Question</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="edit-question-form">
                <input type="hidden" id="edit-report-id" name="report_id">
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
            dropArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`; });
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, t => { t.preventDefault(); t.stopPropagation(); }, false));
            ['dragenter', 'dragover'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.add('highlight'), false));
            ['dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.remove('highlight'), false));
            dropArea.addEventListener('drop', e => { fileInput.files = e.dataTransfer.files; fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`; }, false);
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
        function closeEditModal() { modal.style.display = 'none'; }
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