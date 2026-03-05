<?php
/**
 * Database User Viewer - Single PHP File
 * Handles both frontend HTML interface and backend API functionality
 */

// Database Configuration
define('DB_HOST', '3.6.173.181');
define('DB_PORT', 3306);
define('DB_ROOT_USER', 'testingteam');
define('DB_ROOT_PASSWORD', 'C24ist[LxDG8*)i9'); // Change this to your actual password

define('DEBUG_MODE', false);

/**
 * Check if this is an API call
 */
$isApiCall = isset($_GET['action']);

/**
 * Create PDO connection to a specific database
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
 * Validate database name
 */
function isValidDatabaseName($dbName) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
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
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

/**
 * Handle API requests
 */
if ($isApiCall) {
    header('Content-Type: application/json');
    
    try {
        
        $action = $_GET['action'];
        
        if ($action === 'getDatabases') {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->query('SHOW DATABASES');
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $databases = array_filter($databases, function($db) {
                return !in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys']);
            });
            
            sendResponse('success', array_values($databases));
        }
        
        else if ($action === 'getUsers') {
            if (!isset($_GET['db'])) {
                http_response_code(400);
                sendResponse('error', null, 'Database name not provided');
            }
            
            $dbName = $_GET['db'];
            
            if (!isValidDatabaseName($dbName)) {
                http_response_code(400);
                sendResponse('error', null, 'Invalid database name format');
            }
            
            $pdo = getDatabaseConnection();
            $stmt = $pdo->query('SHOW DATABASES');
            $allowedDatabases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array($dbName, $allowedDatabases)) {
                http_response_code(400);
                sendResponse('error', null, 'Database does not exist');
            }
            
            try {
                $pdo = getDatabaseConnection($dbName);
            } catch (Exception $e) {
                http_response_code(500);
                sendResponse('error', null, 'Failed to connect to database: ' . $e->getMessage());
            }
            
            $stmt = $pdo->query("SHOW TABLES LIKE 'bm_users'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                http_response_code(404);
                sendResponse('error', null, 'Table bm_users not found in this database');
            }
            
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
        
        else {
            http_response_code(400);
            sendResponse('error', null, 'Unknown action');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        sendResponse('error', null, DEBUG_MODE ? $e->getMessage() : 'An error occurred');
    }
}

/**
 * Serve HTML interface
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database User Viewer</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 900px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        select, input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #666;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            display: none;
        }

        table.show {
            display: table;
        }

        thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #667eea;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 14px;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
            display: none;
        }

        .no-records.show {
            display: block;
        }

        .results {
            margin-top: 30px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-refresh {
            background-color: #667eea;
            color: white;
        }

        .btn-refresh:hover:not(:disabled) {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-refresh:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-clear {
            background-color: #6c757d;
            color: white;
        }

        .btn-clear:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .record-count {
            color: #666;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 22px;
                margin-bottom: 20px;
            }

            th, td {
                padding: 10px;
                font-size: 12px;
            }

            button {
                font-size: 12px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database User Viewer</h1>

        <div id="successAlert" class="alert alert-success"></div>
        <div id="errorAlert" class="alert alert-error"></div>
        <div id="infoAlert" class="alert alert-info"></div>

        <form id="dataForm">
            <div class="form-group">
                <label for="database">Select Database:</label>
                <select id="database" name="database" required>
                    <option value="">-- Loading databases --</option>
                </select>
            </div>

            <div class="button-group">
                <button type="button" class="btn-refresh" id="refreshBtn">Refresh Data</button>
                <button type="button" class="btn-clear" id="clearBtn">Clear Results</button>
            </div>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <div class="loading-text">Loading data...</div>
        </div>

        <div class="results" id="results" style="display: none;">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                </tbody>
            </table>
            <div class="no-records" id="noRecords">No records found in this database.</div>
            <div class="record-count" id="recordCount"></div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Load databases on page load
            loadDatabases();

            // Load users when database is selected
            $('#database').on('change', function() {
                if ($(this).val()) {
                    loadUsers($(this).val());
                }
            });

            // Refresh button
            $('#refreshBtn').on('click', function() {
                if ($('#database').val()) {
                    loadUsers($('#database').val());
                } else {
                    showAlert('error', 'Please select a database first.');
                }
            });

            // Clear button
            $('#clearBtn').on('click', function() {
                clearResults();
            });
        });

        /**
         * Load available databases
         */
        function loadDatabases() {
            showLoading(true);
            hideAllAlerts();

            $.ajax({
                url: 'index.php',
                type: 'GET',
                data: { action: 'getDatabases' },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        populateDatabaseDropdown(response.data);
                        showAlert('info', 'Databases loaded successfully. Select a database to view users.');
                    } else {
                        showAlert('error', response.message || 'Failed to load databases');
                    }
                },
                error: function(xhr, status, error) {
                    handleAjaxError(xhr);
                },
                complete: function() {
                    showLoading(false);
                }
            });
        }

        /**
         * Populate database dropdown
         */
        function populateDatabaseDropdown(databases) {
            var $select = $('#database');
            $select.empty();
            $select.append($('<option></option>').attr('value', '').text('-- Select a database --'));

            $.each(databases, function(index, db) {
                $select.append($('<option></option>').attr('value', db).text(db));
            });
        }

        /**
         * Load users from selected database
         */
        function loadUsers(database) {
            showLoading(true);
            hideAllAlerts();
            clearResults();

            $.ajax({
                url: 'index.php',
                type: 'GET',
                data: {
                    action: 'getUsers',
                    db: database
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.data && response.data.length > 0) {
                            displayUsers(response.data);
                            $('#results').show();
                        } else {
                            $('#results').show();
                            $('#noRecords').addClass('show');
                            showAlert('info', 'No records found in the bm_users table.');
                        }
                    } else {
                        showAlert('error', response.message || 'Failed to load users');
                    }
                },
                error: function(xhr, status, error) {
                    handleAjaxError(xhr);
                },
                complete: function() {
                    showLoading(false);
                }
            });
        }

        /**
         * Display users in table
         */
        function displayUsers(users) {
            var $tbody = $('#usersTableBody');
            $tbody.empty();

            $.each(users, function(index, user) {
                var row = '<tr>' +
                    '<td>' + escapeHtml(user.username || '') + '</td>' +
                    '<td><code style="background-color: #f5f5f5; padding: 4px 8px; border-radius: 3px; font-family: monospace; font-size: 12px;">' + escapeHtml(user.password || '') + '</code></td>' +
                    '</tr>';
                $tbody.append(row);
            });

            $('#usersTable').addClass('show');
            $('#noRecords').removeClass('show');
            $('#recordCount').text('Total records: ' + users.length);
        }

        /**
         * Show/hide loading indicator
         */
        function showLoading(show) {
            if (show) {
                $('#loading').show();
            } else {
                $('#loading').hide();
            }
        }

        /**
         * Show alert message
         */
        function showAlert(type, message) {
            var $alert = $('#' + type + 'Alert');
            $alert.text(message).show();
        }

        /**
         * Hide all alerts
         */
        function hideAllAlerts() {
            $('#successAlert').hide();
            $('#errorAlert').hide();
            $('#infoAlert').hide();
        }

        /**
         * Clear results
         */
        function clearResults() {
            $('#usersTable').removeClass('show');
            $('#usersTableBody').empty();
            $('#noRecords').removeClass('show');
            $('#recordCount').empty();
            $('#results').hide();
            hideAllAlerts();
        }

        /**
         * Handle AJAX errors
         */
        function handleAjaxError(xhr) {
            var message = 'An error occurred';

            if (xhr.status === 401) {
                message = 'Authentication failed. Please check your credentials.';
            } else if (xhr.status === 404) {
                message = 'Resource not found (404)';
            } else if (xhr.status === 500) {
                message = 'Server error (500). Please try again.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.statusText) {
                message = 'Error: ' + xhr.statusText;
            }

            showAlert('error', message);
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            if (!text) return '';
            return $('<div/>').text(text).html();
        }
    </script>
</body>
</html>
