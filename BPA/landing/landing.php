<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSwap — Share Knowledge</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700;800;900&display=swap" rel="stylesheet">
  <!-- Stylesheet (cache-busted) -->
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
                  <a href="../about/learn-more.php" class="btn btn-secondary">Learn More</a>
              </div>
            </div>
        </main>
    </div>
      <section class="features-section sticky-section" id="features">
        <div class="features-container">
          <div class="capabilities">
            <div class="caps-left">
              <h2 class="caps-kicker">Our</h2>
              <h2 class="features-title">Student Learning Toolkit</h2>
            </div>
            <div class="caps-right">
              <div class="accordion" role="region" aria-label="Site Features">
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>Dashboard</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Discover classes and connect with classmates. Explore rosters, instructors, and everything tied to each course.</p>
                    <ul class="acc-list">
                      <li>Join course spaces with discussions and shared materials</li>
                      <li>See classmates and organize study groups</li>
                      <li>Browse modules, sessions, and key topics</li>
                      <li>Jump into related forums and notes from the same page</li>
                    </ul>
                  </div></div>
                </details>
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>Posts</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Share ideas, questions, and resources with the entire community in a social feed.</p>
                    <ul class="acc-list">
                      <li>Create posts with text, images, and links</li>
                      <li>Like, comment, and engage with classmates</li>
                      <li>Filter by subject or follow trending topics</li>
                      <li>Build your reputation and discover new perspectives</li>
                    </ul>
                  </div></div>
                </details>
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>DMs</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Keep conversations moving with classmates and groups—right where you already study.</p>
                    <ul class="acc-list">
                      <li>1:1 and group chats with quick reactions</li>
                      <li>Share files, images, and links inline</li>
                      <li>Message requests and mute controls</li>
                      <li>Fast and reliable across devices</li>
                    </ul>
                  </div></div>
                </details>
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>Connections</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Grow a network of peers who learn the way you do—find classmates by course and interest.</p>
                    <ul class="acc-list">
                      <li>Follow students and form private or public groups</li>
                      <li>Profiles highlight skills, interests, and badges</li>
                      <li>Smart suggestions to meet your next study partner</li>
                      <li>Simple privacy controls for a safe experience</li>
                    </ul>
                  </div></div>
                </details>
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>Calendar</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Keep everything on schedule—from study sessions to assignment deadlines—all in one view.</p>
                    <ul class="acc-list">
                      <li>Personal and group events with reminders</li>
                      <li>Color-coded subjects and quick add</li>
                      <li>RSVP to study sessions and track attendance</li>
                      <li>Optional export to your device calendar</li>
                    </ul>
                  </div></div>
                </details>
                <details class="acc-item">
                  <summary><span class="summary-dot" aria-hidden="true"></span>Events</summary>
                  <div class="acc-content"><div class="acc-inner">
                    <p>Discover and join study sessions, workshops, and campus gatherings organized by students.</p>
                    <ul class="acc-list">
                      <li>Browse upcoming events by subject or interest</li>
                      <li>Register for events and track attendance</li>
                      <li>Create your own events and invite classmates</li>
                      <li>Get reminders and updates before events start</li>
                    </ul>
                  </div></div>
                </details>
              </div>
            </div>
          </div>
        </div>
      </section>
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

          // Intersection Observer for review boxes - initialize after DOM loads
          document.addEventListener('DOMContentLoaded', function() {
              const reviewObserver = new IntersectionObserver((entries) => {
                  entries.forEach(entry => {
                      if (entry.isIntersecting) {
                          console.log('Review element became visible:', entry.target);
                          entry.target.classList.add('visible');
                          // Optional: Unobserve after animation
                          // reviewObserver.unobserve(entry.target);
                      }
                  });
              }, {
                  threshold: 0.2 // Trigger when 20% of the element is visible
              });

              // Observe all review boxes
              const reviews = document.querySelectorAll('.review');
              console.log('Found reviews:', reviews.length);
              reviews.forEach(review => {
                  reviewObserver.observe(review);
              });
          });
    </script>
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
                    <li><a href="../about/learn-more.php">Learn More</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support & Legal</h4>
                <ul>
                    <li><a href="../about/coming-soon.php">Contact Us</a></li>
                    <li><a href="../about/coming-soon.php">Privacy Policy</a></li>
                    <li><a href="../about/coming-soon.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SkillSwap. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>