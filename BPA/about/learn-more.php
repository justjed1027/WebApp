<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn More | SkillSwap Platform Overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=League+Spartan:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="learn-more.css">
</head>
<body>
    <header class="Header">
        <a href="../landing/landing.php" class="logo"><span class="highlight">SKILL</span>SWAP</a>
        <nav class="navbar">
            <a href="../login/login.php" class="login">Login</a>
            <a href="../signup/signup.php" class="sign-up">Sign Up</a>
        </nav>
    </header>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to <span class="highlight">SkillSwap</span></h1>
                <p class="hero-subtitle">A Student Talent Exchange Platform Where Knowledge Meets Collaboration</p>
                <p class="hero-description">
                    SkillSwap connects students who want to share their expertise with those eager to learn. 
                    Whether you're mastering calculus, perfecting photography, or exploring coding, 
                    our platform enables peer-to-peer learning through structured sessions, collaborative events, 
                    and an engaged community.
                </p>
            </div>
        </section>

       
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Explore</h4>
                <ul>
                    <li><a href="../courses/courses.php">Courses</a></li>
                    <li><a href="../events/events.php">Events</a></li>
                    <li><a href="../post/post.php">Posts</a></li>
                    <li><a href="../calendar/calendar.php">Calendar</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Connect</h4>
                <ul>
                    <li><a href="../connections/connections.php">Connections</a></li>
                    <li><a href="../dms/dms.php">Direct Messages</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Get Started</h4>
                <ul>
                    <li><a href="../signup/signup.php">Sign Up</a></li>
                    <li><a href="../login/login.php">Login</a></li>
                    <li><a href="learn-more.php">Learn More</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support & Legal</h4>
                <ul>
                    <li><a href="coming-soon.php">Contact Us</a></li>
                    <li><a href="coming-soon.php">Privacy Policy</a></li>
                    <li><a href="coming-soon.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SkillSwap. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let ticking = false;
        let lastScrollTop = 0;
        let navbar = null;
        
        // Initialize navbar reference when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            navbar = document.querySelector('.Header');
        });
        
        function updateScrollEffect() {
            const scrollY = window.scrollY;
            
            // Handle navbar visibility
            if (navbar) {
                const scrollThreshold = 100; // Start hiding after 100px scroll
                
                if (scrollY > scrollThreshold) {
                    if (scrollY > lastScrollTop && scrollY > scrollThreshold) {
                        // Scrolling down - hide navbar
                        navbar.classList.add('hidden');
                        navbar.classList.remove('visible');
                    } else if (scrollY < lastScrollTop) {
                        // Scrolling up - show navbar
                        navbar.classList.remove('hidden');
                        navbar.classList.add('visible');
                    }
                } else {
                    // Always show navbar at top of page
                    navbar.classList.remove('hidden');
                    navbar.classList.add('visible');
                }
                
                lastScrollTop = scrollY;
            }
            
            ticking = false;
        }
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(updateScrollEffect);
            }
        }, { passive: true });
    </script>
</body>
</html>
