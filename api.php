<?php
header('Content-Type: application/json');

require_once 'config.php';

/**
 * Simple HTTP Basic Authentication
 */
function authenticate() {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        http_response_code(401);
        die(json_encode([
            'status' => 'error',
            'message' => 'Authentication required'
        ]));
    }
    
    if ($_SERVER['PHP_AUTH_USER'] !== AUTH_USER || $_SERVER['PHP_AUTH_PW'] !== AUTH_PASSWORD) {
        http_response_code(401);
        die(json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]));
    }
}

/**
 * Send JSON response
 */
function sendResponse($status, $data = null, $message = null) {
    $response = ['status' => $status];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($message !== null) {
        $response['message'] = $message;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Authenticate request
    authenticate();
    
    // Check if action is provided
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    
    if (!$action) {
        http_response_code(400);
        sendResponse('error', null, 'No action specified');
    }
    
    // Handle getDatabases action
    if ($action === 'getDatabases') {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->query('SHOW DATABASES');
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filter out system databases (optional)
        $databases = array_filter($databases, function($db) {
            return !in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys']);
        });
        
        sendResponse('success', array_values($databases));
    }
    
    // Handle getUsers action
    else if ($action === 'getUsers') {
        if (!isset($_GET['db'])) {
            http_response_code(400);
            sendResponse('error', null, 'Database name not provided');
        }
        
        $dbName = $_GET['db'];
        
        // Validate database name format
        if (!isValidDatabaseName($dbName)) {
            http_response_code(400);
            sendResponse('error', null, 'Invalid database name format');
        }
        
        // Verify database exists in the allowed list
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query('SHOW DATABASES');
        $allowedDatabases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array($dbName, $allowedDatabases)) {
            http_response_code(400);
            sendResponse('error', null, 'Database does not exist');
        }
        
        // Connect to the selected database
        try {
            $pdo = getDatabaseConnection($dbName);
        } catch (Exception $e) {
            http_response_code(500);
            sendResponse('error', null, 'Failed to connect to database: ' . $e->getMessage());
        }
        
        // Check if bm_users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'bm_users'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            http_response_code(404);
            sendResponse('error', null, 'Table bm_users not found in this database');
        }
        
        // Fetch users from bm_users table
        try {
            $stmt = $pdo->prepare('SELECT username, password FROM bm_users');
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            if (empty($users)) {
                sendResponse('success', [], 'No records found');
            } else {
                sendResponse('success', $users);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            sendResponse('error', null, 'Failed to fetch users: ' . $e->getMessage());
        }
    }
    
    // Unknown action
    else {
        http_response_code(400);
        sendResponse('error', null, 'Unknown action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    sendResponse('error', null, DEBUG_MODE ? $e->getMessage() : 'An error occurred');
}
?>
