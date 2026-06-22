<?php
$config_file = __DIR__ . '/config.php';

if (!file_exists($config_file)) {
    // If the DB isn't configured, redirect to installer
    header('Location: install.php');
    exit;
}

require_once $config_file;

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Render a clean database failure page with link to reconfigure
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Database Connection Error</title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Outfit', sans-serif;
                    background-color: hsl(222, 47%, 11%);
                    color: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .error-card {
                    background: hsla(222, 47%, 16%, 0.7);
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(255, 75, 75, 0.3);
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 500px;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                }
                h2 { color: hsl(0, 85%, 60%); margin-top: 0; }
                p { color: hsl(215, 20%, 65%); line-height: 1.5; font-size: 0.95rem; }
                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: hsl(250, 85%, 65%);
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                }
                a:hover { background: hsl(250, 85%, 70%); }
            </style>
        </head>
        <body>
            <div class="error-card">
                <h2>Database Connection Failed</h2>
                <p>Could not connect to the database. This might be due to incorrect configuration, or the MySQL service is offline.</p>
                <p><strong>Error Message:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
                <a href="install.php">Reconfigure Database Settings</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?>
