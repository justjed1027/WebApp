<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSwap — Share Knowledge</title>
    <link rel="stylesheet" href="landing.css">
</head>
<body>
    <div class="scroll-container">
      <header class="Header">
          <a href="a" class="logo"><span class="highlight">SKILL</span>SWAP</a>
          <nav class="navbar">
            <a href="..//login/login.php" class="login">Login</a>
            <a href="..//signup/signup.php" class="sign-up">Sign Up</a>
          </nav>
      </header>
      
      <main class="hero-section sticky-section">
          <div class="hero-content">
              <div class="hero-text">
                  <h1>Share Knowledge</h1>
                  <h2>Build Skills</h2>
                  <h3>Connect With Peers</h3>
                  <p class="tagline">BUILT BY STUDENTS FOR STUDENTS</p>
              </div>
              <div class="hero-buttons">
                  <a href="..//signup/signup.php" class="btn btn-primary">Get Started</a>
                  <a href="#features" class="btn btn-secondary">Learn More</a>
              </div>
          </div>
      </main>
        
      <section class="features-section sticky-section" id="features">
        <div class="features-container">
          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-image">
                <!-- Image placeholder -->
              </div>
              <div class="feature-content">
                <h3>Learn</h3>
                <p>Let's fix up your dashboards and get you the data you need to make crucial decisions.</p>
              </div>
            </div>
            
            <div class="feature-card">
              <div class="feature-image">
                <!-- Image placeholder -->
              </div>
              <div class="feature-content">
                <h3>Collaborate</h3>
                <p>Let me point your business in the right direction, using a mix of foresight and insight.</p>
              </div>
            </div>
            
            <div class="feature-card">
              <div class="feature-image">
                <!-- Image placeholder -->
              </div>
              <div class="feature-content">
                <h3>Share</h3>
                <p>Use my years of experience and invite me as a speaker to your next seminar or conference.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <script>
        let ticking = false;
        let currentScale = 0;
        let targetScale = 0;
        let lastScrollTop = 0;
        let navbar = null;
        
        // Initialize navbar reference when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            navbar = document.querySelector('.Header');
        });
        
        function lerp(start, end, factor) {
            return start + (end - start) * factor;
        }
        
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
            
            // Handle text scaling
            if (scrollY > 50) {
                const maxScroll = window.innerHeight;
                targetScale = Math.min((scrollY - 50) / maxScroll, 2);
            } else {
                targetScale = 0;
            }
            
            // Smooth interpolation
            currentScale = lerp(currentScale, targetScale, 0.1);
            document.documentElement.style.setProperty('--scroll-scale', currentScale);
            
            if (Math.abs(currentScale - targetScale) > 0.001) {
                requestAnimationFrame(updateScrollEffect);
            } else {
                ticking = false;
            }
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
<?php 
?>