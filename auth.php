<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';

// Only accept Ajax/XMLHttpRequest header checks or POST/GET
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$pdo = getDB();

switch ($action) {
    case 'status':
        if (isset($_SESSION['user'])) {
            echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        }
        break;

    case 'signup':
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'All fields are required.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
            exit;
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email is already registered.']);
            exit;
        }

        // Create the user hash
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user (defaults to 'student' role)
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$name, $email, $password_hash]);

        // Generate OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $pdo->prepare("INSERT INTO otp_verifications (email, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at]);

        echo json_encode(['success' => true, 'message' => 'User created. Verification code sent.']);
        break;

    case 'login':
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
            exit;
        }

        // Fetch user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
            exit;
        }

        // Generate OTP for login
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $pdo->prepare("INSERT INTO otp_verifications (email, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at]);

        echo json_encode([
            'success' => true, 
            'require_otp' => true, 
            'message' => 'Verification code sent.'
        ]);
        break;

    case 'verify_otp':
        $email = trim($data['email'] ?? '');
        $code = trim($data['code'] ?? '');
        $context = trim($data['context'] ?? '');

        if (empty($email) || empty($code)) {
            echo json_encode(['success' => false, 'error' => 'Verification code is required.']);
            exit;
        }

        // Query the database for the active, unverified OTP code
        $stmt = $pdo->prepare("
            SELECT * FROM otp_verifications 
            WHERE email = ? AND otp_code = ? AND verified = 0 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$email, $code]);
        $verification = $stmt->fetch();

        // Validate existence and timezone-independent expiration in PHP
        if (!$verification || strtotime($verification['expires_at']) < time()) {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP code.']);
            exit;
        }

        // Mark OTP as verified
        $stmt = $pdo->prepare("UPDATE otp_verifications SET verified = 1 WHERE id = ?");
        $stmt->execute([$verification['id']]);

        // Retrieve full user record to log in
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User profile not found.']);
            exit;
        }

        // Log the user in
        $_SESSION['user'] = $user;

        echo json_encode(['success' => true, 'user' => $user]);
        break;

    case 'logout':
        unset($_SESSION['user']);
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'simulated_mailbox':
        // Retrieve the last 3 unverified OTP entries for display in our simulation log
        $stmt = $pdo->query("
            SELECT email, otp_code, expires_at 
            FROM otp_verifications 
            WHERE verified = 0 
            ORDER BY created_at DESC LIMIT 3
        ");
        $emails = $stmt->fetchAll();
        echo json_encode(['success' => true, 'emails' => $emails]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid auth endpoint action.']);
        break;
}
?>
