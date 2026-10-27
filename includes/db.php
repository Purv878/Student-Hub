<?php
/**
 * Database Connection Module
 * Subject: ITUE203 - Web Development Frameworks (Practical 8)
 * Student ID: 25CE089
 * Supports both MySQLi and PDO with prepared statements and error logging
 */

// Database Configuration Constants
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'studenthub');
define('DB_CHARSET', 'utf8mb4');

// Global connection instances
$conn = null; // MySQLi
$pdo = null;  // PDO

/**
 * Initialize MySQLi Connection
 * @return mysqli
 */
function getMysqliConnection() {
    global $conn;
    if ($conn instanceof mysqli && $conn->ping()) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset(DB_CHARSET);
        return $conn;
    } catch (mysqli_sql_exception $e) {
        error_log("MySQLi Connection Error: " . $e->getMessage());
        die("<div style='font-family: Arial; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px; margin: 20px;'>
                <h3>Database Connection Failed (MySQLi)</h3>
                <p>Could not connect to MySQL server at <strong>" . DB_HOST . ":" . DB_PORT . "</strong>.</p>
                <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
                <p><em>Please ensure MySQL is running in XAMPP.</em></p>
             </div>");
    }
}

/**
 * Initialize PDO Connection
 * @return PDO
 */
function getPdoConnection() {
    global $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("PDO Connection Error: " . $e->getMessage());
        die("<div style='font-family: Arial; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px; margin: 20px;'>
                <h3>Database Connection Failed (PDO)</h3>
                <p>Could not establish PDO connection to <strong>" . DB_NAME . "</strong>.</p>
                <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
             </div>");
    }
}

// Auto-initialize default instances for convenience
$conn = getMysqliConnection();
$pdo = getPdoConnection();
?>
