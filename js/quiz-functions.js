/**
 * Skill Assessment Quiz Platform
 * Quiz Specific Functions
 */

// Initialize timer for quiz
function initTimer() {
    // Create timer element if it doesn't exist
    if (!document.getElementById('quiz-timer')) {
        const timerElement = document.createElement('div');
        timerElement.id = 'quiz-timer';
        timerElement.className = 'quiz-timer';
        timerElement.innerHTML = 'Time: <span id="timer-value">00:00</span>';
        
        // Insert before progress container
        const progressContainer = document.querySelector('.progress-container');
        if (progressContainer && progressContainer.parentNode) {
            progressContainer.parentNode.insertBefore(timerElement, progressContainer);
        }
    }
    
    // Get topic from URL
    const urlParams = new URLSearchParams(window.location.search);
    const topic = urlParams.get('topic') || 'webdev';
    
    // Get or set the start time
    let startTime = localStorage.getItem(`${topic}_start_time`);
    if (!startTime) {
        startTime = Date.now();
        localStorage.setItem(`${topic}_start_time`, startTime);
    } else {
        startTime = parseInt(startTime);
    }
    
    // Update the timer
    function updateTimer() {
        const timerValue = document.getElementById('timer-value');
        if (timerValue) {
            const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsedSeconds / 60).toString().padStart(2, '0');
            const seconds = (elapsedSeconds % 60).toString().padStart(2, '0');
            timerValue.textContent = `${minutes}:${seconds}`;
        }
    }
    
    // Update immediately and then every second
    updateTimer();
    setInterval(updateTimer, 1000);
}

// Show toast message
function showToast(message, duration = 2000) {
    // Create or get toast element
    let toast = document.getElementById('toast-message');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-message';
        document.body.appendChild(toast);
    }
    
    // Set message and show
    toast.textContent = message;
    toast.style.display = 'block';
    toast.style.opacity = '1';
    
    // Hide after duration
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, duration);
}

// Handle answer selection
function handleAnswerSelection(radioButton) {
    // Get all radio buttons in the group
    const radioButtons = document.querySelectorAll('input[name="' + radioButton.name + '"]');
    
    // Reset styles for all options
    radioButtons.forEach(rb => {
        const option = rb.closest('.option');
        if (option) {
            option.style.backgroundColor = '';
            option.style.borderLeft = '';
        }
    });
    
    // Style the selected option
    const selectedOption = radioButton.closest('.option');
    if (selectedOption) {
        selectedOption.style.backgroundColor = 'rgba(137, 56, 237, 0.2)';
        selectedOption.style.borderLeft = '3px solid #8938ed';
    }
    
    // Get current question from the DOM
    const currentQuestionElem = document.getElementById('current-question');
    const questionIndex = currentQuestionElem ? parseInt(currentQuestionElem.textContent) - 1 : 0;
    
    // Get topic from URL
    const urlParams = new URLSearchParams(window.location.search);
    const topic = urlParams.get('topic') || 'webdev';
    
    // Save answer to localStorage
    saveAnswer(topic, questionIndex, radioButton.value);
    
    // Show toast message
    showToast('Answer saved!');
}

// Save answer to localStorage
function saveAnswer(topic, questionIndex, value) {
    // Get existing answers
    let answers = localStorage.getItem(`${topic}_answers`);
    answers = answers ? JSON.parse(answers) : new Array(20).fill(null);
    
    // Save this answer
    answers[questionIndex] = parseInt(value);
    
    // Store back to localStorage
    localStorage.setItem(`${topic}_answers`, JSON.stringify(answers));
    localStorage.setItem(`${topic}_position`, questionIndex.toString());
    
    // Also announce to screen readers
    const announcer = document.getElementById('accessibility-announcer');
    if (announcer) {
        announcer.textContent = `Answer ${parseInt(value) + 1} selected and saved.`;
    }
}

// Initialize keyboard navigation
function initKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Only apply on quiz page
        const quizForm = document.getElementById('quiz-form');
        if (!quizForm) return;
        
        // Number keys 1-4 select options
        if (e.key >= '1' && e.key <= '4') {
            const optionIndex = parseInt(e.key) - 1;
            const options = document.querySelectorAll('input[name="answer"]');
            
            if (options && options.length > optionIndex) {
                options[optionIndex].checked = true;
                handleAnswerSelection(options[optionIndex]);
            }
        }
        
        // Arrow keys for navigation
        if (e.key === 'ArrowLeft') {
            const prevButton = document.getElementById('prev-button');
            if (prevButton && !prevButton.disabled) {
                prevButton.click();
            }
        }
        
        if (e.key === 'ArrowRight') {
            const nextButton = document.getElementById('next-button');
            if (nextButton) {
                // Verify an answer is selected
                const selectedOption = document.querySelector('input[name="answer"]:checked');
                if (selectedOption) {
                    nextButton.click();
                } else {
                    showToast('Please select an answer before proceeding');
                }
            }
        }
    });
}

// Wait for DOM to load, then initialize
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on quiz page
    if (document.getElementById('quiz-form')) {
        initTimer();
        initKeyboardNavigation();
        
        // Add event listeners to radio buttons
        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.addEventListener('change', function() {
                handleAnswerSelection(this);
            });
        });
        
        // Make entire option containers clickable
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function(e) {
                if (e.target.type !== 'radio') {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        handleAnswerSelection(radio);
                    }
                }
            });
        });
    }
});