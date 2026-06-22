<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Access Denied: Students only.']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$pdo = getDB();

switch ($action) {
    case 'student_stats':
        // 1. Get stats
        // Active courses (progress < 100)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND progress_percent < 100");
        $stmt->execute([$user_id]);
        $active_courses = (int)$stmt->fetchColumn();

        // Average completion rate
        $stmt = $pdo->prepare("SELECT AVG(progress_percent) FROM enrollments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $avg_comp = $stmt->fetchColumn();
        $completion_rate = $avg_comp !== null ? round((float)$avg_comp) : 0;

        // Dynamic Study Hours: Sum of (course duration * course progress)
        $stmt = $pdo->prepare("
            SELECT SUM(c.duration_hours * (e.progress_percent / 100.0)) 
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $hours_sum = $stmt->fetchColumn();
        $study_hours = $hours_sum !== null ? round((float)$hours_sum, 1) : 0.0;

        // 2. Get enrollments list
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.instructor, c.category, e.progress_percent 
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute([$user_id]);
        $enrollments = $stmt->fetchAll();

        // 3. Get category distribution for doughnut chart
        $stmt = $pdo->prepare("
            SELECT c.category, COUNT(*) as count 
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.user_id = ?
            GROUP BY c.category
        ");
        $stmt->execute([$user_id]);
        $cat_distribution = $stmt->fetchAll();

        $cat_labels = [];
        $cat_values = [];
        foreach ($cat_distribution as $row) {
            $cat_labels[] = $row['category'];
            $cat_values[] = (int)$row['count'];
        }

        // Default categories if empty, to draw a nice chart
        if (empty($cat_labels)) {
            $cat_labels = ['Design', 'Development', 'Business'];
            $cat_values = [0, 0, 0];
        }

        // Mock weekly study log (based on user_id to show variety)
        $base_hours = [1.2, 2.5, 0.8, 3.2, 1.5, 0.0, 2.0];
        // Shift values slightly based on user ID for realism
        $hours_values = array_map(function($h) use ($user_id) {
            return round(max(0, $h + (($user_id % 3) - 1) * 0.4), 1);
        }, $base_hours);

        $chart_data = [
            'hours_labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'hours_values' => $hours_values,
            'cat_labels' => $cat_labels,
            'cat_values' => $cat_values
        ];

        echo json_encode([
            'success' => true,
            'stats' => [
                'active_courses' => $active_courses,
                'completion_rate' => $completion_rate,
                'study_hours' => $study_hours
            ],
            'enrollments' => $enrollments,
            'chart_data' => $chart_data
        ]);
        break;

    case 'lesson_content':
        $course_id = (int)($_GET['course_id'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course ID.']);
            exit;
        }

        // Verify user is enrolled
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        $enrollment = $stmt->fetch();

        if (!$enrollment) {
            echo json_encode(['success' => false, 'error' => 'You must be enrolled in this course to view lessons.']);
            exit;
        }

        // Fetch Course Details
        $stmt = $pdo->prepare("SELECT id, title, description, instructor FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        // Fetch Lessons
        $stmt = $pdo->prepare("SELECT id, title, content, order_index FROM lessons WHERE course_id = ? ORDER BY order_index ASC");
        $stmt->execute([$course_id]);
        $lessons = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'course' => $course,
            'enrollment' => $enrollment,
            'lessons' => $lessons
        ]);
        break;

    case 'update_progress':
        $course_id = (int)($data['course_id'] ?? 0);
        $progress = (int)($data['progress'] ?? 0);

        if ($course_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid course selection.']);
            exit;
        }

        if ($progress < 0 || $progress > 100) {
            echo json_encode(['success' => false, 'error' => 'Progress value must be between 0 and 100.']);
            exit;
        }

        // Update progress
        $stmt = $pdo->prepare("UPDATE enrollments SET progress_percent = ? WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$progress, $user_id, $course_id]);

        echo json_encode(['success' => true, 'message' => 'Progress updated successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid dashboard endpoint action.']);
        break;
}
?>
