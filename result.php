<?php
// Start session for tracking user progress
require_once __DIR__ . '/config.php';   // gives you $mysqli + session_start()

// Check if topic is specified
if (!isset($_GET['topic'])) {
    header('Location: index.php');
    exit;
}

// Get the topic from URL
$topic = $_GET['topic'];
$dataFile = __DIR__ . '/data-quiz/' . $topic . '.json';

// Check if the topic file exists and session data exists
if (!file_exists($dataFile) || !isset($_SESSION[$topic])) {
    header('Location: index.php');
    exit;
}

// Load quiz data
$jsonData = file_get_contents($dataFile);
$quizData = json_decode($jsonData, true);

// Calculate score
$score = 0;
$totalQuestions = count($quizData['questions']);
$answers = $_SESSION[$topic]['answers'];
$questionOrder = $_SESSION[$topic]['question_order'];

// Collect results for detailed view
$results = [];

for ($i = 0; $i < $totalQuestions; $i++) {
    $questionIndex = $questionOrder[$i];
    $question = $quizData['questions'][$questionIndex];
    $userAnswer = $answers[$i];
    $correctAnswer = $question['answer'];
    
    $isCorrect = ($userAnswer === $correctAnswer);
    if ($isCorrect) {
        $score++;
    }
    
    $results[] = [
        'question' => $question['question'],
        'options' => $question['options'],
        'userAnswer' => $userAnswer,
        'correctAnswer' => $correctAnswer,
        'isCorrect' => $isCorrect,
        'explanation' => $question['explanation']
    ];
}

// Store results in session for detailed view
$_SESSION[$topic]['results'] = $results;
$_SESSION[$topic]['score'] = $score;

// Calculate percentage
$percentage = ($score / $totalQuestions) * 100;

$skillMap = [
    'MachineLearning' => 1,  // AI & ML
    'Backend'         => 4,
    'CloudDevops'     => 5,
    'Design'          => 3,
    'Frontend'        => 2,
    'MobileDev'       => 6
];

// Make sure we know who took the quiz and which quiz it was
if (!empty($_SESSION['user_id']) && isset($skillMap[$topic])) {

    $uid = (int) $_SESSION['user_id'];      // user_id
    $sid = $skillMap[$topic];               // skill_id
    $lvl = (int) round($percentage);        // 0-100 whole number

    // INSERT … ON DUPLICATE KEY UPDATE  →  “upsert”
    $sql  = "INSERT INTO user_skill (user_id, skill_id, level)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE level = VALUES(level)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $uid, $sid, $lvl);
    $stmt->execute();
    $stmt->close();
}

// Determine skill level
$skillLevel = '';
if ($percentage < 50) {
    $skillLevel = 'Beginner';
    $message = 'You\'re just starting out. Keep learning and you\'ll improve!';
} elseif ($percentage < 75) {
    $skillLevel = 'Intermediate';
    $message = 'You have a good foundation. Continue expanding your knowledge!';
} else {
    $skillLevel = 'Advanced';
    $message = 'Excellent work! You have a strong understanding of the subject.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($quizData['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container">
        <header class="result-header">
            <h1>Your Quiz Results</h1>
            <h2><?php echo htmlspecialchars($quizData['title']); ?></h2>
        </header>
        
        <div class="result-container">
            <div class="score-container">
                <div class="score-circle">
                    <span class="score-number"><?php echo $score; ?></span>
                    <span class="score-total">/ <?php echo $totalQuestions; ?></span>
                </div>
                <div class="percentage"><?php echo round($percentage); ?>%</div>
            </div>
            
            <div class="result-details">
                <h3>Skill Level: <span class="skill-level"><?php echo $skillLevel; ?></span></h3>
                <p class="result-message"><?php echo $message; ?></p>
                
                <div class="result-stats">
                    <div class="stat-item">
                        <span class="stat-label">Correct Answers:</span>
                        <span class="stat-value"><?php echo $score; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Incorrect Answers:</span>
                        <span class="stat-value"><?php echo $totalQuestions - $score; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Accuracy:</span>
                        <span class="stat-value"><?php echo round($percentage, 1); ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="result-details.php?topic=<?php echo $topic; ?>" class="action-button details-button">View Detailed Results</a>
                <a href="quiz.php?topic=<?php echo $topic; ?>&restart=1" class="action-button retry-button">Retake Quiz</a>
                <a href="index.php" class="action-button home-button">Try Another Topic</a>
            </div>
        </div>
        
        <section class="recommendation-section">
            <h3>What's Next?</h3>
            <?php if ($percentage < 50): ?>
                <p>Based on your results, we recommend focusing on building a stronger foundation in <?php echo htmlspecialchars($quizData['title']); ?>. Consider these resources:</p>
                <ul class="resource-list">
                    <li>Online tutorials and beginner courses</li>
                    <li>Hands-on practice with simple projects</li>
                    <li>Reviewing fundamental concepts</li>
                </ul>
            <?php elseif ($percentage < 75): ?>
                <p>You're on the right track! To improve your <?php echo htmlspecialchars($quizData['title']); ?> skills further, we recommend:</p>
                <ul class="resource-list">
                    <li>Intermediate-level projects and challenges</li>
                    <li>Diving deeper into advanced concepts</li>
                    <li>Participating in coding communities</li>
                </ul>
            <?php else: ?>
                <p>Congratulations on your excellent performance! To master <?php echo htmlspecialchars($quizData['title']); ?>, consider:</p>
                <ul class="resource-list">
                    <li>Advanced projects and real-world applications</li>
                    <li>Contributing to open-source projects</li>
                    <li>Exploring specialized areas within the field</li>
                </ul>
            <?php endif; ?>
        </section>
        
        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Skill Assessment Quiz Platform. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="./js/quiz.js"></script>
</body>
</html>