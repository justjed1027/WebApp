<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon - SkillSwap</title>
    <link rel="stylesheet" href="coming-soon.css">
</head>
<body>
    <!-- Popup Modal -->
    <div class="modal-overlay" id="constructionModal">
        <div class="modal">
            <div class="modal-header">
                <h2>⚠️ Under Construction</h2>
            </div>
            <div class="modal-body">
                <p>This page is still under construction and the features you see have yet to be complete.</p>
                <p>We're actively working on bringing you the best experience possible!</p>
            </div>
            <div class="modal-footer">
                <button class="modal-button" onclick="closeModal()">Got it!</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <h1>Coming Soon</h1>
            <p class="message">This page is currently under construction.</p>
            <p class="sub-message">We're working hard to bring you this content. Check back soon!</p>
            <a href="javascript:history.back()" class="back-button">Go Back</a>
        </div>
    </div>

    <script>
        // Show modal on page load
        window.addEventListener('load', function() {
            document.getElementById('constructionModal').style.display = 'flex';
        });

        // Close modal function
        function closeModal() {
            document.getElementById('constructionModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('constructionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
