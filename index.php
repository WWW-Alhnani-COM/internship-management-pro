<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If user is logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Management - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="home-body">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Internship System</span>
            </div>
            <div class="nav-links">
                <a href="#features" class="nav-link">Features</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
            <div class="nav-actions">
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>
            <button class="nav-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-rocket"></i>
                    <span>Integrated Internship Management</span>
                </div>
                <h1 class="hero-title">
                    Manage <span class="highlight">Internships</span> with
                    <span class="typing-text"></span>
                </h1>
                <p class="hero-description">
                    A complete platform for students, academic supervisors and site supervisors to track,
                    manage and evaluate internships professionally.
                </p>
                <div class="hero-actions">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-play"></i>
                        Get Started — Free
                    </a>
                    <a href="#features" class="btn btn-outline btn-large">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Registered Students</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Partner Companies</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfaction Rate</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="image-container">
                    <img src="https://cdn.pixabay.com/photo/2018/03/10/12/00/teamwork-3213924_1280.jpg" alt="Internship management system">
                    <div class="floating-card card-1">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress Tracking</span>
                    </div>
                    <div class="floating-card card-2">
                        <i class="fas fa-tasks"></i>
                        <span>Task Management</span>
                    </div>
                    <div class="floating-card card-3">
                        <i class="fas fa-comments"></i>
                        <span>Direct Communication</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
                <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
                <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Platform Features</h2>
                <p>All the tools you need to manage internships professionally</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Student Management</h3>
                    <p>Register and track students' internships with a full-featured dashboard</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Academic Supervisors</h3>
                    <p>Empower supervisors to follow up and evaluate students effectively</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Site Supervisors</h3>
                    <p>A complete platform for site supervisors to monitor and evaluate performance</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Task Management</h3>
                    <p>Create weekly tasks and track completion with detailed reports</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Weekly Reports</h3>
                    <p>Upload and evaluate weekly internship reports</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Evaluations & Reports</h3>
                    <p>Comprehensive evaluation system with analytics and summaries</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How it Works</h2>
                <p>3 simple steps to get started</p>
            </div>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Create an Account</h3>
                        <p>Register and choose your role (student, academic supervisor, site supervisor)</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Manage Internships</h3>
                        <p>Add companies, create tasks and manage reports</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Track & Evaluate</h3>
                        <p>Monitor progress and perform final evaluations with full reports</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to action -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to start managing internships?</h2>
                <p>Join thousands who trust our platform for internship management</p>
                <div class="cta-actions">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i>
                        Create a Free Account
                    </a>
                    <a href="login.php" class="btn btn-outline btn-large">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Internship System</span>
                    </div>
                    <p>A full-featured internship management platform for efficient supervision and evaluation.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                    <a href="#features">Features</a>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <a href="#">Help</a>
                    <a href="#">Contact</a>
                    <a href="#">FAQ</a>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p><i class="fas fa-phone"></i> +966 50 123 4567</p>
                    <p><i class="fas fa-envelope"></i> info@internship-system.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Internship Management. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>