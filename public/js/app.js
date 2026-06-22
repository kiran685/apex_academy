// Core Application Logic for ApexAcademy

document.addEventListener("DOMContentLoaded", () => {
    App.init();
});

const App = {
    currentUser: null,
    activeView: "catalogue",
    courses: [],
    currentCourse: null,
    currentLessons: [],
    activeLessonIndex: 0,
    charts: {},

    init() {
        this.setupNavigation();
        this.setupAuthForms();
        this.setupSearchFilters();
        this.setupAdminForm();
        this.checkAuthStatus();
        this.startOtpMailboxPolling();
    },

    // --- NAVIGATION & ROUTING ---
    setupNavigation() {
        // Brand logo click goes to dashboard, admin panel, or role selection
        document.querySelectorAll(".brand, .nav-logo-btn").forEach(el => {
            el.addEventListener("click", () => {
                if (this.currentUser) {
                    this.switchView(this.currentUser.role === "admin" ? "admin" : "dashboard");
                } else {
                    this.switchView("portal-select");
                }
            });
        });

        // Navigation links
        document.getElementById("nav-catalogue").addEventListener("click", () => this.switchView("catalogue"));
        document.getElementById("nav-dashboard").addEventListener("click", () => this.switchView("dashboard"));
        document.getElementById("nav-admin").addEventListener("click", () => this.switchView("admin"));
        
        // Sidebar links
        document.getElementById("sidebar-catalogue").addEventListener("click", () => this.switchView("catalogue"));
        document.getElementById("sidebar-dashboard").addEventListener("click", () => this.switchView("dashboard"));
        document.getElementById("sidebar-admin").addEventListener("click", () => this.switchView("admin"));

        // Auth links
        document.getElementById("nav-login").addEventListener("click", () => this.switchView("portal-select"));
        document.getElementById("nav-logout").addEventListener("click", () => this.handleLogout());

        // Portal Selection Card Clicks
        document.getElementById("portal-select-admin").addEventListener("click", () => {
            const container = document.getElementById("view-login");
            container.querySelector(".auth-header h2").textContent = "Administrator Sign In";
            container.querySelector(".auth-header p").textContent = "Enter details to access the administrative dashboard";
            document.getElementById("login-signup-toggle").style.display = "none";
            this.switchView("login");
        });

        document.getElementById("portal-select-student").addEventListener("click", () => {
            const container = document.getElementById("view-login");
            container.querySelector(".auth-header h2").textContent = "Student Sign In";
            container.querySelector(".auth-header p").textContent = "Enter details to access your learning center";
            document.getElementById("login-signup-toggle").style.display = "block";
            this.switchView("login");
        });

        // Simulated Mailbox collapse toggle
        const mailboxHeader = document.querySelector(".otp-console-header");
        const mailboxBody = document.querySelector(".otp-console-body");
        if (mailboxHeader && mailboxBody) {
            mailboxHeader.addEventListener("click", () => {
                const isCollapsed = mailboxBody.style.display === "none";
                mailboxBody.style.display = isCollapsed ? "block" : "none";
            });
            // Default collapsed on smaller screens, expanded on load
            mailboxBody.style.display = "block";
        }
    },

    switchView(viewName) {
        // Role Guards: Prevent Admin from accessing Student views, and Student from Admin views
        if (viewName === "admin" && (!this.currentUser || this.currentUser.role !== "admin")) {
            this.switchView(this.currentUser ? "dashboard" : "portal-select");
            return;
        }
        if ((viewName === "dashboard" || viewName === "player") && (!this.currentUser || this.currentUser.role !== "student")) {
            this.switchView(this.currentUser ? "admin" : "portal-select");
            return;
        }

        this.activeView = viewName;
        
        // Update navigation classes
        document.querySelectorAll(".nav-item, .sidebar-link").forEach(el => {
            el.classList.remove("active");
        });
        
        // Activate view panels
        document.querySelectorAll(".view-section").forEach(el => {
            el.classList.remove("active");
        });

        const activePanel = document.getElementById(`view-${viewName}`);
        if (activePanel) {
            activePanel.classList.add("active");
        }

        // Activate matching nav/sidebar items
        const navItem = document.getElementById(`nav-${viewName}`);
        if (navItem) navItem.classList.add("active");
        
        const sidebarItem = document.getElementById(`sidebar-${viewName}`);
        if (sidebarItem) sidebarItem.classList.add("active");

        // View-specific loaders
        if (viewName === "catalogue") {
            this.loadCourses();
        } else if (viewName === "dashboard") {
            this.loadDashboardData();
        } else if (viewName === "admin") {
            this.loadAdminData();
        }
    },

    // --- AUTHENTICATION ---
    async checkAuthStatus() {
        const response = await API.get("auth.php?action=status");
        if (response.success && response.user) {
            this.setLoggedInUser(response.user);
            this.switchView(response.user.role === "admin" ? "admin" : "dashboard");
        } else {
            this.setLoggedOut();
            this.switchView("portal-select");
        }
    },

    setLoggedInUser(user) {
        this.currentUser = user;
        document.body.classList.add("logged-in");
        document.body.classList.remove("logged-out");
        
        // Show/hide based on roles
        if (user.role === "admin") {
            document.body.classList.add("user-admin");
        } else {
            document.body.classList.remove("user-admin");
        }

        // Set avatar
        const avatar = document.getElementById("user-avatar-initial");
        if (avatar) {
            avatar.textContent = user.name.charAt(0).toUpperCase();
        }
        
        const profileName = document.getElementById("profile-name");
        if (profileName) {
            profileName.textContent = user.name;
        }

        // Show/hide based on roles
        if (user.role === "admin") {
            // Admin View
            document.getElementById("nav-dashboard").style.display = "none";
            document.getElementById("sidebar-dashboard").style.display = "none";
            document.getElementById("nav-admin").style.display = "block";
            document.getElementById("sidebar-admin").style.display = "flex";
        } else {
            // Student View
            document.getElementById("nav-dashboard").style.display = "block";
            document.getElementById("sidebar-dashboard").style.display = "flex";
            document.getElementById("nav-admin").style.display = "none";
            document.getElementById("sidebar-admin").style.display = "none";
        }
        
        document.getElementById("nav-login").style.display = "none";
        document.getElementById("nav-logout").style.display = "block";
    },

    setLoggedOut() {
        this.currentUser = null;
        document.body.classList.remove("logged-in", "user-admin");
        document.body.classList.add("logged-out");
        
        document.getElementById("nav-dashboard").style.display = "none";
        document.getElementById("sidebar-dashboard").style.display = "none";
        document.getElementById("nav-admin").style.display = "none";
        document.getElementById("sidebar-admin").style.display = "none";
        
        document.getElementById("nav-login").style.display = "block";
        document.getElementById("nav-logout").style.display = "none";
    },

    setupAuthForms() {
        // Sign In Form
        document.getElementById("login-form").addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = document.getElementById("login-email").value;
            const password = document.getElementById("login-password").value;

            const response = await API.post("auth.php?action=login", { email, password });
            if (response.success) {
                if (response.require_otp) {
                    this.showOtpVerification(email, "login");
                } else {
                    this.showToast("Login successful!", "success");
                    this.setLoggedInUser(response.user);
                    this.switchView(response.user.role === "admin" ? "admin" : "dashboard");
                }
            } else {
                this.showToast(response.error, "error");
            }
        });

        // Sign Up Form
        document.getElementById("signup-form").addEventListener("submit", async (e) => {
            e.preventDefault();
            const name = document.getElementById("signup-name").value;
            const email = document.getElementById("signup-email").value;
            const password = document.getElementById("signup-password").value;

            const response = await API.post("auth.php?action=signup", { name, email, password });
            if (response.success) {
                this.showOtpVerification(email, "signup");
                this.showToast("OTP code sent to email!", "warning");
            } else {
                this.showToast(response.error, "error");
            }
        });

        // OTP Verification Form
        document.getElementById("otp-form").addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = document.getElementById("otp-email-hidden").value;
            const code = document.getElementById("otp-code").value;
            const context = document.getElementById("otp-context").value;

            const response = await API.post("auth.php?action=verify_otp", { email, code, context });
            if (response.success) {
                this.showToast("Verification successful!", "success");
                this.setLoggedInUser(response.user);
                this.switchView(response.user.role === "admin" ? "admin" : "dashboard");
            } else {
                this.showToast(response.error, "error");
            }
        });

        // Toggle Switch Auth UI
        document.getElementById("switch-to-signup").addEventListener("click", () => {
            this.switchView("signup");
        });
        document.getElementById("switch-to-login").addEventListener("click", () => {
            this.switchView("login");
        });
    },

    showOtpVerification(email, context) {
        document.getElementById("otp-email-display").textContent = email;
        document.getElementById("otp-email-hidden").value = email;
        document.getElementById("otp-context").value = context;
        document.getElementById("otp-code").value = "";
        this.switchView("otp");
    },

    async handleLogout() {
        const response = await API.post("auth.php?action=logout");
        if (response.success) {
            this.showToast("Successfully logged out", "success");
            this.setLoggedOut();
            this.switchView("catalogue");
        }
    },

    // --- OTP MAILBOX POLLING (For testing visual convenience) ---
    startOtpMailboxPolling() {
        this.pollOtpMailbox();
        setInterval(() => this.pollOtpMailbox(), 3000);
    },

    async pollOtpMailbox() {
        const response = await API.get("auth.php?action=simulated_mailbox");
        const listContainer = document.getElementById("otp-list-console");
        if (!listContainer) return;
        
        if (response.success && response.emails && response.emails.length > 0) {
            listContainer.innerHTML = "";
            response.emails.forEach(item => {
                const div = document.createElement("div");
                div.className = "otp-console-log";
                div.innerHTML = `
                    <div><strong>TO:</strong> ${escapeHtml(item.email)}</div>
                    <div><strong>SUBJ:</strong> Apex OTP Verification Code</div>
                    <div><strong>CODE:</strong> <span class="otp-code-highlight">${escapeHtml(item.otp_code)}</span></div>
                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">Expires: ${item.expires_at}</div>
                `;
                listContainer.appendChild(div);
            });
        } else {
            listContainer.innerHTML = `<div style="color:var(--text-muted);">No messages in simulation queue... Try signing up or logging in to trigger an OTP code.</div>`;
        }
    },

    // --- COURSE CATALOGUE (SEARCH / FILTER) ---
    setupSearchFilters() {
        const searchInput = document.getElementById("search-bar");
        const catFilter = document.getElementById("filter-category");
        const diffFilter = document.getElementById("filter-difficulty");

        const triggerFilter = () => {
            const query = searchInput.value.toLowerCase();
            const cat = catFilter.value;
            const diff = diffFilter.value;
            this.renderCourses(query, cat, diff);
        };

        searchInput.addEventListener("keyup", triggerFilter);
        catFilter.addEventListener("change", triggerFilter);
        diffFilter.addEventListener("change", triggerFilter);
    },

    async loadCourses() {
        const response = await API.get("courses.php?action=list");
        if (response.success) {
            this.courses = response.courses;
            this.renderCourses();
        }
    },

    renderCourses(searchQuery = "", categoryFilter = "", difficultyFilter = "") {
        const grid = document.getElementById("course-grid");
        if (!grid) return;
        grid.innerHTML = "";

        const filtered = this.courses.filter(course => {
            const matchesSearch = course.title.toLowerCase().includes(searchQuery) || 
                                  course.instructor.toLowerCase().includes(searchQuery) ||
                                  course.description.toLowerCase().includes(searchQuery);
            const matchesCat = categoryFilter === "" || course.category === categoryFilter;
            const matchesDiff = difficultyFilter === "" || course.difficulty === difficultyFilter;
            return matchesSearch && matchesCat && matchesDiff;
        });

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                    No courses match your search criteria.
                </div>`;
            return;
        }

        filtered.forEach(course => {
            const card = document.createElement("div");
            card.className = "course-card glass";
            
            // Generate difficulty class
            const diffClass = course.difficulty.toLowerCase();
            
            // Check enrollment status if logged in
            let actionBtnHtml = "";
            if (this.currentUser) {
                if (course.enrolled) {
                    actionBtnHtml = `<button class="glow-btn" style="padding: 8px 16px; font-size: 0.85rem;" onclick="App.launchCoursePlayer(${course.id})">Resume</button>`;
                } else {
                    actionBtnHtml = `<button class="glow-btn" style="padding: 8px 16px; font-size: 0.85rem;" onclick="App.enrollCourse(${course.id})">Enroll</button>`;
                }
            } else {
                actionBtnHtml = `<button class="glow-btn" style="padding: 8px 16px; font-size: 0.85rem;" onclick="App.switchView('login')">Unlock</button>`;
            }

            // Emote based on category
            let emote = "💻";
            if (course.category.toLowerCase() === 'design') emote = "🎨";
            else if (course.category.toLowerCase() === 'business') emote = "📈";

            card.innerHTML = `
                <div class="course-thumbnail">
                    ${emote}
                    <span class="badge badge-${diffClass} course-badge">${course.difficulty}</span>
                </div>
                <div class="course-body">
                    <span class="course-cat">${escapeHtml(course.category)}</span>
                    <h4 class="course-title">${escapeHtml(course.title)}</h4>
                    <p class="course-desc">${escapeHtml(course.description)}</p>
                    <div class="course-footer">
                        <div class="instructor-info">
                            <div>By <strong>${escapeHtml(course.instructor)}</strong></div>
                        </div>
                        <div class="course-duration">
                            ${course.duration_hours} hrs
                        </div>
                    </div>
                    <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                        ${actionBtnHtml}
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    },

    async enrollCourse(courseId) {
        if (!this.currentUser) {
            this.switchView("login");
            return;
        }
        const response = await API.post("courses.php?action=enroll", { course_id: courseId });
        if (response.success) {
            this.showToast("Successfully enrolled in course!", "success");
            this.loadCourses();
        } else {
            this.showToast(response.error, "error");
        }
    },

    // --- STUDENT DASHBOARD & CHARTS ---
    async loadDashboardData() {
        if (!this.currentUser) return;
        const response = await API.get("dashboard.php?action=student_stats");
        if (response.success) {
            // Update counts
            document.getElementById("dash-active-count").textContent = response.stats.active_courses;
            document.getElementById("dash-completion-rate").textContent = response.stats.completion_rate + "%";
            document.getElementById("dash-hours").textContent = response.stats.study_hours + "h";
            
            // Render progress list
            this.renderEnrollmentsList(response.enrollments);

            // Render Charts
            this.renderDashboardCharts(response.chart_data);
        }
    },

    renderEnrollmentsList(enrollments) {
        const list = document.getElementById("dash-enrollments-list");
        if (!list) return;
        list.innerHTML = "";

        if (enrollments.length === 0) {
            list.innerHTML = `<div style="text-align: center; color: var(--text-muted); padding: 20px;">You are not enrolled in any courses yet. Go to <a href="#" style="color:var(--primary)" onclick="App.switchView('catalogue')">Courses</a> to sign up!</div>`;
            return;
        }

        enrollments.forEach(item => {
            const row = document.createElement("div");
            row.className = "enrollment-item";
            row.innerHTML = `
                <div style="flex: 1;">
                    <h4 style="font-weight: 600; margin-bottom: 5px;">${escapeHtml(item.title)}</h4>
                    <span style="font-size:0.8rem; color:var(--text-muted);">Instructor: ${escapeHtml(item.instructor)}</span>
                </div>
                <div class="progress-bar-wrapper">
                    <div class="progress-info">
                        <span>Progress</span>
                        <span>${item.progress_percent}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: ${item.progress_percent}%;"></div>
                    </div>
                </div>
                <button class="glow-btn" style="padding: 6px 14px; font-size:0.85rem;" onclick="App.launchCoursePlayer(${item.id})">Study</button>
            `;
            list.appendChild(row);
        });
    },

    renderDashboardCharts(chartData) {
        // Reset old chart instances to avoid redraw overlapping
        if (this.charts.hours) this.charts.hours.destroy();
        if (this.charts.cats) this.charts.cats.destroy();

        // 1. Line Chart: Weekly Study Hours
        const ctxHours = document.getElementById("chart-study-hours");
        if (ctxHours) {
            this.charts.hours = new Chart(ctxHours, {
                type: 'line',
                data: {
                    labels: chartData.hours_labels, // ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                    datasets: [{
                        label: 'Study Hours',
                        data: chartData.hours_values,
                        borderColor: '#6e44ff',
                        backgroundColor: 'rgba(110, 68, 255, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#8a99ad' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#8a99ad' }
                        }
                    }
                }
            });
        }

        // 2. Doughnut Chart: Enrollment Categories
        const ctxCats = document.getElementById("chart-categories");
        if (ctxCats) {
            this.charts.cats = new Chart(ctxCats, {
                type: 'doughnut',
                data: {
                    labels: chartData.cat_labels, // e.g. ['Design', 'Development', 'Business']
                    datasets: [{
                        data: chartData.cat_values,
                        backgroundColor: ['#6e44ff', '#00f5ff', '#ffaa32', '#ff4b4b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#8a99ad', boxWidth: 12 }
                        }
                    }
                }
            });
        }
    },

    // --- COURSE PLAYER (LESSONS) ---
    async launchCoursePlayer(courseId) {
        const response = await API.get(`dashboard.php?action=lesson_content&course_id=${courseId}`);
        if (response.success) {
            this.currentCourse = response.course;
            this.currentLessons = response.lessons;
            this.activeLessonIndex = 0;

            // Find current active lesson based on enrollment progress if possible
            const progress = response.enrollment.progress_percent;
            if (progress > 0 && response.lessons.length > 0) {
                const index = Math.min(
                    Math.floor((progress / 100) * response.lessons.length),
                    response.lessons.length - 1
                );
                this.activeLessonIndex = index;
            }

            // Update UI Title
            document.getElementById("player-course-title").textContent = response.course.title;

            // Render Sidebar Playlist
            this.renderPlayerPlaylist();
            
            // Render Active Lesson
            this.renderActiveLesson();

            this.switchView("player");
        } else {
            this.showToast(response.error, "error");
        }
    },

    renderPlayerPlaylist() {
        const sidebar = document.getElementById("player-playlist");
        if (!sidebar) return;
        sidebar.innerHTML = "";

        this.currentLessons.forEach((lesson, index) => {
            const item = document.createElement("div");
            item.className = `playlist-item ${index === this.activeLessonIndex ? 'active' : ''}`;
            item.innerHTML = `
                <div style="font-weight:600; font-size:0.75rem; min-width: 20px; text-align: center;">${index + 1}</div>
                <div style="font-size:0.85rem; line-height:1.2;">${escapeHtml(lesson.title)}</div>
            `;
            item.addEventListener("click", () => {
                this.activeLessonIndex = index;
                this.renderActiveLesson();
                
                // Highlight item
                document.querySelectorAll(".playlist-item").forEach(el => el.classList.remove("active"));
                item.classList.add("active");
            });
            sidebar.appendChild(item);
        });
    },

    renderActiveLesson() {
        const lesson = this.currentLessons[this.activeLessonIndex];
        const contentBox = document.getElementById("player-lesson-body");
        if (!lesson || !contentBox) return;

        contentBox.innerHTML = `
            <h2>${escapeHtml(lesson.title)}</h2>
            <div style="margin-top:20px; line-height:1.6;">
                ${lesson.content}
            </div>
        `;

        // Configure Navigation Buttons
        const prevBtn = document.getElementById("player-prev-btn");
        const nextBtn = document.getElementById("player-next-btn");

        prevBtn.style.display = this.activeLessonIndex > 0 ? "block" : "none";
        
        if (this.activeLessonIndex < this.currentLessons.length - 1) {
            nextBtn.textContent = "Next Lesson →";
            nextBtn.style.display = "block";
        } else {
            nextBtn.textContent = "Complete Course ✓";
            nextBtn.style.display = "block";
        }
        
        // Setup Button Action Listeners
        prevBtn.onclick = () => {
            if (this.activeLessonIndex > 0) {
                this.activeLessonIndex--;
                this.renderActiveLesson();
                this.renderPlayerPlaylist();
            }
        };

        nextBtn.onclick = () => {
            if (this.activeLessonIndex < this.currentLessons.length - 1) {
                this.activeLessonIndex++;
                this.updateCourseProgress();
                this.renderActiveLesson();
                this.renderPlayerPlaylist();
            } else {
                // Course Completed!
                this.completeCourse();
            }
        };
    },

    async updateCourseProgress() {
        const total = this.currentLessons.length;
        const currentCompleted = this.activeLessonIndex; // represents lessons completed prior to active
        const percent = Math.round((currentCompleted / total) * 100);
        
        await API.post("dashboard.php?action=update_progress", {
            course_id: this.currentCourse.id,
            progress: percent
        });
    },

    async completeCourse() {
        const response = await API.post("dashboard.php?action=update_progress", {
            course_id: this.currentCourse.id,
            progress: 100
        });
        if (response.success) {
            this.showToast("Congratulations! You have completed the course!", "success");
            this.switchView("dashboard");
        } else {
            this.showToast(response.error, "error");
        }
    },

    // --- ADMIN PANEL & COURSE MANAGEMENT ---
    async loadAdminData() {
        if (!this.currentUser || this.currentUser.role !== "admin") return;
        const response = await API.get("admin.php?action=stats");
        if (response.success) {
            document.getElementById("admin-total-users").textContent = response.stats.total_users;
            document.getElementById("admin-total-enrollments").textContent = response.stats.total_enrollments;
            document.getElementById("admin-active-courses").textContent = response.stats.active_courses;

            // Render courses list in table
            this.renderAdminCoursesTable(response.courses);
        }
    },

    renderAdminCoursesTable(courses) {
        const tbody = document.getElementById("admin-courses-tbody");
        if (!tbody) return;
        tbody.innerHTML = "";

        courses.forEach(course => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><strong>#${course.id}</strong></td>
                <td>${escapeHtml(course.title)}</td>
                <td><span class="badge badge-beginner">${escapeHtml(course.category)}</span></td>
                <td>${course.difficulty}</td>
                <td>${escapeHtml(course.instructor)}</td>
                <td>${course.enrollment_count} students</td>
            `;
            tbody.appendChild(tr);
        });
    },

    setupAdminForm() {
        const form = document.getElementById("admin-course-form");
        if (!form) return;
        
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const title = document.getElementById("new-course-title").value;
            const description = document.getElementById("new-course-desc").value;
            const category = document.getElementById("new-course-cat").value;
            const difficulty = document.getElementById("new-course-diff").value;
            const instructor = document.getElementById("new-course-inst").value;
            const duration = document.getElementById("new-course-duration").value;

            const response = await API.post("admin.php?action=create_course", {
                title, description, category, difficulty, instructor, duration_hours: duration
            });

            if (response.success) {
                this.showToast("Course created successfully!", "success");
                form.reset();
                this.loadAdminData();
            } else {
                this.showToast(response.error, "error");
            }
        });
    },

    // --- TOAST NOTIFICATIONS ---
    showToast(message, type = "success") {
        const toast = document.createElement("div");
        toast.className = `notification notification-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = "slideOut 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Helper to escape HTML characters securely
function escapeHtml(text) {
    if (!text) return "";
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Add animation stylesheet inline for slideOut
const styleSheet = document.createElement("style");
styleSheet.innerText = `
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(120%); opacity: 0; }
}
`;
document.head.appendChild(styleSheet);
