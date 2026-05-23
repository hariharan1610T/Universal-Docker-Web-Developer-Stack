<?php
// ============================================================
//  DATABASE CONFIGURATION — pulled from docker-compose env
// ============================================================
$config = [
    'mysql' => [
        'host'     => getenv('MYSQL_HOST')     ?: 'mysql',
        'port'     => getenv('MYSQL_PORT')     ?: '3306',
        'database' => getenv('MYSQL_DATABASE') ?: 'mydb',
        'user'     => getenv('MYSQL_USER')     ?: 'root',
        'password' => getenv('MYSQL_PASSWORD') ?: 'root',
    ],
    'postgres' => [
        'host'     => getenv('PG_HOST')     ?: 'postgres',
        'port'     => getenv('PG_PORT')     ?: '5432',
        'database' => getenv('PG_DATABASE') ?: 'mydb',
        'user'     => getenv('PG_USER')     ?: 'root',
        'password' => getenv('PG_PASSWORD') ?: 'root',
    ],
    'mongodb' => [
        'host'     => getenv('MONGO_HOST')     ?: 'mongodb',
        'port'     => getenv('MONGO_PORT')     ?: '27017',
        'database' => getenv('MONGO_DATABASE') ?: 'mydb',
        'user'     => getenv('MONGO_USER')     ?: 'root',
        'password' => getenv('MONGO_PASSWORD') ?: 'root',
    ],
];

// ============================================================
//  CONNECTION TESTS
// ============================================================

// MySQL
$mysqlStatus = false;
$mysqlError  = '';
try {
    $m = @new mysqli(
        $config['mysql']['host'],
        $config['mysql']['user'],
        $config['mysql']['password'],
        $config['mysql']['database'],
        (int)$config['mysql']['port']
    );
    if ($m->connect_error) {
        $mysqlError = $m->connect_error;
    } else {
        $mysqlStatus = true;
        $m->close();
    }
} catch (Exception $e) {
    $mysqlError = $e->getMessage();
}

// PostgreSQL
$pgStatus = false;
$pgError  = '';
try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $config['postgres']['host'],
        $config['postgres']['port'],
        $config['postgres']['database']
    );
    $pdo = new PDO($dsn, $config['postgres']['user'], $config['postgres']['password']);
    $pgStatus = true;
} catch (Exception $e) {
    $pgError = $e->getMessage();
}

// MongoDB
$mongoStatus = false;
$mongoError  = '';
try {
    if (class_exists('MongoDB\Driver\Manager')) {
        $uri = sprintf(
            "mongodb://%s:%s@%s:%s/%s",
            $config['mongodb']['user'],
            $config['mongodb']['password'],
            $config['mongodb']['host'],
            $config['mongodb']['port'],
            $config['mongodb']['database']
        );
        $manager = new MongoDB\Driver\Manager($uri);
        $cmd     = new MongoDB\Driver\Command(['ping' => 1]);
        $manager->executeCommand('admin', $cmd);
        $mongoStatus = true;
    } else {
        $mongoError = 'MongoDB PHP extension not installed';
    }
} catch (Exception $e) {
    $mongoError = $e->getMessage();
}

// ============================================================
//  HELPERS
// ============================================================
function formatBytes($bytes) {
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function getFileIcon($filename) {
    $ext   = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php'  => '🐘', 'html' => '🌐', 'css'  => '🎨',
        'js'   => '⚡',  'json' => '📋', 'sql'  => '🗄️',
        'txt'  => '📄', 'md'   => '📝', 'jpg'  => '🖼️',
        'jpeg' => '🖼️', 'png'  => '🖼️', 'gif'  => '🖼️',
        'pdf'  => '📕', 'zip'  => '📦', 'xml'  => '📰',
    ];
    return $icons[$ext] ?? '📄';
}

function statusBadge($ok, $error = '') {
    if ($ok) return "<span class='badge-status success'>✅ Connected</span>";
    return "<span class='badge-status error' title='" . htmlspecialchars($error) . "'>❌ Failed</span>";
}

