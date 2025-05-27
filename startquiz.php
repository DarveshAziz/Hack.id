<?php
// Start session for tracking user progress
session_start();

// Check if topic is specified
if (!isset($_GET['topic'])) {
    header('Location: index.php');
    exit;
}

// Get the topic from URL
$topic = $_GET['topic'];
$dataFile = __DIR__ . '/data-quiz/' . $topic . '.json';

// Check if the topic file exists
if (!file_exists($dataFile)) {
    header('Location: index.php');
    exit;
}

// Load quiz data
$jsonData = file_get_contents($dataFile);
$quizData = json_decode($jsonData, true);

// Initialize session variables for new quiz
if (!isset($_SESSION[$topic]) || isset($_GET['restart'])) {
    $_SESSION[$topic] = [
        'current_question' => 0,
        'answers' => array_fill(0, count($quizData['questions']), null),
        'question_order' => range(0, count($quizData['questions']) - 1) // Default order
    ];
    
    // Randomize question order
    shuffle($_SESSION[$topic]['question_order']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['answer'])) {
        $currentIndex = $_SESSION[$topic]['current_question'];
        $_SESSION[$topic]['answers'][$currentIndex] = intval($_POST['answer']);
    }
    
    // Handle navigation
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'next' && $_SESSION[$topic]['current_question'] < count($quizData['questions']) - 1) {
            $_SESSION[$topic]['current_question']++;
        } elseif ($_POST['action'] === 'prev' && $_SESSION[$topic]['current_question'] > 0) {
            $_SESSION[$topic]['current_question']--;
        } elseif ($_POST['action'] === 'submit') {
            header("Location: result.php?topic=$topic");
            exit;
        }
    }
}

// Get current question index
$currentQuestionIndex = $_SESSION[$topic]['current_question'];
$questionNumber = $currentQuestionIndex + 1;
$totalQuestions = count($quizData['questions']);

// Get the actual question based on randomized order
$actualQuestionIndex = $_SESSION[$topic]['question_order'][$currentQuestionIndex];
$currentQuestion = $quizData['questions'][$actualQuestionIndex];

// Get user's saved answer for this question (if any)
$userAnswer = $_SESSION[$topic]['answers'][$currentQuestionIndex];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quizData['title']); ?> Quiz</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container">
        <header class="quiz-header">
            <h1><?php echo htmlspecialchars($quizData['title']); ?> Quiz</h1>
            <a href="index.php" class="back-link">← Back to Topics</a>
        </header>
        
        <div class="progress-container">
            <div class="progress-text">Question <?php echo $questionNumber; ?> of <?php echo $totalQuestions; ?></div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($questionNumber / $totalQuestions) * 100; ?>%"></div>
            </div>
        </div>
        
        <div class="quiz-container">
            <div class="question-container">
                <h2 class="question-text"><?php echo htmlspecialchars($currentQuestion['question']); ?></h2>
                
                <form method="post" id="quiz-form">
                    <div class="options-container">
                        <?php foreach ($currentQuestion['options'] as $index => $option): ?>
                            <div class="option">
                                <input 
                                    type="radio" 
                                    id="option<?php echo $index; ?>" 
                                    name="answer" 
                                    value="<?php echo $index; ?>"
                                    <?php echo ($userAnswer !== null && $userAnswer === $index) ? 'checked' : ''; ?>
                                >
                                <label for="option<?php echo $index; ?>"><?php echo htmlspecialchars($option); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="navigation-buttons">
                        <button 
                            type="submit" 
                            name="action" 
                            value="prev"
                            class="nav-button prev-button"
                            <?php echo $currentQuestionIndex === 0 ? 'disabled' : ''; ?>
                        >Previous</button>
                        
                        <?php if ($currentQuestionIndex < $totalQuestions - 1): ?>
                            <button type="submit" name="action" value="next" class="nav-button next-button">Next</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="submit" class="nav-button submit-button">Submit Quiz</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="quiz-tips">
            <h3>Tips:</h3>
            <ul>
                <li>Read each question carefully before answering</li>
                <li>Your answers are automatically saved when you navigate between questions</li>
                <li>You can change your answers at any time before submitting</li>
                <li>Use the Previous and Next buttons to navigate between questions</li>
            </ul>
        </div>
        
        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Skill Assessment Quiz Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="./js/quiz.js"></script>
</body>
</html>