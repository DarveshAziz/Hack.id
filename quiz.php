<?php
require __DIR__ . '/vendor/autoload.php';
include 'config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// 2️⃣ If they’re logged in, grab their info; otherwise skip the DB hit
if (isset($_SESSION['user_id'])) {
    // cast to int for safety
    $uid = (int) $_SESSION['user_id'];

    // prepare + execute a safe, injection-proof query
    $stmt = $mysqli->prepare("
        SELECT
          username,
          COALESCE(avatar, 'img/default-avatar.png') AS avatar
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

function getQuizTopics() {
    $topics = [];
    $dataDir = __DIR__ . '/data-quiz/';
    $files = glob($dataDir . '*.json');
    
    foreach ($files as $file) {
        $topicId = basename($file, '.json');
        $jsonData = file_get_contents($file);
        $data = json_decode($jsonData, true);
        
        $topics[$topicId] = [
            'id' => $topicId,
            'title' => $data['title'],
            'description' => $data['description'],
            'difficulty' => $data['difficulty'],
            'questions' => count($data['questions'])
        ];
    }
    
    return $topics;
}

// Get quiz topics
$quizTopics = getQuizTopics();

// Clear session if requested
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Hack.id - Find Your Team. Hack the Future </title>
    <link rel="Website Icon" type="png" href="img/Logo1.png" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />


    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>
        <div class="container-quiz">
            <header class="main-header">
                <h1>Skill Assessment Quiz Platform</h1>
                <p class="tagline">Test your knowledge and assess your technical skills</p>
            </header>

            <section class="intro-section">
                <div class="intro-content">
                    <h2>Welcome to the Tech Skills Quiz</h2>
                    <p>Challenge yourself with our comprehensive quizzes designed to test your knowledge in various tech domains. Each quiz contains 20 carefully selected questions to evaluate your understanding and help you identify areas for improvement.</p>
                    <p>Select a topic below to get started:</p>
                </div>
            </section>

            <section class="topics-grid">
                <?php foreach ($quizTopics as $topic): ?>
                    <div class="topic-card">
                        <div class="topic-header">
                            <h3><?php echo htmlspecialchars($topic['title']); ?></h3>
                            <span class="difficulty-badge"><?php echo htmlspecialchars($topic['difficulty']); ?></span>
                        </div>
                        <p class="topic-description"><?php echo htmlspecialchars($topic['description']); ?></p>
                        <div class="topic-meta">
                            <span class="question-count"><?php echo $topic['questions']; ?> Questions</span>
                            <span class="time-estimate">~15 Minutes</span>
                        </div>
                        <a href="./startquiz.php?topic=<?php echo $topic['id']; ?>" class="start-button">Start Quiz</a>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="features-section">
                <h2>Why Take Our Quizzes?</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <h3>Assess Your Skills</h3>
                        <p>Get an accurate assessment of your current knowledge level in different technical domains.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Learn As You Go</h3>
                        <p>Each question comes with a detailed explanation to help you understand the concepts better.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Track Progress</h3>
                        <p>Take multiple quizzes and track your improvement over time.</p>
                    </div>
                    <div class="feature-item">
                        <h3>Interview Prep</h3>
                        <p>Perfect for preparing for technical interviews and assessments.</p>
                    </div>
                </div>
            </section>

            <footer class="main-footer">
                <p>&copy; <?php echo date('Y'); ?> Skill Assessment Quiz Platform. All rights reserved.</p>
            </footer>
        </div>

        <script src="./js/quiz.js"></script>
        <?php include 'includes/footer.php'; ?>
        
    </body>
</html>