function maskPassword($pass) {
    return str_repeat('●', strlen($pass));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WAMP Docker - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #1e1e2e;
            color: #cdd6f4;
            padding: 30px;
            min-height: 100vh;
        }

        h1 { font-size: 26px; color: #cba6f7; margin-bottom: 4px; }
        .subtitle { font-size: 13px; color: #6c7086; margin-bottom: 28px; }

        .card {
            background: #313244;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            border: 1px solid #45475a;
        }

        .card h2 {
            font-size: 15px;
            color: #89b4fa;
            border-bottom: 1px solid #45475a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .info-box {
            background: #45475a;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .info-box .label { font-size: 11px; color: #6c7086; margin-bottom: 4px; }
        .info-box .value { font-size: 13px; color: #cdd6f4; word-break: break-all; }

        .db-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .db-card {
            background: #2a2a3e;
            border-radius: 10px;
            border: 1px solid #45475a;
            overflow: hidden;
        }

        .db-card-header {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #45475a;
        }

        .db-card-header.mysql    { background: #1a2a1a; }
        .db-card-header.postgres { background: #1a1a2e; }
        .db-card-header.mongodb  { background: #2a1a0a; }

        .db-title { font-size: 15px; font-weight: bold; }
        .db-title.mysql    { color: #a6e3a1; }
        .db-title.postgres { color: #89b4fa; }
        .db-title.mongodb  { color: #fab387; }

        .db-card-body { padding: 16px 18px; }

        .db-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px solid #3a3a50;
            font-size: 13px;
        }

        .db-row:last-child { border-bottom: none; }
        .db-row .key { color: #6c7086; min-width: 90px; }
        .db-row .val { color: #cdd6f4; text-align: right; word-break: break-all; }
        .db-row .url { color: #89dceb; font-family: monospace; font-size: 11px; }

        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-status.success { background: #0d3022; color: #a6e3a1; }
        .badge-status.error   { background: #3d0a0a; color: #f38ba8; cursor: help; }

        .pass-wrap { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #6c7086;
            padding: 0;
        }
        .toggle-btn:hover { color: #cdd6f4; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            text-align: left;
            padding: 10px 12px;
            background: #45475a;
            color: #cdd6f4;
            font-weight: normal;
        }
        td { padding: 10px 12px; border-bottom: 1px solid #45475a; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #3d3f54; }

        .folder { color: #f9e2af; }
        .file   { color: #a6e3a1; }
        .link   { color: #89dceb; text-decoration: none; }
        .link:hover { text-decoration: underline; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        .badge-folder { background: #3d3008; color: #f9e2af; }
        .badge-file   { background: #0d3022; color: #a6e3a1; }
        .size  { color: #6c7086; font-size: 12px; }
        .date  { color: #6c7086; font-size: 12px; }
        .empty { color: #6c7086; font-style: italic; padding: 15px 0; }

        .breadcrumb { font-size: 13px; margin-bottom: 12px; color: #6c7086; }
        .breadcrumb span { color: #cba6f7; }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .link-box {
            background: #45475a;
            border-radius: 8px;
            padding: 14px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
        }

        .link-box:hover { background: #585b70; }
        .link-box .licon { font-size: 22px; }
        .link-box .ltext { font-size: 13px; color: #cdd6f4; }
        .link-box .lsub  { font-size: 11px; color: #6c7086; }

        .copy-btn {
            background: #45475a;
            border: none;
            color: #89dceb;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            margin-left: 6px;
        }
        .copy-btn:hover { background: #585b70; }
    </style>
</head>
<body>

<h1>🐳 WAMP Docker Dashboard</h1>
<p class="subtitle">Apache · MySQL · PostgreSQL · MongoDB · phpMyAdmin · pgAdmin · Mongo Express</p>

<!-- SERVER INFO -->
<div class="card">
    <h2>⚙️ Server Info</h2>
    <div class="info-grid">
        <div class="info-box">
            <div class="label">PHP Version</div>
            <div class="value"><?php echo phpversion(); ?></div>
        </div>
        <div class="info-box">
            <div class="label">Server Time</div>
            <div class="value"><?php echo date("Y-m-d H:i:s"); ?></div>
        </div>
        <div class="info-box">
            <div class="label">Document Root</div>
            <div class="value"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></div>
        </div>
        <div class="info-box">
            <div class="label">Server Software</div>
            <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></div>
        </div>
        <div class="info-box">
            <div class="label">Total Files</div>
            <div class="value"><?php echo count(array_diff(scandir('/var/www/html'), ['.','..'])); ?> items</div>
        </div>
        <div class="info-box">
            <div class="label">Server IP</div>
            <div class="value"><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></div>
        </div>
    </div>
</div>

<!-- DATABASE CARDS -->
<div class="card">
    <h2>🗄️ Database Connections</h2>
    <div class="db-grid">

        <!-- MySQL -->
        <div class="db-card">
            <div class="db-card-header mysql">
                <span class="db-title mysql">🐬 MySQL</span>
                <?php echo statusBadge($mysqlStatus, $mysqlError); ?>
            </div>
            <div class="db-card-body">
                <div class="db-row"><span class="key">Host</span><span class="val"><?php echo $config['mysql']['host']; ?></span></div>
                <div class="db-row"><span class="key">Port</span><span class="val"><?php echo $config['mysql']['port']; ?></span></div>
                <div class="db-row"><span class="key">Database</span><span class="val"><?php echo $config['mysql']['database']; ?></span></div>
                <div class="db-row"><span class="key">Username</span><span class="val"><?php echo $config['mysql']['user']; ?></span></div>
                <div class="db-row">
                    <span class="key">Password</span>
                    <span class="val">
                        <span class="pass-wrap">
                            <span id="mysql-pass"><?php echo maskPassword($config['mysql']['password']); ?></span>
                            <button class="toggle-btn" onclick="togglePass('mysql-pass', '<?php echo $config['mysql']['password']; ?>')">👁</button>
                        </span>
                    </span>
                </div>
                <div class="db-row">
                    <span class="key">URL</span>
                    <span class="val">
                        <span class="url" id="mysql-url">mysql://<?php echo $config['mysql']['user']; ?>:<?php echo $config['mysql']['password']; ?>@<?php echo $config['mysql']['host']; ?>:<?php echo $config['mysql']['port']; ?>/<?php echo $config['mysql']['database']; ?></span>
                        <button class="copy-btn" onclick="copyText('mysql-url')">Copy</button>
                    </span>
                </div>
                <div class="db-row"><span class="key">Admin UI</span><span class="val"><a class="link" href="http://localhost:8080" target="_blank">phpMyAdmin →</a></span></div>
            </div>
        </div>

        <!-- PostgreSQL -->
        <div class="db-card">
            <div class="db-card-header postgres">
                <span class="db-title postgres">🐘 PostgreSQL</span>
                <?php echo statusBadge($pgStatus, $pgError); ?>
            </div>
            <div class="db-card-body">
                <div class="db-row"><span class="key">Host</span><span class="val"><?php echo $config['postgres']['host']; ?></span></div>
                <div class="db-row"><span class="key">Port</span><span class="val"><?php echo $config['postgres']['port']; ?></span></div>
                <div class="db-row"><span class="key">Database</span><span class="val"><?php echo $config['postgres']['database']; ?></span></div>
                <div class="db-row"><span class="key">Username</span><span class="val"><?php echo $config['postgres']['user']; ?></span></div>
                <div class="db-row">
                    <span class="key">Password</span>
                    <span class="val">
                        <span class="pass-wrap">
                            <span id="pg-pass"><?php echo maskPassword($config['postgres']['password']); ?></span>
                            <button class="toggle-btn" onclick="togglePass('pg-pass', '<?php echo $config['postgres']['password']; ?>')">👁</button>
                        </span>
                    </span>
                </div>
                <div class="db-row">
                    <span class="key">URL</span>
                    <span class="val">
                        <span class="url" id="pg-url">pgsql://<?php echo $config['postgres']['user']; ?>:<?php echo $config['postgres']['password']; ?>@<?php echo $config['postgres']['host']; ?>:<?php echo $config['postgres']['port']; ?>/<?php echo $config['postgres']['database']; ?></span>
                        <button class="copy-btn" onclick="copyText('pg-url')">Copy</button>
                    </span>
                </div>
                <div class="db-row"><span class="key">Admin UI</span><span class="val"><a class="link" href="http://localhost:8082" target="_blank">pgAdmin →</a></span></div>
            </div>
        </div>

        <!-- MongoDB -->
        <div class="db-card">
            <div class="db-card-header mongodb">
                <span class="db-title mongodb">🍃 MongoDB</span>
                <?php echo statusBadge($mongoStatus, $mongoError); ?>
            </div>
            <div class="db-card-body">
                <div class="db-row"><span class="key">Host</span><span class="val"><?php echo $config['mongodb']['host']; ?></span></div>
                <div class="db-row"><span class="key">Port</span><span class="val"><?php echo $config['mongodb']['port']; ?></span></div>
                <div class="db-row"><span class="key">Database</span><span class="val"><?php echo $config['mongodb']['database']; ?></span></div>
                <div class="db-row"><span class="key">Username</span><span class="val"><?php echo $config['mongodb']['user']; ?></span></div>
                <div class="db-row">
                    <span class="key">Password</span>
                    <span class="val">
                        <span class="pass-wrap">
                            <span id="mongo-pass"><?php echo maskPassword($config['mongodb']['password']); ?></span>
                            <button class="toggle-btn" onclick="togglePass('mongo-pass', '<?php echo $config['mongodb']['password']; ?>')">👁</button>
                        </span>
                    </span>
                </div>
                <div class="db-row">
                    <span class="key">URL</span>
                    <span class="val">
                        <span class="url" id="mongo-url">mongodb://<?php echo $config['mongodb']['user']; ?>:<?php echo $config['mongodb']['password']; ?>@<?php echo $config['mongodb']['host']; ?>:<?php echo $config['mongodb']['port']; ?>/<?php echo $config['mongodb']['database']; ?></span>
                        <button class="copy-btn" onclick="copyText('mongo-url')">Copy</button>
                    </span>
                </div>
                <div class="db-row"><span class="key">Admin UI</span><span class="val"><a class="link" href="http://localhost:8083" target="_blank">Mongo Express →</a></span></div>
            </div>
        </div>

    </div>
</div>

<!-- DIRECTORY LISTING -->
<div class="card">
    <h2>📁 Directory Listing</h2>
    <?php
        $dir         = '/var/www/html';
        $requestPath = isset($_GET['path']) ? $_GET['path'] : '';
        $requestPath = ltrim(str_replace(['..', '//'], '', $requestPath), '/');
        $currentDir  = $dir . ($requestPath ? '/' . $requestPath : '');

        echo "<div class='breadcrumb'>📌 Path: <span>/var/www/html/" . htmlspecialchars($requestPath) . "</span></div>";

        if ($requestPath) {
            $parent = dirname($requestPath);
            $parent = $parent === '.' ? '' : $parent;
            echo "<p style='margin-bottom:12px;'><a class='link' href='?path=" . urlencode($parent) . "'>⬅ Back</a></p>";
        }

        $items = @scandir($currentDir);

        if (!$items) {
            echo "<p class='empty'>⚠️ Cannot read directory.</p>";
        } else {
            $folders = [];
            $files   = [];

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $fullPath = $currentDir . '/' . $item;
                if (is_dir($fullPath)) $folders[] = $item;
                else                   $files[]   = $item;
            }

            sort($folders);
            sort($files);
            $all = array_merge($folders, $files);

            if (empty($all)) {
                echo "<p class='empty'>📭 This folder is empty.</p>";
            } else {
                echo "<table><thead><tr>
                    <th>Name</th><th>Type</th><th>Size</th><th>Last Modified</th>
                </tr></thead><tbody>";

                foreach ($all as $item) {
                    $fullPath = $currentDir . '/' . $item;
                    $isDir    = is_dir($fullPath);
                    $size     = $isDir ? '-' : formatBytes(filesize($fullPath));
                    $modified = date("Y-m-d H:i", filemtime($fullPath));
                    $itemPath = $requestPath ? $requestPath . '/' . $item : $item;

                    if ($isDir) {
                        $badge    = "<span class='badge badge-folder'>Folder</span>";
                        $nameHtml = "<a class='link folder' href='?path=" . urlencode($itemPath) . "'>📁 $item</a>";
                    } else {
                        $icon     = getFileIcon($item);
                        $badge    = "<span class='badge badge-file'>File</span>";
                        $nameHtml = "<a class='link file' href='/" . htmlspecialchars($itemPath) . "' target='_blank'>$icon $item</a>";
                    }

                    echo "<tr>
                        <td>$nameHtml</td>
                        <td>$badge</td>
                        <td class='size'>$size</td>
                        <td class='date'>$modified</td>
                    </tr>";
                }

                echo "</tbody></table>";
            }
        }
    ?>
</div>

<!-- QUICK LINKS -->
<div class="card">
    <h2>🔗 Quick Links</h2>
    <div class="links-grid">
        <a class="link-box" href="http://localhost:8080" target="_blank">
            <span class="licon">📊</span>
            <div><div class="ltext">phpMyAdmin</div><div class="lsub">MySQL Admin · :8080</div></div>
        </a>
        <a class="link-box" href="http://localhost:8082" target="_blank">
            <span class="licon">🐘</span>
            <div><div class="ltext">pgAdmin</div><div class="lsub">PostgreSQL Admin · :8082</div></div>
        </a>
        <a class="link-box" href="http://localhost:8083" target="_blank">
            <span class="licon">🍃</span>
            <div><div class="ltext">Mongo Express</div><div class="lsub">MongoDB Admin · :8083</div></div>
        </a>
        <a class="link-box" href="/phpinfo.php" target="_blank">
            <span class="licon">🔍</span>
            <div><div class="ltext">PHP Info</div><div class="lsub">Full PHP configuration</div></div>
        </a>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePass(id, plain) {
        const el = document.getElementById(id);
        if (el.dataset.shown === 'true') {
            el.textContent = '●'.repeat(plain.length);
            el.dataset.shown = 'false';
        } else {
            el.textContent = plain;
            el.dataset.shown = 'true';
        }
    }

    // Copy URL to clipboard
    function copyText(id) {
        const text = document.getElementById(id).textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target;
            btn.textContent = 'Copied!';
            btn.style.color = '#a6e3a1';
            setTimeout(() => {
                btn.textContent = 'Copy';
                btn.style.color = '';
            }, 2000);
        });
    }
</script>

</body>
</html>