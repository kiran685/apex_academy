<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$pdo = getDB();

switch ($action) {
    case 'list':
        $user_id = $_SESSION['user']['id'] ?? 0;
        
        // Retrieve courses. If logged in, fetch enrollment state.
        if ($user_id > 0) {
            $stmt = $pdo->prepare("
                SELECT c.*, IF(e.id IS NULL, 0, 1) as enrolled 
                FROM courses c
                LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
                ORDER BY c.title ASC
            ");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->query("SELECT *, 0 as enrolled FROM courses ORDER BY title ASC");
        }
        
        $courses = $stmt->fetchAll();
        
        // Make sure types are correct (JSON format)
        foreach ($courses as &$c) {
            $c['id'] = (int)$c['id'];
            $c['duration_hours'] = (int)$c['duration_hours'];
            $c['enrolled'] = (int)$c['enrolled'];
        }
        
        echo json_encode(['success' => true, 'courses' => $courses]);
        break;

    case 'enroll':
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
            echo json_encode(['success' => false, 'error' => 'Only students can enroll in courses.']);
            exit;
        }

        $user_id = $_SESSION['user']['id'];
        $course_id = (int)($data['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course selection.']);
            exit;
        }

        // Check if course exists
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Course does not exist.']);
            exit;
        }

        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You are already enrolled in this course.']);
            exit;
        }

        // Insert enrollment
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, progress_percent) VALUES (?, ?, 0)");
        $stmt->execute([$user_id, $course_id]);

        echo json_encode(['success' => true, 'message' => 'Enrolled successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid courses endpoint action.']);
        break;
}
?>
