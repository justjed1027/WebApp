<?php
// Page 2 — Choose What You Already Know
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (2/4)</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body data-step="2">
  <div class="setup-shell">
    <div class="header"><div class="logo">SkillSwap</div></div>
    <h1 class="step-title">Creating your Account</h1>
    <p class="subtitle">What do you already know?</p>

    <div class="form-stack">
      <div class="input-card">
        <div class="panel-wrap">
          <div class="courses-panel" id="knownCoursesPanel" tabindex="0">
            <div class="search-row">
          <input id="knownCourseSearch" type="text" placeholder="Search skills by name..." aria-label="Search skills" />
          <div class="filters">
            <select id="knownTopicFilter" aria-label="Filter by topic">
              <option value="all">All Topics</option>
              <option value="STEM">STEM</option>
              <option value="Arts">Arts</option>
              <option value="Languages">Languages</option>
              <option value="Business">Business</option>
            </select>
            <select id="knownSortBy" aria-label="Sort skills">
              <option value="popular">Most Popular</option>
              <option value="alpha">Alphabetical</option>
              <option value="new">Newest</option>
            </select>
          </div>
            </div>

            <div id="knownCategories" class="grid">
          <div class="category" data-topic="STEM">
            <h3>Mathematics</h3>
            <label class="course" data-name="Algebra"><input type="checkbox" /> Algebra <span class="desc">— Variables, equations, functions</span></label>
            <label class="course" data-name="Geometry"><input type="checkbox" /> Geometry <span class="desc">— Shapes, proofs, spatial reasoning</span></label>
            <label class="course" data-name="Calculus"><input type="checkbox" /> Calculus <span class="desc">— Limits, derivatives, integrals</span></label>
          </div>

          <div class="category" data-topic="STEM">
            <h3>Science</h3>
            <label class="course" data-name="Biology"><input type="checkbox" /> Biology <span class="desc">— Cells, genetics, ecosystems</span></label>
            <label class="course" data-name="Chemistry"><input type="checkbox" /> Chemistry <span class="desc">— Reactions, bonds, periodic trends</span></label>
            <label class="course" data-name="Physics"><input type="checkbox" /> Physics <span class="desc">— Motion, energy, forces</span></label>
          </div>

          <div class="category" data-topic="STEM">
            <h3>Technology</h3>
            <label class="course" data-name="Programming"><input type="checkbox" /> Programming <span class="desc">— Data structures, algorithms</span></label>
            <label class="course" data-name="Web Development"><input type="checkbox" /> Web Development <span class="desc">— HTML, CSS, JS, frameworks</span></label>
            <label class="course" data-name="AI"><input type="checkbox" /> AI <span class="desc">— ML basics, neural networks</span></label>
          </div>

          <div class="category" data-topic="Languages">
            <h3>Language</h3>
            <label class="course" data-name="English"><input type="checkbox" /> English <span class="desc">— Grammar, writing, literature</span></label>
            <label class="course" data-name="Spanish"><input type="checkbox" /> Spanish <span class="desc">— Conversation, grammar, culture</span></label>
            <label class="course" data-name="French"><input type="checkbox" /> French <span class="desc">— Pronunciation, verbs, phrases</span></label>
          </div>

          <div class="category" data-topic="Business">
            <h3>Business</h3>
            <label class="course" data-name="Marketing"><input type="checkbox" /> Marketing <span class="desc">— Branding, growth, analytics</span></label>
            <label class="course" data-name="Finance"><input type="checkbox" /> Finance <span class="desc">— Budgeting, investing basics</span></label>
            <label class="course" data-name="Entrepreneurship"><input type="checkbox" /> Entrepreneurship <span class="desc">— Startups, product, PMF</span></label>
          </div>

          <div class="category" data-topic="Arts">
            <h3>Design</h3>
            <label class="course" data-name="UX Design"><input type="checkbox" /> UX Design <span class="desc">— Research, prototyping, usability</span></label>
            <label class="course" data-name="Graphic Design"><input type="checkbox" /> Graphic Design <span class="desc">— Layout, typography, color</span></label>
            <label class="course" data-name="Motion Design"><input type="checkbox" /> Motion Design <span class="desc">— Animation, transitions</span></label>
          </div>

          <div class="category" data-topic="Business">
            <h3>Law</h3>
            <label class="course" data-name="Civics"><input type="checkbox" /> Civics <span class="desc">— Government, rights, policy</span></label>
            <label class="course" data-name="Business Law"><input type="checkbox" /> Business Law <span class="desc">— Contracts, IP, compliance</span></label>
            <label class="course" data-name="Legal Writing"><input type="checkbox" /> Legal Writing <span class="desc">— Memos, briefs</span></label>
          </div>

          <div class="category" data-topic="Arts">
            <h3>Art</h3>
            <label class="course" data-name="Painting"><input type="checkbox" /> Painting <span class="desc">— Acrylic, oil, watercolor</span></label>
            <label class="course" data-name="Sculpture"><input type="checkbox" /> Sculpture <span class="desc">— Clay, stone, modern</span></label>
            <label class="course" data-name="Photography"><input type="checkbox" /> Photography <span class="desc">— Composition, lighting</span></label>
          </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="nav-bar">
      <a class="btn btn-primary" href="page1.php">Back</a>
      <a class="btn btn-ghost" href="page3.php">Skip for Now</a>
      <div class="spacer"></div>
      <a class="btn btn-primary" href="page3.php">Next</a>
    </div>

    <div class="progress" aria-label="Progress">
      <span class="dot"></span><span class="dot active"></span><span class="dot"></span><span class="dot"></span>
    </div>
  </div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
