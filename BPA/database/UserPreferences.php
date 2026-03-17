<?php

class UserPreferences {
    private static $allowedColors = [
        '#ED2100',
        'Silver',
        '#00D97E',
        '#8686AC',
        '#FDBCB4',
        '#BCA90B'
    ];

    public static function getAllowedColors() {
        return self::$allowedColors;
    }

    public static function normalizeTheme($theme) {
        $value = strtolower(trim((string) $theme));
        if ($value !== 'light' && $value !== 'dark' && $value !== 'mixed') {
            return 'mixed';
        }
        return $value;
    }

    public static function normalizeNavigationMode($mode) {
        if (is_bool($mode)) {
            return $mode ? 'top' : 'sidebar';
        }

        if (is_int($mode) || is_float($mode)) {
            return ((int) $mode) === 1 ? 'top' : 'sidebar';
        }

        $value = strtolower(trim((string) $mode));
        if ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'top') {
            return 'top';
        }
        if ($value === '0' || $value === 'false' || $value === 'no' || $value === 'sidebar' || $value === '') {
            return 'sidebar';
        }

        if ($value !== 'top' && $value !== 'sidebar') {
            return 'sidebar';
        }
        return $value;
    }

    public static function normalizeColor($color) {
        $value = trim((string) $color);
        if ($value === '') {
            return '#00D97E';
        }

        if (strcasecmp($value, 'silver') === 0) {
            return 'Silver';
        }

        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $value)) {
            if ($value[0] !== '#') {
                $value = '#' . $value;
            }
            $value = strtoupper($value);
        }

        foreach (self::$allowedColors as $allowed) {
            if (strcasecmp($allowed, $value) === 0) {
                return $allowed;
            }
        }

        return '#00D97E';
    }

    public static function toHexColor($color) {
        $value = self::normalizeColor($color);
        if (strcasecmp($value, 'Silver') === 0) {
            return '#C0C0C0';
        }
        return strtoupper($value);
    }

    public static function getForUser($conn, $userId) {
        $prefs = [
            'theme' => 'mixed',
            'primary_color' => '#00D97E',
            'primary_color_hex' => '#00D97E',
            'navigation_mode' => 'sidebar'
        ];

        $mapping = self::resolveMapping($conn);
        if (!$mapping) {
            return $prefs;
        }

        $table = $mapping['table'];
        $userCol = $mapping['user'];
        $themeCol = $mapping['theme'];
        $colorCol = $mapping['color'];
        $navigationCol = $mapping['navigation'];
        $navigationType = $mapping['navigationType'];

        if (!$themeCol && !$colorCol && !$navigationCol) {
            return $prefs;
        }

        $selectCols = [];
        if ($themeCol) {
            $selectCols[] = "`$themeCol`";
        }
        if ($colorCol) {
            $selectCols[] = "`$colorCol`";
        }
        if ($navigationCol) {
            $selectCols[] = "`$navigationCol`";
        }

        $sql = "SELECT " . implode(', ', $selectCols) . " FROM `$table` WHERE `$userCol` = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $prefs;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return $prefs;
        }

        if ($themeCol && isset($row[$themeCol])) {
            $prefs['theme'] = self::normalizeTheme($row[$themeCol]);
        }

        if ($colorCol && isset($row[$colorCol])) {
            $prefs['primary_color'] = self::normalizeColor($row[$colorCol]);
            $prefs['primary_color_hex'] = self::toHexColor($prefs['primary_color']);
        }

        if ($navigationCol && isset($row[$navigationCol])) {
            $prefs['navigation_mode'] = self::normalizeNavigationMode($row[$navigationCol]);
        }

        return $prefs;
    }

    public static function saveForUser($conn, $userId, $theme, $primaryColor, $navigationMode = 'sidebar') {
        $mapping = self::resolveMapping($conn);
        if (!$mapping) {
            return [
                'success' => false,
                'message' => 'Preferences table not found.'
            ];
        }

        $table = $mapping['table'];
        $userCol = $mapping['user'];
        $themeCol = $mapping['theme'];
        $colorCol = $mapping['color'];
        $navigationCol = $mapping['navigation'];
        $navigationType = $mapping['navigationType'];

        if (!$themeCol && !$colorCol && !$navigationCol) {
            return [
                'success' => false,
                'message' => 'Preferences columns not found.'
            ];
        }

        $theme = self::normalizeTheme($theme);
        $primaryColor = self::normalizeColor($primaryColor);
        $navigationMode = self::normalizeNavigationMode($navigationMode);
        $navigationValue = self::isNumericColumnType($navigationType)
            ? ($navigationMode === 'top' ? 1 : 0)
            : $navigationMode;

        $existsSql = "SELECT 1 FROM `$table` WHERE `$userCol` = ? LIMIT 1";
        $existsStmt = $conn->prepare($existsSql);
        if (!$existsStmt) {
            return [
                'success' => false,
                'message' => 'Failed to prepare preferences check query.'
            ];
        }

        $existsStmt->bind_param('i', $userId);
        $existsStmt->execute();
        $existsResult = $existsStmt->get_result();
        $exists = $existsResult && $existsResult->num_rows > 0;
        $existsStmt->close();

        if ($exists) {
            $sets = [];
            $types = '';
            $params = [];

            if ($themeCol) {
                $sets[] = "`$themeCol` = ?";
                $types .= 's';
                $params[] = $theme;
            }

            if ($colorCol) {
                $sets[] = "`$colorCol` = ?";
                $types .= 's';
                $params[] = $primaryColor;
            }

            if ($navigationCol) {
                $sets[] = "`$navigationCol` = ?";
                if (self::isNumericColumnType($navigationType)) {
                    $types .= 'i';
                    $params[] = (int) $navigationValue;
                } else {
                    $types .= 's';
                    $params[] = (string) $navigationValue;
                }
            }

            $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$userCol` = ?";
            $types .= 'i';
            $params[] = $userId;

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Failed to prepare preferences update query.'
                ];
            }

            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                return [
                    'success' => false,
                    'message' => 'Failed to update preferences.'
                ];
            }
        } else {
            $cols = ["`$userCol`"];
            $holders = ['?'];
            $types = 'i';
            $params = [$userId];

            if ($themeCol) {
                $cols[] = "`$themeCol`";
                $holders[] = '?';
                $types .= 's';
                $params[] = $theme;
            }

            if ($colorCol) {
                $cols[] = "`$colorCol`";
                $holders[] = '?';
                $types .= 's';
                $params[] = $primaryColor;
            }

            if ($navigationCol) {
                $cols[] = "`$navigationCol`";
                $holders[] = '?';
                if (self::isNumericColumnType($navigationType)) {
                    $types .= 'i';
                    $params[] = (int) $navigationValue;
                } else {
                    $types .= 's';
                    $params[] = (string) $navigationValue;
                }
            }

            $sql = "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $holders) . ")";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [
                    'success' => false,
                    'message' => 'Failed to prepare preferences insert query.'
                ];
            }

            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                return [
                    'success' => false,
                    'message' => 'Failed to insert preferences.'
                ];
            }
        }

        return [
            'success' => true,
            'theme' => $theme,
            'primary_color' => $primaryColor,
            'primary_color_hex' => self::toHexColor($primaryColor),
            'navigation_mode' => $navigationMode
        ];
    }

    private static function resolveMapping($conn) {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $tableCandidates = [
            'preferences',
            'user_preferences',
            'userpreference',
            'user_preferences_settings'
        ];

        $table = null;
        foreach ($tableCandidates as $candidate) {
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $candidate);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $table = $candidate;
                $stmt->close();
                break;
            }
            $stmt->close();
        }

        if (!$table) {
            $cache = null;
            return null;
        }

        $columns = [];
        $columnTypes = [];
        $result = $conn->query("SHOW COLUMNS FROM `$table`");
        if (!$result) {
            $cache = null;
            return null;
        }

        while ($row = $result->fetch_assoc()) {
            if (!empty($row['Field'])) {
                $columns[] = $row['Field'];
                $columnTypes[$row['Field']] = $row['Type'] ?? '';
            }
        }

        $userCol = self::pickColumn($columns, ['user_id', 'pref_user_id', 'up_user_id', 'preferences_user_id']);
        if (!$userCol) {
            $cache = null;
            return null;
        }

        $themeCol = self::pickColumn($columns, ['theme_preference', 'theme', 'theme_mode', 'preferred_theme']);
        $colorCol = self::pickColumn($columns, ['primary_color', 'color_scheme', 'accent_color', 'main_color']);
        $navigationCol = self::pickColumn($columns, ['navigation', 'navigation_mode', 'navigation_preference', 'nav_mode', 'nav_preference', 'layout_preference']);
        $navigationType = $navigationCol ? ($columnTypes[$navigationCol] ?? '') : '';

        $cache = [
            'table' => $table,
            'user' => $userCol,
            'theme' => $themeCol,
            'color' => $colorCol,
            'navigation' => $navigationCol,
            'navigationType' => $navigationType
        ];

        return $cache;
    }

    private static function pickColumn($columns, $candidates) {
        foreach ($candidates as $name) {
            if (in_array($name, $columns, true)) {
                return $name;
            }
        }
        return null;
    }

    private static function isNumericColumnType($type) {
        $normalized = strtolower((string) $type);
        return strpos($normalized, 'int') !== false || strpos($normalized, 'decimal') !== false || strpos($normalized, 'float') !== false || strpos($normalized, 'double') !== false || strpos($normalized, 'bit') !== false;
    }
}
