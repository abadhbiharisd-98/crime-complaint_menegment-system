<?php
/**
 * 1-Click Aiven Cloud MySQL Database Importer & Health Checker
 * Crime Complaints Reporting Management System (CRMS)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable default MySQLi error throwing in PHP 8.1+
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Include database connection
include_once __DIR__ . '/admin/db_connect.php';

$message = '';
$messageType = '';
$importedCount = 0;
$importErrors = [];

// Configuration attributes
$activeHost = $db_server ?? (getenv('DB_SERVER') ?: 'localhost');
$activePort = $db_port ?? (getenv('DB_PORT') ?: 3306);
$activeUser = $db_username ?? (getenv('DB_USERNAME') ?: 'root');
$activeDb   = $db_name ?? (getenv('DB_NAME') ?: 'crms_db');
$activeSsl  = isset($use_ssl) ? $use_ssl : (getenv('DB_SSL') === 'true');

// Test connection health
$isConnected = false;
$serverVersion = '';
$sslCipher = '';
$connError = '';

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error && @mysqli_ping($conn)) {
    $isConnected = true;
    $serverVersion = @mysqli_get_server_info($conn);
    $sslRes = @mysqli_query($conn, "SHOW STATUS LIKE 'Ssl_cipher'");
    if ($sslRes && $sslRes instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($sslRes);
        $sslCipher = !empty($row['Value']) ? $row['Value'] : 'TLS (Encrypted)';
    } else {
        $sslCipher = $activeSsl ? 'TLS Active' : 'Plaintext/Disabled';
    }
} else {
    $connError = isset($db_connection_error) ? $db_connection_error : (mysqli_connect_error() ?: 'Connection could not be established.');
}

// Quote-aware SQL statement splitter function
if (!function_exists('splitSqlStatements')) {
    function splitSqlStatements($sqlText) {
        $statements = [];
        $current = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $len = strlen($sqlText);

        for ($i = 0; $i < $len; $i++) {
            $char = $sqlText[$i];

            if ($char === '\\' && ($inSingleQuote || $inDoubleQuote)) {
                $current .= $char;
                if ($i + 1 < $len) {
                    $i++;
                    $current .= $sqlText[$i];
                }
                continue;
            }

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
                $current .= $char;
                continue;
            }

            if ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
                $current .= $char;
                continue;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
                $stmt = trim($current);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $last = trim($current);
        if (!empty($last)) {
            $statements[] = $last;
        }

        return $statements;
    }
}

// Handle 1-Click Import / Recreate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['import_sql', 'recreate_sql'])) {
    if (!$isConnected) {
        $message = "Cannot import: Database connection is not established. Check your environment variables.";
        $messageType = "danger";
    } else {
        // Find SQL file
        $candidates = [
            __DIR__ . '/database/crms_db.sql',
            __DIR__ . '/crms_db.sql',
            __DIR__ . '/Crime_Complaint_Management_System/database/crms_db.sql'
        ];
        $sqlPath = null;
        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $sqlPath = $cand;
                break;
            }
        }

        if (!$sqlPath) {
            $message = "Database dump file 'crms_db.sql' not found on the server.";
            $messageType = "danger";
        } else {
            $sqlContent = file_get_contents($sqlPath);

            // Clean SQL directives that fail on restricted cloud databases
            $sqlContent = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $sqlContent);
            $sqlContent = preg_replace('/USE\s+[^;]+;/i', '', $sqlContent);

            // Aiven Cloud MySQL requires disabling sql_require_primary_key for initial dump load
            @mysqli_query($conn, "SET SESSION sql_require_primary_key = 0");
            @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
            @mysqli_query($conn, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            // If clean recreate requested, drop old tables first
            if ($_POST['action'] === 'recreate_sql') {
                $tablesToDrop = ['complaints_action', 'complaints', 'complainants', 'responders_team', 'stations', 'system_settings', 'users'];
                foreach ($tablesToDrop as $tbl) {
                    @mysqli_query($conn, "DROP TABLE IF EXISTS `$tbl`");
                }
            }

            // Filter comment lines
            $cleanLines = [];
            foreach (explode("\n", $sqlContent) as $line) {
                $tr = trim($line);
                if (strpos($tr, '--') === 0 || strpos($tr, '/*') === 0 || strpos($tr, '#') === 0) {
                    continue;
                }
                $cleanLines[] = $line;
            }
            $cleanSql = implode("\n", $cleanLines);

            // Split into discrete SQL statements respecting quotes
            $statements = splitSqlStatements($cleanSql);

            foreach ($statements as $stmt) {
                $res = @mysqli_query($conn, $stmt);
                if ($res) {
                    $importedCount++;
                } else {
                    $err = mysqli_error($conn);
                    if (!empty($err) && strpos($err, 'already exists') === false && strpos($err, 'Duplicate entry') === false) {
                        $importErrors[] = substr($stmt, 0, 80) . '... => ' . $err;
                    }
                }
            }

            // Ensure Admin user exists with default credentials (admin / admin123)
            $adminCheck = @mysqli_query($conn, "SELECT * FROM `users` WHERE `username` = 'admin'");
            if (!$adminCheck || mysqli_num_rows($adminCheck) === 0) {
                $adminPass = md5('admin123');
                @mysqli_query($conn, "INSERT INTO `users` (`id`, `name`, `username`, `password`, `type`) VALUES (1, 'Administrator', 'admin', '$adminPass', 1) ON DUPLICATE KEY UPDATE `password` = '$adminPass', `type` = 1");
            }

            @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

            $message = "Database schema and initial seed data imported successfully! ($importedCount statements processed).";
            $messageType = "success";
        }
    }
}

// Inspect tables and records
$expectedTables = ['complainants', 'complaints', 'complaints_action', 'responders_team', 'stations', 'system_settings', 'users'];
$tableStatus = [];
$adminExists = false;

if ($isConnected) {
    foreach ($expectedTables as $table) {
        $check = @mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `$table`");
        if ($check && $check instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($check);
            $tableStatus[$table] = (int)$row['cnt'];
        } else {
            $tableStatus[$table] = null; // Table does not exist
        }
    }

    if (isset($tableStatus['users']) && $tableStatus['users'] !== null && $tableStatus['users'] > 0) {
        $admQry = @mysqli_query($conn, "SELECT * FROM `users` WHERE `username` = 'admin'");
        if ($admQry && mysqli_num_rows($admQry) > 0) {
            $adminExists = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aiven Cloud MySQL Database Setup - CRMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            padding: 30px 15px;
        }
        .setup-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            margin-bottom: 25px;
        }
        .setup-header {
            border-bottom: 1px solid #334155;
            padding: 20px 25px;
        }
        .setup-body {
            padding: 25px;
        }
        .badge-ssl {
            background: #059669;
            color: white;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .metric-tile {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .metric-title {
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-value {
            color: #f8fafc;
            font-size: 1.15rem;
            font-weight: 600;
            word-break: break-all;
        }
        .table-dark {
            background-color: #0f172a;
        }
        .table-dark td, .table-dark th {
            border-color: #334155;
        }
        .btn-action {
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 960px;">
    <!-- Title Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold mb-1"><i class="fas fa-shield-alt text-primary mr-2"></i> CRMS Database Installer</h2>
            <p class="text-muted mb-0">Aiven Cloud MySQL SSL Integration & Diagnostic Health Dashboard</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-light btn-sm mr-2"><i class="fas fa-home mr-1"></i> Home</a>
            <a href="admin/login.php" class="btn btn-primary btn-sm"><i class="fas fa-user-lock mr-1"></i> Admin Login</a>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <?php if (!empty($importErrors)): ?>
            <div class="mt-2 small">
                <strong>Minor notices:</strong>
                <ul>
                    <?php foreach (array_slice($importErrors, 0, 5) as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <!-- Connection Health Status -->
    <div class="setup-card">
        <div class="setup-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold">
                <i class="fas fa-server mr-2 text-info"></i> Aiven MySQL Connection Status
            </h5>
            <div>
                <?php if ($isConnected): ?>
                    <span class="badge badge-success px-3 py-2"><i class="fas fa-link mr-1"></i> Connected & Online</span>
                <?php else: ?>
                    <span class="badge badge-danger px-3 py-2"><i class="fas fa-unlink mr-1"></i> Connection Offline</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="setup-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="metric-tile">
                        <div class="metric-title">Database Host</div>
                        <div class="metric-value"><?php echo htmlspecialchars($activeHost); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-tile">
                        <div class="metric-title">Port</div>
                        <div class="metric-value"><?php echo htmlspecialchars($activePort); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-tile">
                        <div class="metric-title">Database Name</div>
                        <div class="metric-value"><?php echo htmlspecialchars($activeDb); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-title">Database User</div>
                        <div class="metric-value"><?php echo htmlspecialchars($activeUser); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-title">SSL / TLS Mode</div>
                        <div class="metric-value">
                            <?php if ($activeSsl): ?>
                                <span class="badge-ssl"><i class="fas fa-lock mr-1"></i> <?php echo htmlspecialchars($sslCipher ?: 'Active'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Disabled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-title">MySQL Engine Version</div>
                        <div class="metric-value"><?php echo htmlspecialchars($serverVersion ?: 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$isConnected): ?>
            <div class="alert alert-danger mb-0 mt-2">
                <strong><i class="fas fa-exclamation-circle mr-1"></i> Connection Diagnostics:</strong><br>
                <?php echo htmlspecialchars($connError); ?>
                <hr>
                <small>Verify your Render Environment Variables (<code>DB_SERVER</code>, <code>DB_PORT</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_SSL=true</code>).</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Database Tables Status -->
    <div class="setup-card">
        <div class="setup-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold">
                <i class="fas fa-table mr-2 text-warning"></i> Schema Tables & Data Integrity
            </h5>
            <div>
                <?php if ($adminExists): ?>
                    <span class="badge badge-success px-3 py-1"><i class="fas fa-check-circle mr-1"></i> Admin Account Ready</span>
                <?php else: ?>
                    <span class="badge badge-warning px-3 py-1"><i class="fas fa-clock mr-1"></i> Setup Pending</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="setup-body">
            <table class="table table-dark table-hover mb-0">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Status</th>
                        <th>Record Count</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $descriptions = [
                        'complainants'       => 'Public users filing complaints',
                        'complaints'         => 'Reported incidents & complaint tickets',
                        'complaints_action'  => 'Assigned responder actions and status updates',
                        'responders_team'    => 'Police / rescue responder teams',
                        'stations'           => 'Police & municipal jurisdiction stations',
                        'system_settings'    => 'Portal title, contact, about info, logo',
                        'users'              => 'Administrators and staff login accounts'
                    ];
                    foreach ($expectedTables as $tName):
                        $count = $tableStatus[$tName] ?? null;
                    ?>
                    <tr>
                        <td><code><?php echo $tName; ?></code></td>
                        <td>
                            <?php if ($count !== null): ?>
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Exists</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i> Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $count !== null ? number_format($count) . ' records' : '<em class="text-muted">None</em>'; ?>
                        </td>
                        <td class="text-muted small"><?php echo $descriptions[$tName] ?? ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1-Click Action Hub -->
    <div class="setup-card">
        <div class="setup-header">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-bolt mr-2 text-warning"></i> 1-Click Setup & Actions</h5>
        </div>
        <div class="setup-body">
            <div class="row align-items-center">
                <div class="col-md-7 mb-3 mb-md-0">
                    <p class="mb-1 text-light"><strong>Initialize or Update Database:</strong></p>
                    <p class="text-muted small mb-0">
                        Automatically parses <code>crms_db.sql</code>, cleans foreign keys, creates all 7 tables, seeds default responder stations, and registers the master administrator account.
                    </p>
                </div>
                <div class="col-md-5 text-md-right">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="import_sql">
                        <button type="submit" class="btn btn-success btn-action mr-2" <?php echo !$isConnected ? 'disabled' : ''; ?>>
                            <i class="fas fa-download mr-1"></i> 1-Click Import
                        </button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Drop existing tables and completely rebuild clean database?');">
                        <input type="hidden" name="action" value="recreate_sql">
                        <button type="submit" class="btn btn-outline-danger btn-action" <?php echo !$isConnected ? 'disabled' : ''; ?>>
                            <i class="fas fa-redo mr-1"></i> Clean Rebuild
                        </button>
                    </form>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <!-- Default Credentials Box -->
            <div class="alert alert-info bg-transparent border-info mb-0">
                <h6 class="font-weight-bold text-info"><i class="fas fa-key mr-2"></i> Default Administrator Credentials</h6>
                <div class="row mt-2">
                    <div class="col-sm-4">
                        <strong>Username:</strong> <code>admin</code>
                    </div>
                    <div class="col-sm-4">
                        <strong>Password:</strong> <code>admin123</code>
                    </div>
                    <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                        <a href="admin/login.php" class="btn btn-info btn-sm font-weight-bold"><i class="fas fa-sign-in-alt mr-1"></i> Open Admin Portal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
