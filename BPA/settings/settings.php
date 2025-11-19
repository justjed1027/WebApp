<?php
// /c:/xampp/htdocs/WebApp/BPA/settings/settings.php

// Simple settings storage (JSON file next to this script)
$settingsFile = __DIR__ . '/settings.json';

$defaults = [
    'site_title' => 'My App',
    'items_per_page' => 10,
    'default_view' => 'calendar',
    'timezone' => date_default_timezone_get(),
    'date_format' => 'F j, Y',
    'time_format' => '12',
    'first_day_of_week' => '0', // 0 = Sunday
    'workday_start' => '09:00',
    'workday_end' => '17:00',
    'enable_email_notifications' => false,
    'enable_reminders' => true,
    'default_event_duration' => 60,
    'theme' => 'system',
];

if (file_exists($settingsFile)) {
    $raw = file_get_contents($settingsFile);
    $stored = json_decode($raw, true);
    if (is_array($stored)) {
        $settings = array_merge($defaults, $stored);
    } else {
        $settings = $defaults;
    }
} else {
    $settings = $defaults;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $post = [];
    $post['items_per_page'] = max(1, (int)($_POST['items_per_page'] ?? $defaults['items_per_page']));
    $post['default_view'] = in_array($_POST['default_view'] ?? '', ['list','calendar','board']) ? $_POST['default_view'] : $defaults['default_view'];
    $post['timezone'] = in_array($_POST['timezone'] ?? '', timezone_identifiers_list()) ? $_POST['timezone'] : $defaults['timezone'];
    $post['time_format'] = in_array($_POST['time_format'] ?? '', ['12','24']) ? $_POST['time_format'] : $defaults['time_format'];
    $post['workday_start'] = preg_match('/^\d{2}:\d{2}$/', $_POST['workday_start'] ?? '') ? $_POST['workday_start'] : $defaults['workday_start'];
    $post['workday_end'] = preg_match('/^\d{2}:\d{2}$/', $_POST['workday_end'] ?? '') ? $_POST['workday_end'] : $defaults['workday_end'];
    $post['enable_email_notifications'] = isset($_POST['enable_email_notifications']) && $_POST['enable_email_notifications'] === '1';
    $post['enable_reminders'] = isset($_POST['enable_reminders']) && $_POST['enable_reminders'] === '1';
    $post['theme'] = in_array($_POST['theme'] ?? '', ['light','dark','system']) ? $_POST['theme'] : $defaults['theme'];

    // persist
    if (file_put_contents($settingsFile, json_encode($post, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
        $message = 'Settings saved.';
        $settings = array_merge($settings, $post);
    } else {
        $message = 'Failed to save settings.';
    }
}

// helper for escaping
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$timezones = timezone_identifiers_list();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="settings.css">
</head>
<body>
    <div class="wrap">
        <div>
            <header class="card" style="margin-bottom:16px">
                <h1>Settings</h1>
                <div class="muted">Configure site, calendar and notification preferences</div>
            </header>

            <?php if ($message): ?>
                <div class="notice" style="display:flex;align-items:center;justify-content:space-between">
                    <span><?= h($message) ?></span>
                    <a href="../post/post.php" class="btn btn-primary" style="text-decoration:none;font-size:13px;padding:8px 12px">← Back to Home</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="card">
                <div class="form-grid">
                    <div class="field">
                        <label for="items_per_page">Items per page</label>
                        <input id="items_per_page" name="items_per_page" type="number" min="1" value="<?= h($settings['items_per_page']) ?>">
                    </div>

                    <div class="field">
                        <label for="default_view">Default view</label>
                        <select id="default_view" name="default_view">
                            <option value="calendar" <?= $settings['default_view']==='calendar' ? 'selected' : '' ?>>Calendar</option>
                            <option value="list" <?= $settings['default_view']==='list' ? 'selected' : '' ?>>List</option>
                            <option value="board" <?= $settings['default_view']==='board' ? 'selected' : '' ?>>Board</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone" title="Timezone">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?= h($tz) ?>" <?= $tz === $settings['timezone'] ? 'selected' : '' ?>><?= h($tz) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small">Server timezone affects date/time displays.</div>
                    </div>

                    <div class="field">
                        <label for="time_format">Time format</label>
                        <select id="time_format" name="time_format">
                            <option value="12" <?= $settings['time_format'] === '12' ? 'selected' : '' ?>>12-hour (AM/PM)</option>
                            <option value="24" <?= $settings['time_format'] === '24' ? 'selected' : '' ?>>24-hour</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Notifications</label>
                        <div style="display:flex;flex-direction:column;gap:6px">
                            <label class="toggle"><input type="checkbox" name="enable_email_notifications" value="1" <?= $settings['enable_email_notifications'] ? 'checked' : '' ?>> Enable email notifications</label>
                            <label class="toggle"><input type="checkbox" name="enable_reminders" value="1" <?= $settings['enable_reminders'] ? 'checked' : '' ?>> Enable reminders</label>
                        </div>
                        <div class="small">Reminders are in-app; enable email to send messages to users.</div>
                    </div>

                    <div class="field full">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="system" <?= $settings['theme']==='system' ? 'selected' : '' ?>>System</option>
                            <option value="light" <?= $settings['theme']==='light' ? 'selected' : '' ?>>Light</option>
                            <option value="dark" <?= $settings['theme']==='dark' ? 'selected' : '' ?>>Dark</option>
                        </select>
                    </div>
                </div>

                <div class="controls">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="#" onclick="location.reload();return false;" class="btn btn-ghost">Discard</a>
                    <div style="margin-left:auto" class="small muted">Last saved: <?= date('Y-m-d H:i') ?></div>
                </div>
            </form>
        </div>

        <aside class="card preview">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <strong>Preview</strong>
                    <div class="small muted">How settings affect UI</div>
                </div>
                <div class="small muted">Theme: <span id="preview-theme"><?= h($settings['theme']) ?></span></div>
            </div>

            <div style="margin-top:12px">
                <div class="event-preview">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:700">Weekly Team Meeting</div>
                            <div class="small muted">Organizer • My App</div>
                        </div>
                        <div class="small muted">
                            <?php
                                $t = new DateTimeImmutable('now', new DateTimeZone($settings['timezone']));
                                $end = $t->add(new DateInterval('PT60M')); // Default 60 minutes
                                if ($settings['time_format'] === '12') {
                                    echo $t->format('g:i A') . ' - ' . $end->format('g:i A');
                                } else {
                                    echo $t->format('H:i') . ' - ' . $end->format('H:i');
                                }
                            ?>
                        </div>
                    </div>
                    <div class="small muted" style="margin-top:8px">
                        Timezone: <?= h($settings['timezone']) ?>
                    </div>
                </div>

                <div style="margin-top:14px" class="small muted">
                    Items per page: <?= h($settings['items_per_page']) ?> • Default view: <?= h(ucfirst($settings['default_view'])) ?>
                </div>
            </div>
        </aside>
    </div>

    <script>
        // small script to reflect theme selection in preview area
        (function(){
            const themeSelect = document.getElementById('theme');
            const previewTheme = document.getElementById('preview-theme');
            themeSelect.addEventListener('change', function(){
                previewTheme.textContent = this.value;
            });
        })();
    </script>
</body>
</html>w'])) ?>) ?>div>div>div>ion>ion>bel>bel>?>">?>">/option>ion>ion> ?></option>/option>ion>ion>ion>?>">div>tle>