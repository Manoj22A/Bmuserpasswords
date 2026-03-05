<?php
// Database Configuration
// This file contains all configuration settings for the application

define('DB_HOST', '3.6.173.181');
define('DB_PORT', 3306);
define('DB_ROOT_USER', 'root');
define('DB_ROOT_PASSWORD', 'your_root_password_here'); // Change this to your actual password

// Simple authentication credentials for the tool
define('AUTH_USER', 'admin');
define('AUTH_PASSWORD', 'admin123'); // Change this to a secure password

// Enable error logging
define('DEBUG_MODE', false); // Set to true for development

/**
 * Create PDO connection to a specific database
 * 
 * @param string $database Optional database name
 * @return PDO
 * @throws PDOException
 */
function getDatabaseConnection($database = null) {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ':' . DB_PORT;
        if ($database) {
            $dsn .= ';dbname=' . $database;
        }
        
        $pdo = new PDO(
            $dsn,
            DB_ROOT_USER,
            DB_ROOT_PASSWORD,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        );
        
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

/**
 * Check if a database name is valid (alphanumeric and underscore only)
 * This is an additional security measure
 * 
 * @param string $dbName
 * @return bool
 */
function isValidDatabaseName($dbName) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
}
?>
