<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Read optional type parameter
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Post</title>
</head>
<body>
  <h2>Write a New Post</h2>
  <form action="post_submit.php" method="POST" enctype="multipart/form-data">
    <?php if ($type): ?>
      <input type="hidden" name="type" value="<?= $type ?>">
      <p>Creating a <strong><?= $type ?></strong> post</p>
    <?php endif; ?>
    <textarea name="content" rows="5" cols="50" placeholder="Write your post here..." required></textarea>
    <br>
    <?php if (in_array($type, ['photo','video','document'])): ?>
      <label for="file">Attach <?= htmlspecialchars($type) ?>:</label>
      <input type="file" id="file" name="attachment" accept="<?php
        if ($type === 'photo') echo 'image/*';
        elseif ($type === 'video') echo 'video/*';
        else echo '.pdf,.doc,.docx,.txt';
      ?>">
      <div id="preview" style="margin-top:12px;"></div>
    <?php endif; ?>
    <br>
    <button type="submit">Post</button>
  </form>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const fileInput = document.getElementById('file');
      const preview = document.getElementById('preview');
      if (!fileInput || !preview) return;
      fileInput.addEventListener('change', function () {
        preview.innerHTML = '';
        const f = this.files[0];
        if (!f) return;
        const type = f.type;
        if (type.startsWith('image/')) {
          const img = document.createElement('img');
          img.style.maxWidth = '320px';
          img.style.maxHeight = '240px';
          img.src = URL.createObjectURL(f);
          preview.appendChild(img);
        } else if (type.startsWith('video/')) {
          const vid = document.createElement('video');
          vid.controls = true;
          vid.style.maxWidth = '480px';
          vid.src = URL.createObjectURL(f);
          preview.appendChild(vid);
        } else {
          const p = document.createElement('p');
          p.textContent = 'File: ' + f.name;
          preview.appendChild(p);
        }
      });
    });
  </script>
</body>
</html>
