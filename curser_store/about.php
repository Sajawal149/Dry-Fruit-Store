<?php
require_once 'config/database.php';

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Northern Dry Fruits Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Light Theme Colors */
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --background-color: #f5f3f0;
            --surface-color: rgba(255, 255, 255, 0.8);
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --border-color: rgba(255, 255, 255, 0.2);
            --shadow-color: rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        [data-theme="dark"] {
            /* Dark Theme Colors */
            --primary-color: #8b5cf6;
            --secondary-color: #a855f7;
            --background-color: #0f172a;
            --surface-color: rgba(30, 41, 59, 0.8);
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --glass-bg: rgba(30, 41, 59, 0.25);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background: var(--background-color);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Liquid Glass Effects */
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .glass-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px 0 var(--shadow-color);
        }

        /* Navbar Styling */
        .navbar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5em;
            color: var(--text-primary) !important;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        /* Theme Toggle Button */
        .theme-toggle {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            padding: 8px 16px;
            color: var(--text-primary);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            margin-top: 10px;
            min-width: 200px;
            z-index: 1040 !important;
        }

        .dropdown-item {
            color: var(--text-primary);
            padding: 10px 20px;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 8px;
        }

        .dropdown-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .dropdown-toggle::after {
            margin-left: 8px;
            color: var(--text-secondary);
        }

        /* Ensure dropdown is visible */
        .dropdown-menu.show {
            display: block !important;
            opacity: 1;
            transform: translateY(0);
        }

        /* Fix z-index for navbar and dropdown */
        .navbar {
            z-index: 1030 !important;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 120px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        /* About Content */
        .about-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            margin: 20px 0;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .about-image {
            border-radius: 20px;
            box-shadow: 0 15px 35px 0 var(--shadow-color);
            transition: all 0.3s ease;
        }

        .about-image:hover {
            transform: scale(1.02);
            box-shadow: 0 25px 50px 0 var(--shadow-color);
        }

        /* Feature Cards */
        .feature-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px 0 var(--shadow-color);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Product Cards */
        .product-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px 0 var(--shadow-color);
        }

        /* Cart Badge */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3);
        }

        .nav-item {
            position: relative;
        }

        /* Contact Section */
        .contact-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            margin: 20px 0;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .contact-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer */
        footer {
            background: var(--surface-color);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .glass-card {
                margin-bottom: 1rem;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light glass-effect">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain"></i> Northern Dry Fruits
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products_new.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <button class="btn theme-toggle" onclick="toggleTheme()">
                            <i class="fas fa-moon" id="theme-icon"></i>
                            <span id="theme-text">Dark</span>
                        </button>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu glass-effect">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center position-relative">
            <h1 class="display-3 fw-bold mb-4">About Northern Dry Fruits</h1>
            <p class="lead mb-4">Discover the story behind our premium dry fruits from the pristine mountains of Northern Pakistan</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <div class="about-content">
                        <h2 class="mb-4 fw-bold">Our Story</h2>
                        <p class="lead">Northern Dry Fruits Store was founded with a simple mission: to bring the finest quality dry fruits from the majestic mountains of Northern Pakistan to your table.</p>
                        <p>Nestled in the heart of the Karakoram and Himalayan ranges, our dry fruits are sourced directly from the fertile valleys of Gilgit, Hunza, Skardu, and surrounding regions. These areas are renowned for their pristine environment, pure mountain water, and optimal growing conditions that produce some of the world's finest dry fruits.</p>
                        <p>We work directly with local farmers and producers who have been cultivating these traditional crops for generations, ensuring that every product maintains its authentic taste and nutritional value.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         alt="Northern Pakistan Mountains" class="img-fluid about-image">
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-lg-6">
                    <img src="images/products/misdry.jpg" 
                         alt="Dry Fruits Collection" class="img-fluid about-image">
                </div>
                <div class="col-lg-6">
                    <div class="about-content">
                        <h2 class="mb-4 fw-bold">Why Choose Our Dry Fruits?</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="feature-card">
                                    <i class="fas fa-leaf feature-icon"></i>
                                    <h5 class="fw-bold">100% Natural</h5>
                                    <p class="text-muted">No artificial preservatives or additives</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-card">
                                    <i class="fas fa-mountain feature-icon"></i>
                                    <h5 class="fw-bold">Mountain Fresh</h5>
                                    <p class="text-muted">Sourced from high-altitude regions</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-card">
                                    <i class="fas fa-heart feature-icon"></i>
                                    <h5 class="fw-bold">Rich in Nutrients</h5>
                                    <p class="text-muted">Packed with vitamins and minerals</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-card">
                                    <i class="fas fa-award feature-icon"></i>
                                    <h5 class="fw-bold">Premium Quality</h5>
                                    <p class="text-muted">Handpicked and carefully selected</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Products Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Our Premium Products</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100 border-0">
                        <div class="card-body text-center">
                            <i class="fas fa-seedling fa-3x feature-icon mb-3"></i>
                            <h5 class="fw-bold">Almonds</h5>
                            <p class="text-muted">Premium quality almonds from the mountains of Northern Pakistan, rich in protein and healthy fats.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100 border-0">
                        <div class="card-body text-center">
                            <i class="fas fa-tree fa-3x feature-icon mb-3"></i>
                            <h5 class="fw-bold">Walnuts</h5>
                            <p class="text-muted">Fresh walnuts from Gilgit region, known for their rich flavor and nutritional benefits.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100 border-0">
                        <div class="card-body text-center">
                            <i class="fas fa-sun fa-3x feature-icon mb-3"></i>
                            <h5 class="fw-bold">Apricots</h5>
                            <p class="text-muted">Dried apricots from Hunza Valley, naturally sweet and rich in vitamins.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="contact-section">
                        <h2 class="mb-4 fw-bold">Get in Touch</h2>
                        <p class="lead mb-4">Have questions about our products or want to place a bulk order? We'd love to hear from you!</p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-envelope contact-icon"></i>
                                <h5 class="fw-bold">Email</h5>
                                <p class="text-muted">info@northerndryfruits.com</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-phone contact-icon"></i>
                                <h5 class="fw-bold">Phone</h5>
                                <p class="text-muted">+92-300-1234567</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <i class="fas fa-map-marker-alt contact-icon"></i>
                                <h5 class="fw-bold">Address</h5>
                                <p class="text-muted">Gilgit, Northern Pakistan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">Northern Dry Fruits Store</h5>
                    <p class="text-muted">Bringing you the finest quality dry fruits from the mountains of Northern Pakistan.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="fw-bold">Contact Us</h5>
                    <p class="text-muted">Email: info@northerndryfruits.com<br>
                    Phone: +92-300-1234567</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle Functionality
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (html.getAttribute('data-theme') === 'dark') {
                html.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = 'Dark';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light';
            }

            // Initialize dropdown functionality
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownToggle && dropdownMenu) {
                // Add click event for dropdown
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });

                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        dropdownMenu.classList.remove('show');
                    }
                });

                // Prevent dropdown from closing when clicking inside it
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>
</body>
</html> 