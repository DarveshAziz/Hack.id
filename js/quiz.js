/**
 * Skill Assessment Quiz Platform
 * Main JavaScript file
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search functionality if on home page
    initSearch();
    
    // Initialize quiz timer if on quiz page
    initQuizTimer();
    
    // Handle form validation and submission
    const quizForm = document.getElementById('quiz-form');
    if (quizForm) {
        // Auto-save answers when radio buttons are clicked
        const radioButtons = document.querySelectorAll('input[name="answer"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                // Get quiz topic from URL
                const topicId = new URLSearchParams(window.location.search).get('topic') || 'webdev';
                const questionIndex = document.getElementById('current-question').textContent - 1;
                
                // Save to localStorage
                saveAnswer(topicId, questionIndex, this.value);
                
                // Highlight selected option
                highlightSelectedOption(radio, radioButtons);
                
                // Show feedback toast
                showToast('Answer saved!');
            });
        });
        
        // Apply initial highlight to the selected option
        const initialSelected = document.querySelector('input[name="answer"]:checked');
        if (initialSelected) {
            highlightSelectedOption(initialSelected, radioButtons);
        }
    }
    
    // Add mobile menu functionality
    setupMobileMenu();
    
    // Add animations to score circle on results page
    animateScoreCircle();
    
    // Handle progress bar animation
    animateProgressBar();
    
    // Add hover effects to topic cards
    addTopicCardEffects();
    
    // Add option hover effects
    addOptionHoverEffects();
    
    // Add keyboard navigation for accessibility
    addKeyboardNavigation();
    
    // Add responsive handling
    handleResponsiveLayout();
});

// Helper Functions

function saveAnswer(topicId, questionIndex, value) {
    // Get existing answers from localStorage or initialize empty array
    const answersKey = `${topicId}_answers`;
    let answers = JSON.parse(localStorage.getItem(answersKey) || '[]');
    
    // Make sure the answers array is large enough
    while (answers.length <= questionIndex) {
        answers.push(null);
    }
    
    // Save the answer
    answers[questionIndex] = parseInt(value, 10);
    localStorage.setItem(answersKey, JSON.stringify(answers));
    
    // Save current position too
    localStorage.setItem(`${topicId}_position`, questionIndex.toString());
}

function highlightSelectedOption(selectedRadio, allRadios) {
    // Reset all options
    allRadios.forEach(rb => {
        const optionContainer = rb.closest('.option');
        optionContainer.style.backgroundColor = '';
        optionContainer.style.borderLeft = '';
    });
    
    // Highlight the selected option
    const optionContainer = selectedRadio.closest('.option');
    optionContainer.style.backgroundColor = 'rgba(137, 56, 237, 0.2)';
    optionContainer.style.borderLeft = '3px solid #8938ed';
}

function showToast(message, duration = 1500) {
    // Create toast element if it doesn't exist
    let toast = document.getElementById('toast-message');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-message';
        document.body.appendChild(toast);
        
        // Add toast styles
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%)';
        toast.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        toast.style.color = 'white';
        toast.style.padding = '10px 20px';
        toast.style.borderRadius = '4px';
        toast.style.zIndex = '9999';
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
    }
    
    // Set message and show the toast
    toast.textContent = message;
    toast.style.opacity = '1';
    
    // Hide after duration
    setTimeout(() => {
        toast.style.opacity = '0';
    }, duration);
}

function setupMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

function animateScoreCircle() {
    const scoreCircle = document.querySelector('.score-circle');
    if (scoreCircle) {
        // Add a simple fade-in and scale animation
        scoreCircle.style.animation = 'scoreAppear 0.5s ease-out forwards';
        
        // Define the animation
        if (!document.getElementById('score-animation')) {
            const styleSheet = document.createElement('style');
            styleSheet.id = 'score-animation';
            styleSheet.innerText = `
                @keyframes scoreAppear {
                    0% {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            `;
            document.head.appendChild(styleSheet);
        }
    }
}

function animateProgressBar() {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        // Ensure the width transition is smooth
        setTimeout(() => {
            progressFill.style.transition = 'width 0.5s ease-in-out';
        }, 100);
    }
}

function addTopicCardEffects() {
    const topicCards = document.querySelectorAll('.topic-card');
    topicCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 15px rgba(0, 0, 0, 0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        });
    });
}

function addOptionHoverEffects() {
    const options = document.querySelectorAll('.option');
    options.forEach(option => {
        option.addEventListener('mouseenter', function() {
            // Only apply hover effect if not already selected
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.checked) {
                this.style.backgroundColor = 'rgba(137, 56, 237, 0.1)';
            }
        });
        
        option.addEventListener('mouseleave', function() {
            // Reset style if not checked
            const radio = this.querySelector('input[type="radio"]');
            if (radio && !radio.checked) {
                this.style.backgroundColor = '';
            }
        });
        
        // Make the entire option clickable
        option.addEventListener('click', function(e) {
            // Don't trigger if the click was on the radio button itself
            if (e.target.type !== 'radio') {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    
                    // Trigger the change event
                    const event = new Event('change');
                    radio.dispatchEvent(event);
                }
            }
        });
    });
}

function addKeyboardNavigation() {
    // Add keyboard navigation for quiz options
    document.addEventListener('keydown', function(e) {
        // Only apply on quiz page
        if (!document.getElementById('quiz-form')) return;
        
        // Number keys 1-4 for selecting options
        if (e.key >= '1' && e.key <= '4') {
            const optionIndex = parseInt(e.key) - 1;
            const options = document.querySelectorAll('input[name="answer"]');
            
            if (options && options.length > optionIndex) {
                options[optionIndex].checked = true;
                
                // Trigger change event
                const event = new Event('change');
                options[optionIndex].dispatchEvent(event);
            }
        }
        
        // Left arrow or Backspace for Previous
        if (e.key === 'ArrowLeft' || e.key === 'Backspace') {
            const prevButton = document.getElementById('prev-button');
            if (prevButton && !prevButton.disabled) {
                prevButton.click();
            }
        }
        
        // Right arrow or Enter for Next/Submit
        if (e.key === 'ArrowRight' || e.key === 'Enter') {
            // Prevent default Enter behavior if we're in the quiz
            if (e.key === 'Enter') {
                e.preventDefault();
            }
            
            const nextButton = document.getElementById('next-button');
            if (nextButton) {
                // Check if an option is selected
                const selectedOption = document.querySelector('input[name="answer"]:checked');
                if (selectedOption) {
                    nextButton.click();
                } else {
                    showToast('Please select an answer first');
                }
            }
        }
    });
}

function handleResponsiveLayout() {
    // Add any responsive layout adjustments here
    function adjustLayout() {
        const isMobile = window.innerWidth < 768;
        
        // Adjust layout for mobile if needed
        if (isMobile) {
            // Example: Make navigation buttons full width on mobile
            const navButtons = document.querySelectorAll('.nav-button');
            navButtons.forEach(button => {
                button.style.width = '100%';
                button.style.marginBottom = '10px';
            });
        } else {
            // Reset styles for desktop
            const navButtons = document.querySelectorAll('.nav-button');
            navButtons.forEach(button => {
                button.style.width = '';
                button.style.marginBottom = '';
            });
        }
    }
    
    // Run on load
    adjustLayout();
    
    // Run on resize
    window.addEventListener('resize', adjustLayout);
}

function initQuizTimer() {
    // Only initialize if on quiz page
    const quizContainer = document.querySelector('.quiz-container');
    if (!quizContainer) return;
    
    // Get topic and create timer display if it doesn't exist
    const topicId = new URLSearchParams(window.location.search).get('topic') || 'webdev';
    let timerDisplay = document.getElementById('quiz-timer');
    
    if (!timerDisplay) {
        // Create timer element
        timerDisplay = document.createElement('div');
        timerDisplay.id = 'quiz-timer';
        timerDisplay.className = 'quiz-timer';
        timerDisplay.innerHTML = '<span>Time: 00:00</span>';
        
        // Style the timer
        timerDisplay.style.textAlign = 'right';
        timerDisplay.style.marginBottom = '10px';
        timerDisplay.style.color = 'var(--text-muted)';
        
        // Add it to the page before the progress container
        const progressContainer = document.querySelector('.progress-container');
        if (progressContainer) {
            progressContainer.parentNode.insertBefore(timerDisplay, progressContainer);
        }
    }
    
    // Get start time from localStorage or set it now
    let startTime = parseInt(localStorage.getItem(`${topicId}_start_time`) || Date.now());
    localStorage.setItem(`${topicId}_start_time`, startTime);
    
    // Update timer every second
    function updateTimer() {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const seconds = (elapsed % 60).toString().padStart(2, '0');
        timerDisplay.innerHTML = `<span>Time: ${minutes}:${seconds}</span>`;
    }
    
    // Initial update
    updateTimer();
    
    // Update every second
    setInterval(updateTimer, 1000);
}

function initSearch() {
    const searchInput = document.getElementById('topic-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const topicCards = document.querySelectorAll('.topic-card');
        
        topicCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('.topic-description').textContent.toLowerCase();
            const difficulty = card.querySelector('.difficulty-badge').textContent.toLowerCase();
            
            const isMatch = title.includes(searchTerm) || 
                           description.includes(searchTerm) || 
                           difficulty.includes(searchTerm);
            
            // Show or hide the card based on match
            card.style.display = isMatch ? 'flex' : 'none';
        });
        
        // Show a message if no results
        const topicsGrid = document.querySelector('.topics-grid');
        let noResultsMsg = document.getElementById('no-results-message');
        
        const visibleCards = document.querySelectorAll('.topic-card[style="display: flex"]').length;
        
        if (visibleCards === 0 && searchTerm !== '') {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'no-results-message';
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.textContent = 'No quiz topics match your search.';
                topicsGrid.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = 'block';
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    });
}