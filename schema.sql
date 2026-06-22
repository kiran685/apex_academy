-- Database Schema for ApexAcademy

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    difficulty ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255) DEFAULT NULL,
    duration_hours INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    order_index INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress_percent INT DEFAULT 0,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_course (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin account (password is 'admin123')
INSERT INTO users (name, email, password_hash, role) 
VALUES ('Apex Admin', 'admin@apex.com', '$2y$10$pE5Y8rmBpO1p1INWN5ssm.kR5ySS51.mrehH2/h5cMF12Dm0RUK/a', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Seed Courses
INSERT INTO courses (id, title, description, category, difficulty, instructor, duration_hours) VALUES
(1, 'Mastering Web Design & CSS Aesthetics', 'Learn how to construct premium, modern web applications from scratch. This course covers HSL color theory, custom CSS grids, modern glassmorphism UI/UX design, custom layouts, and interactive micro-animations that will wow your users.', 'Design', 'Beginner', 'Sophia Vance', 12),
(2, 'Deep Dive into Modern JavaScript', 'Unleash the full power of ES6+ JavaScript. Master asynchronous flows, fetch APIs, local state storage, dynamic DOM structures, routing mechanisms, and integrating charting modules like Chart.js.', 'Development', 'Intermediate', 'Marcus Chen', 20),
(3, 'Building Scalable Backends with PHP & MySQL', 'Master server-side development using modern PHP and MySQL. Learn PDO query structures, secure session validation, clean authentication cycles, SQL query optimizations, and database design relationships.', 'Development', 'Advanced', 'Dr. Sarah Jenkins', 25),
(4, 'Product Strategy & Launch Frameworks', 'A comprehensive guide to product strategy, user discovery, wireframing, feature scoping, and metrics planning to turn ideas into successful market-ready products.', 'Business', 'Beginner', 'Elena Rostova', 8)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Seed Lessons for CSS course
INSERT INTO lessons (course_id, title, content, order_index) VALUES
(1, 'Introduction to Visual Hierarchy', '<h3>1. What is Visual Hierarchy?</h3><p>Visual hierarchy refers to the arrangement or presentation of elements in a way that implies importance. By utilizing scale, contrast, color, and whitespace, you guide the reader\'s eye along a specific reading path.</p><h3>2. The Golden Ratio and Scaling</h3><p>Using consistent size scales (like 1.25x or 1.5x ratios) for headings, body text, and elements creates a natural, rhythmic balance across your interface.</p>', 1),
(1, 'Mastering HSL Color Systems', '<h3>1. Why HSL (Hue, Saturation, Lightness)?</h3><p>Unlike HEX or RGB, HSL makes it incredibly easy to programmatically or logically adjust contrast. By keeping the Hue constant and varying Saturation or Lightness, you can generate complete palettes (shadows, borders, highlights) from a single base color.</p><h3>2. Example Setup</h3><pre><code>:root {\n  --primary-h: 250;\n  --primary-s: 85%;\n  --primary-l: 65%;\n  --primary: hsl(var(--primary-h), var(--primary-s), var(--primary-l));\n}</code></pre>', 2),
(1, 'Implementing Premium Glassmorphism', '<h3>1. The Formula for Glassmorphism</h3><p>Glassmorphism relies on stacked translucent layers combined with heavy blur and high-contrast borders. To achieve this:</p><ul><li>Use a semi-transparent background color (`rgba` or `hsla`).</li><li>Apply `backdrop-filter: blur(12px)`.</li><li>Add a thin, slightly reflective border.</li><li>Ensure the background behind the container has high-contrast colors or shapes.</li></ul>', 3)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Seed Lessons for JavaScript course
INSERT INTO lessons (course_id, title, content, order_index) VALUES
(2, 'Understanding the Event Loop', '<h3>1. Asynchronous JavaScript</h3><p>JavaScript is a single-threaded language, meaning it can only execute one command at a time. The event loop is the engine that allows JS to run asynchronous callbacks by delegating tasks like network requests to the browser APIs and processing them via queues.</p>', 1),
(2, 'Modern AJAX and Fetch API', '<h3>1. AJAX in 2026</h3><p>AJAX is no longer synonymous with jQuery. The native modern standard is the `fetch()` API combined with `async/await` syntax. This provides clean, readable promises for retrieving server data in real-time without reloading pages.</p>', 2),
(2, 'Interactive Charting with Chart.js', '<h3>1. Why Chart.js?</h3><p>Chart.js is a lightweight, responsive library that renders diagrams in HTML5 canvas. It allows you to display multi-dimensional data like weekly learning logs, progress charts, and admin user counts with simple JavaScript object configurations.</p>', 3)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Seed Lessons for PHP course
INSERT INTO lessons (course_id, title, content, order_index) VALUES
(3, 'PDO vs Old MySQL Extensions', '<h3>1. Secure Queries with PDO</h3><p>The PHP Data Objects (PDO) extension provides a lightweight, consistent interface for accessing databases. Unlike deprecated extensions, PDO enforces the use of prepared statements, which completely neutralizes SQL injection vulnerabilities.</p>', 1),
(3, 'Designing Robust SQL Relations', '<h3>1. Normalization and Keys</h3><p>Learn how to design your tables to avoid redundancy. We will cover Primary Keys (PK), Foreign Keys (FK), and index constraints. In this project, the `enrollments` table joins the `users` and `courses` tables to represent a many-to-many relationship.</p>', 2)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Seed Lessons for Product course
INSERT INTO lessons (course_id, title, content, order_index) VALUES
(4, 'Defining the Minimum Viable Product (MVP)', '<h3>1. Scoping your Project</h3><p>An MVP focuses on the absolute core values of your application. When building your capstone project, prioritize a solid authentication cycle, standard database relations, and clean searching before spending time on tertiary features.</p>', 1),
(4, 'Conducting User Feedback Cycles', '<h3>1. Iteration and Feedback</h3><p>Once your prototype is running, watch users interact with it without guiding them. Track where they get stuck, what they find confusing, and refine the interface dynamically to improve UX.</p>', 2)
ON DUPLICATE KEY UPDATE id=id;
