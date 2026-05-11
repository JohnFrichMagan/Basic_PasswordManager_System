<?php
require_once 'config.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Manager - Enterprise Security Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Grid */
        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(16, 185, 129, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
            pointer-events: none;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Animated Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1), transparent);
            border-radius: 50%;
            animation: float 25s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
            25% { transform: translateY(-50px) translateX(30px) rotate(90deg); }
            50% { transform: translateY(-100px) translateX(0) rotate(180deg); }
            75% { transform: translateY(-50px) translateX(-30px) rotate(270deg); }
        }

        /* Main Container */
        .landing-container {
            position: relative;
            z-index: 1;
        }

        /* Navigation Bar */
        .navbar-custom {
            padding: 1rem 2rem;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(16, 185, 129, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #10B981, #34D399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: #CBD5E1;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #10B981;
            transition: width 0.3s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a:hover {
            color: #10B981;
        }

        /* Hero Section */
        .hero-section {
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .trust-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.15);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            color: #10B981;
            margin-bottom: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #FFFFFF 0%, #10B981 40%, #34D399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.1rem;
            color: #94A3B8;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Stats Counter */
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #10B981;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #94A3B8;
        }

        /* Login Trigger Button */
        .login-trigger-btn {
            background: linear-gradient(135deg, #10B981, #059669);
            border: none;
            padding: 14px 40px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.4);
        }

        .login-trigger-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px -5px rgba(16, 185, 129, 0.5);
        }

        /* Features Section */
        .features-section {
            padding: 4rem 2rem;
            background: rgba(0, 0, 0, 0.2);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .section-title p {
            color: #94A3B8;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .feature-card:hover {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 55px;
            height: 55px;
            background: rgba(16, 185, 129, 0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .feature-icon i {
            font-size: 1.6rem;
            color: #10B981;
        }

        .feature-card h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: white;
        }

        .feature-card p {
            font-size: 0.8rem;
            color: #94A3B8;
            line-height: 1.5;
        }

        /* Testimonials Section */
        .testimonials-section {
            padding: 4rem 2rem;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .testimonial-text {
            font-size: 0.9rem;
            color: #CBD5E1;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .author-avatar i {
            color: white;
            font-size: 1rem;
        }

        .author-info h5 {
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .author-info p {
            font-size: 0.7rem;
            color: #94A3B8;
            margin: 0;
        }

        /* Contact Section */
        .contact-section {
            padding: 4rem 2rem;
            background: rgba(0, 0, 0, 0.2);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .contact-info p {
            color: #94A3B8;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            transition: all 0.3s;
        }

        .contact-item:hover {
            background: rgba(16, 185, 129, 0.1);
            transform: translateX(10px);
        }

        .contact-icon {
            width: 45px;
            height: 45px;
            background: rgba(16, 185, 129, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-icon i {
            font-size: 1.2rem;
            color: #10B981;
        }

        .contact-text h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }

        .contact-text p {
            font-size: 0.8rem;
            color: #94A3B8;
            margin: 0;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 2rem;
        }

        .contact-form h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
        }

        .form-group-custom {
            margin-bottom: 1rem;
        }

        .form-group-custom input,
        .form-group-custom textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-group-custom input:focus,
        .form-group-custom textarea:focus {
            outline: none;
            border-color: #10B981;
            background: rgba(255, 255, 255, 0.12);
        }

        .form-group-custom input::placeholder,
        .form-group-custom textarea::placeholder {
            color: #64748B;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #10B981, #059669);
            border: none;
            padding: 12px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        /* Social Links */
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94A3B8;
            transition: all 0.3s;
        }

        .social-link:hover {
            background: #10B981;
            color: white;
            transform: translateY(-3px);
        }

        /* CTA Section */
        .cta-section {
            padding: 4rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(5, 150, 105, 0.03));
        }

        .cta-section h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .cta-section p {
            color: #94A3B8;
            margin-bottom: 1.5rem;
        }

        /* Footer */
        .footer {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer p {
            color: #64748B;
            font-size: 0.8rem;
        }

        .footer a {
            color: #10B981;
            text-decoration: none;
        }

        /* Login Modal */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(12px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-overlay.active {
            display: flex;
            opacity: 1;
        }

        .login-modal {
            background: linear-gradient(135deg, #FFFFFF, #F8FAFC);
            border-radius: 32px;
            width: 90%;
            max-width: 450px;
            transform: scale(0.9) translateY(30px);
            transition: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
            overflow: hidden;
        }

        .login-overlay.active .login-modal {
            transform: scale(1) translateY(0);
        }

        .login-modal-header {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-modal:hover {
            background: rgba(239, 68, 68, 0.8);
            transform: rotate(90deg);
        }

        .modal-icon {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .modal-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .login-modal-header h3 {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .login-modal-header p {
            color: #94A3B8;
            font-size: 0.85rem;
            margin: 0;
        }

        .login-modal-body {
            padding: 2rem;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .input-group-custom input {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 2px solid #E2E8F0;
            border-radius: 14px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .input-group-custom input:focus {
            outline: none;
            border-color: #10B981;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #10B981;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94A3B8;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #10B981, #059669);
            border: none;
            padding: 12px;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
        }

        .security-badge {
            margin-top: 1.5rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #E2E8F0;
            font-size: 0.7rem;
            color: #94A3B8;
        }

        .alert {
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
            border-left: 3px solid #DC2626;
        }

        /* Responsive */
        @media (max-width: 1000px) {
            .feature-grid, .testimonial-grid { grid-template-columns: repeat(2, 1fr); }
            .contact-container { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .hero-section h1 { font-size: 2.2rem; }
            .feature-grid, .testimonial-grid { grid-template-columns: 1fr; }
            .navbar-custom { flex-direction: column; gap: 1rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 1rem; }
            .stats-container { gap: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="grid-pattern"></div>
    <div class="particles" id="particles"></div>

    <div class="landing-container">
        <!-- Navigation Bar -->
        <nav class="navbar-custom d-flex justify-content-between align-items-center flex-wrap">
            <div class="navbar-brand">
                <i class="fas fa-shield-alt"></i> PM System
            </div>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#security">Security</a>
                <a href="#testimonials">Testimonials</a>
                <a href="#contact">Contact</a>
                <button class="login-trigger-btn" id="showLoginBtn" style="padding: 8px 20px; font-size: 0.85rem;">
                    <i class="fas fa-lock"></i> Login
                </button>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="trust-badge" data-aos="fade-down">
                    <i class="fas fa-check-circle"></i>
                    Trusted by 10,000+ Administrators
                </div>
                <h1 data-aos="fade-up">Secure Enterprise<br>Password Management</h1>
                <p class="hero-description" data-aos="fade-up" data-aos-delay="100">
                    Military-grade encryption, automated security audits, and complete control over your organization's credentials.
                </p>
                
                <div class="stats-container">
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="stat-number" id="stat1">0</div>
                        <div class="stat-label">Passwords Protected</div>
                    </div>
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="stat-number" id="stat2">0</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-item" data-aos="fade-up" data-aos-delay="400">
                        <div class="stat-number" id="stat3">0</div>
                        <div class="stat-label">Security Audits</div>
                    </div>
                </div>
                
                <button class="login-trigger-btn" id="showLoginBtn2" data-aos="fade-up" data-aos-delay="500">
                    <i class="fas fa-fingerprint"></i>
                    Secure Access
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <div class="section-title" data-aos="fade-up">
                <h2>Enterprise-Grade Security Features</h2>
                <p>Everything you need to protect your digital assets</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h4>AES-256 Encryption</h4>
                    <p>Military-grade encryption for all stored credentials</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-icon"><i class="fas fa-key"></i></div>
                    <h4>Master Key Protection</h4>
                    <p>Master key required for decryption of vault</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h4>CSRF & XSS Protection</h4>
                    <p>Advanced protection against web vulnerabilities</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="250">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h4>Real-time Analytics</h4>
                    <p>Monitor security status and password strength</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon"><i class="fas fa-history"></i></div>
                    <h4>Activity Logging</h4>
                    <p>Complete audit trail of all actions</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="350">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h4>Responsive Design</h4>
                    <p>Access from any device, anywhere</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-icon"><i class="fas fa-database"></i></div>
                    <h4>Encrypted Backup</h4>
                    <p>Secure export and import functionality</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="450">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h4>Auto Logout</h4>
                    <p>Automatic session timeout for security</p>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials-section" id="testimonials">
            <div class="section-title" data-aos="fade-up">
                <h2>Trusted by Security Professionals</h2>
                <p>What our users say about PM System</p>
            </div>
            <div class="testimonial-grid">
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-text">
                        "The best password manager I've ever used. The encryption is top-notch and the interface is incredibly intuitive."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar"><i class="fas fa-user"></i></div>
                        <div class="author-info">
                            <h5>Michael Rodriguez</h5>
                            <p>IT Security Director</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-text">
                        "Enterprise-grade security with consumer-friendly UX. Our team loves the credential vault and activity logging."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar"><i class="fas fa-user"></i></div>
                        <div class="author-info">
                            <h5>Sarah Chen</h5>
                            <p>CISO, TechCorp</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-text">
                        "The master key system adds an extra layer of security. Highly recommended for any organization."
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar"><i class="fas fa-user"></i></div>
                        <div class="author-info">
                            <h5>David Okonkwo</h5>
                            <p>Systems Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section class="contact-section" id="contact">
            <div class="contact-container">
                <div class="contact-info" data-aos="fade-right">
                    <h3>Get in Touch</h3>
                    <p>Have questions about our security platform? Our team is here to help you secure your organization's credentials.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="contact-text">
                                <h4>Address</h4>
                                <p>123 Security Street, Tech City, TC 12345</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                            <div class="contact-text">
                                <h4>Email Us</h4>
                                <p>support@passwordmanager.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-phone"></i></div>
                            <div class="contact-text">
                                <h4>Call Us</h4>
                                <p>+1 (555) 123-4567</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon"><i class="fas fa-clock"></i></div>
                            <div class="contact-text">
                                <h4>Support Hours</h4>
                                <p>24/7 Enterprise Support</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                    </div>
                </div>
                
                <div class="contact-form" data-aos="fade-left">
                    <h4>Send us a Message</h4>
                    <form id="contactForm">
                        <div class="form-group-custom">
                            <input type="text" placeholder="Your Name" required>
                        </div>
                        <div class="form-group-custom">
                            <input type="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group-custom">
                            <input type="text" placeholder="Subject">
                        </div>
                        <div class="form-group-custom">
                            <textarea rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section" id="security">
            <h3 data-aos="fade-up">Ready to Secure Your Credentials?</h3>
            <p data-aos="fade-up" data-aos-delay="100">Join thousands of organizations using PM System for enterprise password management</p>
            <button class="login-trigger-btn" id="showLoginBtn3" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-shield-alt"></i> Get Started Now
            </button>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2024 Admin Password Manager. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a> | <a href="#">Security</a></p>
        </footer>
    </div>

    <!-- Login Modal Overlay -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-modal">
            <div class="login-modal-header">
                <button class="close-modal" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                </button>
                <div class="modal-icon">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h3>Secure Login</h3>
                <p>Enter your credentials to access the vault</p>
            </div>
            <div class="login-modal-body">
                <div id="alertMessage"></div>
                <form id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <div class="input-group-custom">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" id="username" required placeholder="Username or Email">
                    </div>
                    <div class="input-group-custom">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" required placeholder="Password">
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                    </div>
                    <div class="input-group-custom">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" name="master_key" id="master_key" required placeholder="Master Key">
                        <i class="fas fa-eye toggle-password" data-target="master_key"></i>
                    </div>
                    <button type="submit" class="btn-login">
                        <span>Access Vault</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i> 256-bit AES Encryption | Secure Connection | Session Timeout: 30min
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Animated counter
        function animateCounter(element, start, end, duration) {
            let startTime = null;
            function update(currentTime) {
                if (!startTime) startTime = currentTime;
                const progress = Math.min((currentTime - startTime) / duration, 1);
                $(element).text(Math.floor(progress * (end - start) + start).toLocaleString());
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter('#stat1', 0, 12500, 2000);
                    animateCounter('#stat2', 0, 3840, 2000);
                    animateCounter('#stat3', 0, 892, 2000);
                    observer.disconnect();
                }
            });
        });
        observer.observe(document.querySelector('.stats-container'));

        // Particles
        function createParticles() {
            for (let i = 0; i < 50; i++) {
                const size = Math.random() * 150 + 50;
                const duration = Math.random() * 25 + 15;
                const delay = Math.random() * 10;
                const left = Math.random() * 100;
                $('<div>').addClass('particle').css({
                    width: size + 'px',
                    height: size + 'px',
                    left: left + '%',
                    bottom: '-' + (size / 2) + 'px',
                    animationDuration: duration + 's',
                    animationDelay: delay + 's',
                    opacity: Math.random() * 0.15 + 0.05
                }).appendTo('#particles');
            }
        }
        createParticles();

        // Modal functions
        function showModal() { 
            $('#loginOverlay').addClass('active'); 
            setTimeout(() => $('#username').focus(), 300); 
        }
        
        function closeModal() { 
            $('#loginOverlay').removeClass('active'); 
            $('#alertMessage').html(''); 
            $('#loginForm')[0].reset(); 
        }
        
        $('#showLoginBtn, #showLoginBtn2, #showLoginBtn3').click(showModal);
        $('#closeModalBtn').click(closeModal);
        $('#loginOverlay').click(function(e) { 
            if ($(e.target).is('#loginOverlay')) closeModal(); 
        });
        $(document).keydown(function(e) { 
            if (e.key === 'Escape' && $('#loginOverlay').hasClass('active')) closeModal(); 
        });

        // Toggle password visibility
        $('.toggle-password').click(function() {
            const target = $(this).data('target');
            const input = $('#' + target);
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        // Login form submit
        $('#loginForm').submit(function(e) {
            e.preventDefault();
            const $btn = $('.btn-login');
            const originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Authenticating...').prop('disabled', true);
            
            $.ajax({
                url: 'auth.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $btn.html('<i class="fas fa-check"></i> Redirecting...');
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 500);
                    } else {
                        $('#alertMessage').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + response.message + '</div>');
                        $btn.html(originalHtml).prop('disabled', false);
                    }
                },
                error: function() {
                    $('#alertMessage').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> An error occurred. Please try again.</div>');
                    $btn.html(originalHtml).prop('disabled', false);
                }
            });
        });

        // Contact form submit
        $('#contactForm').submit(function(e) {
            e.preventDefault();
            alert('Thank you for your message! Our team will contact you shortly.');
            this.reset();
        });

        // Smooth scroll for anchor links
        $('.nav-links a[href^="#"]').click(function(e) {
            e.preventDefault();
            const target = $(this.hash);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800);
            }
        });

        // Prevent modal close when clicking inside form
        $('.login-modal').click(function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>