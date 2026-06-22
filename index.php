<?php
session_start();

// Verify database configuration exists
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/db.php';
// Bootstraps connection just to verify it works
getDB();

// Fetch initial session details if logged in
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexAcademy - Premium E-Learning</title>
    <!-- Outfit Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Core Application Stylesheet -->
    <link rel="stylesheet" href="public/css/style.css">
    <!-- Chart.js for analytics dashboards -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="<?php echo $user ? 'logged-in' : 'logged-out'; ?> <?php echo ($user && $user['role'] === 'admin') ? 'user-admin' : ''; ?>">
    
    <div class="app-container">
        
        <!-- Header Navbar -->
        <header class="navbar glass">
            <div class="brand">ApexAcademy</div>
            
            <div class="nav-links">
                <div id="nav-catalogue" class="nav-item active">Courses</div>
                <div id="nav-dashboard" class="nav-item" style="display: none;">My Dashboard</div>
                <div id="nav-admin" class="nav-item" style="display: none;">Admin Panel</div>
                <div id="nav-login" class="nav-item">Sign In</div>
                <div id="nav-logout" class="nav-item" style="display: none;">Sign Out</div>
                
                <div class="user-profile logged-in-only">
                    <div id="user-avatar-initial" class="avatar">U</div>
                    <span id="profile-name" style="font-weight: 500; font-size: 0.9rem;">User</span>
                </div>
            </div>
        </header>

        <!-- Main Workspace Shell -->
        <div class="main-wrapper">
            
            <!-- Sidebar Navigation -->
            <aside class="sidebar glass">
                <div id="sidebar-catalogue" class="sidebar-link active">
                    <span>📚</span> Explore Courses
                </div>
                <div id="sidebar-dashboard" class="sidebar-link" style="display: none;">
                    <span>📊</span> Student Dashboard
                </div>
                <div id="sidebar-admin" class="sidebar-link" style="display: none;">
                    <span>🛡️</span> Administrative Portal
                </div>
            </aside>

            <!-- Main Dynamic Content Panel -->
            <main class="content">
                
                <!-- 0. PORTAL ROLE SELECTION -->
                <section id="view-portal-select" class="view-section active">
                    <div style="max-width: 800px; margin: 60px auto; text-align: center;">
                        <h2 style="font-size: 2.6rem; font-weight: 800; margin-bottom: 12px; background: linear-gradient(135deg, #fff 0%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing:-0.5px;">
                            Welcome to ApexAcademy
                        </h2>
                        <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 50px;">
                            Please select your gateway portal to access the platform.
                        </p>
                        
                        <div class="portal-select-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
                            
                            <!-- Admin Card -->
                            <div class="portal-card glass" id="portal-select-admin" style="padding: 45px 35px; border-radius: 20px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 15px; text-align: center;">
                                <div style="font-size: 4rem; margin-bottom: 10px;">🛡️</div>
                                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 5px;">Administrator Portal</h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                                    Manage global courses, coordinate course structures, publish new materials, and analyze student analytics.
                                </p>
                                <button class="glow-btn" style="padding: 12px 20px; font-size: 0.95rem; margin-top: 15px; width: 100%;">
                                    Enter Admin Portal
                                </button>
                            </div>
                            
                            <!-- Student Card -->
                            <div class="portal-card glass" id="portal-select-student" style="padding: 45px 35px; border-radius: 20px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 15px; text-align: center;">
                                <div style="font-size: 4rem; margin-bottom: 10px;">🎓</div>
                                <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 5px;">Student Learning Center</h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                                    Explore available courses, enroll in modern skills topics, study lesson content, and view interactive performance logs.
                                </p>
                                <button class="glow-btn" style="padding: 12px 20px; font-size: 0.95rem; margin-top: 15px; width: 100%; background: linear-gradient(135deg, var(--accent) 0%, hsl(170, 85%, 45%) 100%); box-shadow: 0 4px 15px var(--accent-glow);">
                                    Enter Student Center
                                </button>
                            </div>
                            
                        </div>
                    </div>
                </section>
                
                <!-- 1. COURSE CATALOGUE EXPLORER -->
                <section id="view-catalogue" class="view-section">
                    <div class="catalogue-header">
                        <div>
                            <h2 style="font-weight:700; margin-bottom:5px;">Explore Courses</h2>
                            <p style="color:var(--text-muted); font-size:0.95rem;">Unlock professional skills with seed courses taught by industry veterans.</p>
                        </div>
                        <div class="search-filter-bar">
                            <div class="search-input-wrapper">
                                <input type="text" id="search-bar" placeholder="Search by titles, instructors, topics...">
                            </div>
                            <select id="filter-category" class="filter-select">
                                <option value="">All Categories</option>
                                <option value="Development">Development</option>
                                <option value="Design">Design</option>
                                <option value="Business">Business</option>
                            </select>
                            <select id="filter-difficulty" class="filter-select">
                                <option value="">All Difficulties</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="course-grid" class="grid-layout">
                        <!-- Dynamic Course Cards Loaded via AJAX -->
                    </div>
                </section>

                <!-- 2. LOGIN PANEL -->
                <section id="view-login" class="view-section">
                    <div class="auth-container glass">
                        <div class="auth-header">
                            <h2>Welcome Back</h2>
                            <p>Enter details to sign in and access courses</p>
                        </div>
                        <form id="login-form">
                            <div class="form-group">
                                <label for="login-email">Email Address</label>
                                <input type="email" id="login-email" placeholder="admin@apex.com (or your student email)" required>
                            </div>
                            <div class="form-group">
                                <label for="login-password">Password</label>
                                <input type="password" id="login-password" placeholder="admin123 (or your student password)" required>
                            </div>
                            <button type="submit" class="glow-btn auth-btn">Sign In</button>
                        </form>
                        <div class="auth-switch" id="login-signup-toggle">
                            Don't have an account? <span id="switch-to-signup">Create Account</span>
                        </div>
                    </div>
                </section>

                <!-- 3. SIGN UP PANEL -->
                <section id="view-signup" class="view-section">
                    <div class="auth-container glass">
                        <div class="auth-header">
                            <h2>Join ApexAcademy</h2>
                            <p>Create a student account to get started</p>
                        </div>
                        <form id="signup-form">
                            <div class="form-group">
                                <label for="signup-name">Full Name</label>
                                <input type="text" id="signup-name" placeholder="John Doe" required>
                            </div>
                            <div class="form-group">
                                <label for="signup-email">Email Address</label>
                                <input type="email" id="signup-email" placeholder="johndoe@email.com" required>
                            </div>
                            <div class="form-group">
                                <label for="signup-password">Choose Password</label>
                                <input type="password" id="signup-password" placeholder="Min 6 characters" required>
                            </div>
                            <button type="submit" class="glow-btn auth-btn">Register Student</button>
                        </form>
                        <div class="auth-switch">
                            Already registered? <span id="switch-to-login">Sign In</span>
                        </div>
                    </div>
                </section>

                <!-- 4. OTP CODE VERIFICATION PANEL -->
                <section id="view-otp" class="view-section">
                    <div class="auth-container glass" style="border-color: var(--warning);">
                        <div class="auth-header">
                            <h2 style="color: var(--warning);">OTP Verification</h2>
                            <p>For security, we simulated sending a code to: <br><strong id="otp-email-display" style="color:#fff;"></strong></p>
                        </div>
                        <form id="otp-form">
                            <input type="hidden" id="otp-email-hidden">
                            <input type="hidden" id="otp-context">
                            <div class="form-group">
                                <label for="otp-code">Enter 6-Digit OTP Code</label>
                                <input type="text" id="otp-code" placeholder="Check mailbox simulation below" maxlength="6" style="text-align: center; font-size: 1.5rem; letter-spacing: 4px; font-family: monospace;" required>
                            </div>
                            <button type="submit" class="glow-btn auth-btn" style="background: linear-gradient(135deg, var(--warning) 0%, hsl(20, 90%, 50%) 100%); box-shadow: 0 4px 15px rgba(255, 170, 50, 0.3);">Verify OTP & Log In</button>
                        </form>
                    </div>
                </section>

                <!-- 5. STUDENT DASHBOARD -->
                <section id="view-dashboard" class="view-section">
                    <h2 style="font-weight:700; margin-bottom:5px;">Student Dashboard</h2>
                    <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:30px;">Track your study durations, completion rates, and active courses.</p>
                    
                    <div class="stats-row">
                        <div class="stat-card glass">
                            <span class="stat-num" id="dash-active-count">0</span>
                            <span class="stat-label">Active Enrollments</span>
                        </div>
                        <div class="stat-card glass">
                            <span class="stat-num" id="dash-completion-rate">0%</span>
                            <span class="stat-label">Average Completion</span>
                        </div>
                        <div class="stat-card glass">
                            <span class="stat-num" id="dash-hours">0h</span>
                            <span class="stat-label">Logged Study Hours</span>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div style="display:flex; flex-direction:column; gap:30px;">
                            <div class="dashboard-card glass">
                                <h3>Active Courses Progress</h3>
                                <div id="dash-enrollments-list" class="enrollment-list">
                                    <!-- Dynamic Enrollments Loaded via AJAX -->
                                </div>
                            </div>
                        </div>
                        
                        <div style="display:flex; flex-direction:column; gap:30px;">
                            <div class="dashboard-card glass">
                                <h3>Study Engagement Logs</h3>
                                <div class="chart-container">
                                    <canvas id="chart-study-hours"></canvas>
                                </div>
                            </div>
                            <div class="dashboard-card glass">
                                <h3>Subject Categories Distribution</h3>
                                <div class="chart-container">
                                    <canvas id="chart-categories"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 6. COURSE LESSONS PLAYER -->
                <section id="view-player" class="view-section">
                    <div style="margin-bottom: 25px;">
                        <span class="nav-logo-btn" style="color:var(--primary); cursor:pointer; font-weight:600;">← Back to Dash</span>
                        <h2 id="player-course-title" style="margin-top: 10px; font-weight:700;">Course Title</h2>
                    </div>

                    <div class="player-layout">
                        <div class="player-main">
                            <div id="player-lesson-body" class="lesson-content">
                                <!-- Dynamic Lesson HTML Content Rendered via JS -->
                            </div>
                            <div class="lesson-nav">
                                <button id="player-prev-btn" class="glow-btn" style="padding:10px 20px; background:rgba(255,255,255,0.05); border:1px solid var(--border); box-shadow:none;">← Previous Lesson</button>
                                <button id="player-next-btn" class="glow-btn" style="padding:10px 20px;">Next Lesson →</button>
                            </div>
                        </div>
                        
                        <div class="playlist-sidebar glass" style="border-radius:16px; padding:20px; align-self: flex-start;">
                            <div class="playlist-header">Course Playlist</div>
                            <div id="player-playlist" class="sidebar-playlist">
                                <!-- Dynamic Lesson Playlist Items -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 7. ADMINISTRATIVE PORTAL -->
                <section id="view-admin" class="view-section">
                    <h2 style="font-weight:700; margin-bottom:5px;">Administrative Panel</h2>
                    <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:30px;">Access global course stats, add course assets, and monitor platform activities.</p>

                    <div class="stats-row">
                        <div class="stat-card glass" style="border-color: var(--primary);">
                            <span class="stat-num" id="admin-total-users">0</span>
                            <span class="stat-label">Total Users</span>
                        </div>
                        <div class="stat-card glass" style="border-color: var(--primary);">
                            <span class="stat-num" id="admin-total-enrollments">0</span>
                            <span class="stat-label">Active Enrollments</span>
                        </div>
                        <div class="stat-card glass" style="border-color: var(--primary);">
                            <span class="stat-num" id="admin-active-courses">0</span>
                            <span class="stat-label">Platform Courses</span>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="admin-table-card glass" style="overflow-x:auto;">
                            <h3>Course Catalogue Summary</h3>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Difficulty</th>
                                        <th>Instructor</th>
                                        <th>Enrolled Users</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-courses-tbody">
                                    <!-- Dynamic Table Rows -->
                                </tbody>
                            </table>
                        </div>

                        <div class="dashboard-card glass">
                            <h3>Create New Course Asset</h3>
                            <form id="admin-course-form">
                                <div class="form-group">
                                    <label for="new-course-title">Course Title</label>
                                    <input type="text" id="new-course-title" required>
                                </div>
                                <div class="form-group">
                                    <label for="new-course-desc">Short Description</label>
                                    <textarea id="new-course-desc" rows="3" required></textarea>
                                </div>
                                <div class="row" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="new-course-cat">Category</label>
                                        <select id="new-course-cat" required>
                                            <option value="Development">Development</option>
                                            <option value="Design">Design</option>
                                            <option value="Business">Business</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="new-course-diff">Difficulty</label>
                                        <select id="new-course-diff" required>
                                            <option value="Beginner">Beginner</option>
                                            <option value="Intermediate">Intermediate</option>
                                            <option value="Advanced">Advanced</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row" style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="new-course-inst">Instructor</label>
                                        <input type="text" id="new-course-inst" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="new-course-duration">Duration (Hours)</label>
                                        <input type="number" id="new-course-duration" min="1" required>
                                    </div>
                                </div>
                                <button type="submit" class="glow-btn auth-btn">Publish Course</button>
                            </form>
                        </div>
                    </div>
                </section>

            </main>
        </div>

        <!-- Simulated Mailbox Console for OTP Verification codes -->
        <div class="otp-console">
            <div class="otp-console-header">
                <span>✉️ Simulated Mailbox Logs</span>
                <span id="otp-console-toggle">▾</span>
            </div>
            <div class="otp-console-body" id="otp-list-console">
                <div style="color:var(--text-muted);">No messages in simulation queue... Try signing up or logging in to trigger an OTP code.</div>
            </div>
        </div>

    </div>

    <!-- Inject Bootstrap Session to JS -->
    <script>
        window.bootstrapUser = <?php echo json_encode($user); ?>;
        if (window.bootstrapUser) {
            // Seed logged in state directly
            App.currentUser = window.bootstrapUser;
        }
    </script>
    <!-- Core Application Controller -->
    <script src="public/js/api.js"></script>
    <script src="public/js/app.js"></script>
</body>
</html>
