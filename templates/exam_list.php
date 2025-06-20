<?php // templates/exam_list.php ?>
<div class="exam-list-container">
    <div class="exam-header">
        <h2 class="course-title">
            <span class="icon-book" style="font-size:1.5rem;">📚</span>
            Available Exams for <span class="course-name"><?php echo htmlspecialchars($selected_course_name); ?></span>
        </h2>
        <p class="exam-count"><?php echo count($selected_exams); ?> exam(s) available</p>
    </div>
    <div class="exam-grid">
        <?php foreach ($selected_exams as $exam): ?>
            <div class="exam-card" data-exam-code="<?php echo htmlspecialchars($exam['exam_code']); ?>">
                <div class="exam-card-header">
                    <div class="exam-icon">
                        <span style="font-size:1.3rem;">📋</span>
                    </div>
                    <div class="exam-info">
                        <h3 class="exam-code"><?php echo htmlspecialchars($exam['exam_code']); ?></h3>
                        <p class="exam-file"><?php echo htmlspecialchars(basename($exam['exam'], '.json')); ?></p>
                    </div>
                </div>
                <div class="exam-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $exam['total_questions']; ?></span>
                        <span class="stat-label">Questions</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">~<?php echo ceil($exam['total_questions'] * 1.5); ?></span>
                        <span class="stat-label">Minutes</span>
                    </div>
                </div>
                <button class="select-exam-btn" onclick="proceedToExam('<?php echo htmlspecialchars($exam['exam_code']); ?>')">
                    <span>Select Exam</span>
                    <span style="font-size:1.1em;">→</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<style>
.exam-list-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: 'Inter', sans-serif;
}

.exam-header {
    text-align: center;
    margin-bottom: 2rem;
}

.course-title {
    font-size: 2rem;
    font-weight: 700;
    color: #222;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.course-name {
    color: #078930;
    font-weight: 600;
}

.exam-count {
    font-size: 1rem;
    color: #888;
    margin: 0;
}

.exam-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.exam-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 4px 0 #0001;
    padding: 1.5rem;
    transition: box-shadow 0.2s, border 0.2s, transform 0.2s;
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.exam-card:hover {
    border-color: #078930;
    box-shadow: 0 4px 16px 0 #07893022;
    transform: translateY(-4px) scale(1.01);
}

.exam-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.exam-icon {
    width: 44px;
    height: 44px;
    background: #fcd116;
    color: #078930;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.5rem;
}

.exam-info {
    flex: 1;
}

.exam-code {
    font-size: 1.1rem;
    font-weight: 600;
    color: #222;
    margin: 0 0 0.2rem 0;
}

.exam-file {
    font-size: 0.9rem;
    color: #888;
    margin: 0;
    font-family: 'Courier New', monospace;
    background: #f8fafc;
    padding: 0.18rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.exam-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 0.5rem;
    padding: 0.7rem 0.5rem;
    background: #f8fafc;
    border-radius: 0.75rem;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 1.3rem;
    font-weight: 700;
    color: #078930;
    line-height: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.select-exam-btn {
    width: 100%;
    padding: 0.8rem 1.5rem;
    background: #078930;
    color: #fff;
    border: none;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.select-exam-btn:hover {
    background: #fcd116;
    color: #222;
    transform: translateY(-2px) scale(1.01);
}

.select-exam-btn:active {
    transform: translateY(0);
}

/* Responsive design */
@media (max-width: 768px) {
    .exam-list-container {
        padding: 1rem 0.5rem;
    }
    
    .course-title {
        font-size: 1.3rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .exam-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .exam-card {
        padding: 1rem;
    }
    
    .exam-stats {
        gap: 0.7rem;
    }
    
    .stat-number {
        font-size: 1.1rem;
    }
}

/* Loading animation for when exams are being fetched */
.exam-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.exam-card.loading .select-exam-btn {
    background: #95a5a6;
    cursor: not-allowed;
}
</style>
<script>
// Enhanced interaction for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state when exam is selected
    const examButtons = document.querySelectorAll('.select-exam-btn');
    examButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.innerHTML = '<span>Loading...</span>';
            this.style.background = '#95a5a6';
            this.disabled = true;
        });
    });
    // Add smooth entrance animation
    const examCards = document.querySelectorAll('.exam-card');
    examCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(.4,0,.2,1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 80);
    });
});
</script>