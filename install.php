<?php
session_start();

$config_file = __DIR__ . '/config.php';

// If config file already exists and connection works, redirect to main application
if (file_exists($config_file)) {
    include $config_file;
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        // Config exists but connection fails, allow setup again
        $error = "Existing config connection failed: " . $e->getMessage();
    }
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '127.0.0.1';
    $port = $_POST['port'] ?? '3306';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_password'] ?? '';
    $dbname = $_POST['dbname'] ?? 'apex_academy';

    try {
        // Connect to server (without DB name first, to create it)
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        // Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Select Database
        $pdo->exec("USE `$dbname`");

        // Read and execute schema.sql
        $schema_path = __DIR__ . '/schema.sql';
        if (!file_exists($schema_path)) {
            throw new Exception("schema.sql file is missing in the project folder!");
        }

        $sql = file_get_contents($schema_path);
        // PDO exec can run multiple queries for MySQL
        $pdo->exec($sql);

        // Generate config.php
        $config_content = "<?php\n"
            . "// Dynamically generated database config file\n"
            . "define('DB_HOST', '" . addslashes($host) . "');\n"
            . "define('DB_PORT', '" . addslashes($port) . "');\n"
            . "define('DB_NAME', '" . addslashes($dbname) . "');\n"
            . "define('DB_USER', '" . addslashes($user) . "');\n"
            . "define('DB_PASS', '" . addslashes($pass) . "');\n"
            . "?>";

        if (file_put_contents($config_file, $config_content) === false) {
            throw new Exception("Failed to write config.php. Check folder permissions.");
        }

        $success_msg = "Database installation completed successfully! You will be redirected in 3 seconds.";
        header("refresh:3;url=index.php");

    } catch (Exception $e) {
        $error_msg = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexAcademy Setup Wizard</title>
    <!-- Outfit Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: hsl(222, 47%, 11%);
            --accent-color: hsl(250, 85%, 65%);
            --accent-glow: hsla(250, 85%, 65%, 0.15);
            --surface-color: hsla(222, 47%, 16%, 0.7);
            --border-color: hsla(250, 85%, 65%, 0.2);
            --text-color: hsl(210, 40%, 98%);
            --text-muted: hsl(215, 20%, 65%);
            --success: hsl(150, 80%, 40%);
            --error: hsl(0, 85%, 60%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 10% 20%, hsla(250, 85%, 65%, 0.1) 0px, transparent 50%),
                radial-gradient(at 90% 80%, hsla(190, 85%, 50%, 0.08) 0px, transparent 50%);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 550px;
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 50px var(--accent-glow);
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff 0%, var(--accent-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            display: inline-block;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .alert-error {
            background-color: rgba(255, 75, 75, 0.1);
            border: 1px solid rgba(255, 75, 75, 0.3);
            color: var(--error);
        }

        .alert-success {
            background-color: rgba(75, 255, 150, 0.1);
            border: 1px solid rgba(75, 255, 150, 0.3);
            color: var(--success);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-color);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 10px rgba(110, 68, 255, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent-color) 0%, hsl(270, 85%, 55%) 100%);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(110, 68, 255, 0.4);
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(110, 68, 255, 0.6);
        }

        button:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">ApexAcademy Setup</div>
            <div class="subtitle">Configure your local MySQL database connection</div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="form-group">
                    <label for="host">MySQL Host</label>
                    <input type="text" id="host" name="host" value="127.0.0.1" required>
                </div>
                <div class="form-group">
                    <label for="port">Port</label>
                    <input type="text" id="port" name="port" value="3306" required>
                </div>
            </div>

            <div class="form-group">
                <label for="dbname">Database Name</label>
                <input type="text" id="dbname" name="dbname" value="apex_academy" required>
            </div>

            <div class="form-group">
                <label for="db_user">Username</label>
                <input type="text" id="db_user" name="db_user" value="root" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="db_password">Password</label>
                <input type="password" id="db_password" name="db_password" placeholder="Leave empty if none" autocomplete="new-password">
            </div>

            <button type="submit">Test & Install Database</button>
        </form>

        <div class="footer">
            &copy; 2026 ApexPlanet Software Pvt. Ltd. All rights reserved.
        </div>
    </div>
</body>
</html>
