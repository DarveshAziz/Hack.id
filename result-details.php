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

// Check if session data exists
if (!isset($_SESSION[$topic]) || !isset($_SESSION[$topic]['results'])) {
    header('Location: index.php');
    exit;
}

// Load quiz data
$dataFile = __DIR__ . '/data-quiz/' . $topic . '.json';
$jsonData = file_get_contents($dataFile);
$quizData = json_decode($jsonData, true);

// Get results from session
$results = $_SESSION[$topic]['results'];
$score = $_SESSION[$topic]['score'];
$totalQuestions = count($results);
$percentage = ($score / $totalQuestions) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Results - <?php echo htmlspecialchars($quizData['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container">
        <header class="result-header">
            <h1>Detailed Quiz Results</h1>
            <h2><?php echo htmlspecialchars($quizData['title']); ?></h2>
            <div class="score-summary">
                Score: <span class="highlight"><?php echo $score; ?> / <?php echo $totalQuestions; ?></span> 
                (<span class="highlight"><?php echo round($percentage); ?>%</span>)
            </div>
            <div class="nav-links">
                <a href="result.php?topic=<?php echo $topic; ?>" class="back-link">← Back to Results Summary</a>
                <a href="index.php" class="home-link">Home</a>
            </div>
        </header>
        
        <div class="details-container">
            <?php foreach ($results as $index => $result): ?>
                <div class="question-review <?php echo $result['isCorrect'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-header">
                        <span class="question-number">Question <?php echo $index + 1; ?></span>
                        <span class="result-indicator"><?php echo $result['isCorrect'] ? 'Correct' : 'Incorrect'; ?></span>
                    </div>
                    
                    <h3 class="question-text"><?php echo htmlspecialchars($result['question']); ?></h3>
                    
                    <div class="options-review">
                        <?php foreach ($result['options'] as $optionIndex => $option): ?>
                            <div class="option-item <?php 
                                if ($optionIndex === $result['correctAnswer']) echo 'correct-answer';
                                else if ($optionIndex === $result['userAnswer'] && !$result['isCorrect']) echo 'wrong-answer';
                            ?>">
                                <span class="option-marker">
                                    <?php 
                                        if ($optionIndex === $result['correctAnswer']) echo '✓';
                                        else if ($optionIndex === $result['userAnswer'] && !$result['isCorrect']) echo '✗';
                                    ?>
                                </span>
                                <span class="option-text"><?php echo htmlspecialchars($option); ?></span>
                                <?php if ($optionIndex === $result['userAnswer']): ?>
                                    <span class="user-choice">Your answer</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="explanation">
                        <h4>Explanation:</h4>
                        <p><?php echo htmlspecialchars($result['explanation']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="action-buttons center">
            <a href="./quiz.php?topic=<?php echo $topic; ?>&restart=1" class="action-button retry-button">Retake Quiz</a>
            <a href="./quiz.php" class="action-button home-button">Try Another Topic</a>
        </div>
        
        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Skill Assessment Quiz Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="./js/quiz.js"></script>
</body>
</html>