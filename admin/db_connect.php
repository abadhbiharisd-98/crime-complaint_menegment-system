<?php
/**
 * CRMS Database Connection
 * Optimized for PHP 8.x, Aiven Cloud MySQL (SSL enforced), and Docker/Render.
 */

// Disable mysqli default exception reporting in PHP 8.1+ to prevent fatal unhandled crashes
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Suppress non-fatal notices and deprecations in production
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Function to load .env file if present in project root
if (!function_exists('crms_load_env')) {
    function crms_load_env($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!getenv($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Attempt to load .env from current directory or root
crms_load_env(__DIR__ . '/../.env');
crms_load_env(__DIR__ . '/.env');

// Read connection configuration from environment variables
$db_server   = getenv('DB_SERVER') ?: (getenv('DB_HOST') ?: null);
$db_port     = getenv('DB_PORT') ?: null;
$db_username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: null);
$db_password = getenv('DB_PASSWORD');
if ($db_password === false) {
    $db_password = getenv('DB_PASS');
}
$db_name     = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: null);
$db_ssl      = getenv('DB_SSL');

// Support full URI connection strings (e.g. DATABASE_URL / MYSQL_URL)
$database_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($database_url) {
    $parsed = parse_url($database_url);
    if ($parsed) {
        if (!empty($parsed['host']) && empty($db_server)) $db_server = $parsed['host'];
        if (!empty($parsed['port']) && empty($db_port)) $db_port = (int)$parsed['port'];
        if (!empty($parsed['user']) && empty($db_username)) $db_username = $parsed['user'];
        if (isset($parsed['pass']) && empty($db_password)) $db_password = $parsed['pass'];
        if (!empty($parsed['path']) && empty($db_name)) $db_name = ltrim($parsed['path'], '/');
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $q_params);
            if (isset($q_params['ssl-mode']) && $q_params['ssl-mode'] !== 'DISABLED') {
                $db_ssl = 'true';
            }
        }
    }
}

// Fallback defaults
$db_server   = $db_server ?: 'localhost';
$db_port     = $db_port ? (int)$db_port : 3306;
$db_username = $db_username ?: 'root';
$db_password = ($db_password !== false && $db_password !== null) ? $db_password : '';
$db_name     = $db_name ?: 'crms_db';

// SSL detection: enforce for Aiven hosts or non-standard ports, or when explicitly requested
if ($db_ssl === null || $db_ssl === false) {
    if (strpos($db_server, 'aivencloud.com') !== false || ($db_port !== 3306 && $db_server !== 'localhost' && $db_server !== '127.0.0.1')) {
        $use_ssl = true;
    } else {
        $use_ssl = false;
    }
} else {
    $use_ssl = filter_var($db_ssl, FILTER_VALIDATE_BOOLEAN);
}

// Initialize MySQLi instance
$conn = mysqli_init();
if (!$conn) {
    error_log("CRMS Error: mysqli_init failed.");
}

$connected = false;
if ($conn) {
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    if ($use_ssl) {
        // Aiven Cloud requires SSL/TLS encrypted transport
        $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        $connected = @$conn->real_connect($db_server, $db_username, $db_password, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);
    } else {
        $connected = @$conn->real_connect($db_server, $db_username, $db_password, $db_name, $db_port);
    }
}

if (!$connected) {
    $db_connection_error = $conn ? $conn->connect_error : "MySQL initialization failure";
    error_log("CRMS Database Connection Warning: " . $db_connection_error . " (Host: $db_server, Port: $db_port, DB: $db_name)");
} else {
    $conn->set_charset("utf8mb4");
}

// Backward-compatible variable aliases
$con = $conn;
