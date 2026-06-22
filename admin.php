<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// Secure endpoint: Require Admin Role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access Denied: Admins only.']);
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$pdo = getDB();

switch ($action) {
    case 'stats':
        // Total Users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $total_users = (int)$stmt->fetchColumn();

        // Total Enrollments
        $stmt = $pdo->query("SELECT COUNT(*) FROM enrollments");
        $total_enrollments = (int)$stmt->fetchColumn();

        // Active Platform Courses
        $stmt = $pdo->query("SELECT COUNT(*) FROM courses");
        $active_courses = (int)$stmt->fetchColumn();

        // List courses with student counts
        $stmt = $pdo->query("
            SELECT c.id, c.title, c.category, c.difficulty, c.instructor, 
                   (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as enrollment_count
            FROM courses c 
            ORDER BY c.id DESC
        ");
        $courses = $stmt->fetchAll();

        foreach ($courses as &$c) {
            $c['id'] = (int)$c['id'];
            $c['enrollment_count'] = (int)$c['enrollment_count'];
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_users' => $total_users,
                'total_enrollments' => $total_enrollments,
                'active_courses' => $active_courses
            ],
            'courses' => $courses
        ]);
        break;

    case 'create_course':
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');
        $difficulty = trim($data['difficulty'] ?? '');
        $instructor = trim($data['instructor'] ?? '');
        $duration_hours = (int)($data['duration_hours'] ?? 0);

        if (empty($title) || empty($description) || empty($category) || empty($difficulty) || empty($instructor) || $duration_hours <= 0) {
            echo json_encode(['success' => false, 'error' => 'All fields are required. Duration must be positive.']);
            exit;
        }

        // Insert course
        $stmt = $pdo->prepare("
            INSERT INTO courses (title, description, category, difficulty, instructor, duration_hours) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $category, $difficulty, $instructor, $duration_hours]);
        $new_course_id = $pdo->lastInsertId();

        // Automatically create a default lesson for the new course
        $lesson_title = "Lesson 1: Welcome & Course Overview";
        $lesson_content = "<h3>1. Welcome to the Course!</h3>"
            . "<p>We are excited to have you start your learning journey in <strong>" . htmlspecialchars($title) . "</strong>.</p>"
            . "<h3>2. Course Objectives</h3>"
            . "<p>In this course, your instructor <strong>" . htmlspecialchars($instructor) . "</strong> will guide you step-by-step through core skills.</p>"
            . "<h3>3. Next Steps</h3>"
            . "<p>Review this introduction, mark it as completed, and stay tuned for subsequent lessons!</p>";
            
        $stmt = $pdo->prepare("
            INSERT INTO lessons (course_id, title, content, order_index) 
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$new_course_id, $lesson_title, $lesson_content]);

        echo json_encode(['success' => true, 'message' => 'Course created successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid admin endpoint action.']);
        break;
}
?>
