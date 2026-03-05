import express from 'express';
import mysql from 'mysql2/promise';
import path from 'path';
import { fileURLToPath } from 'url';

const app = express();
const PORT = 8000;

// Get __dirname equivalent in ES modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Database Configuration
const DB_CONFIG = {
    host: '3.6.173.181',
    port: 3306,
    user: 'testingautomation',
    password: 'IvVq5a1X6pbR61Oc'
};

// Middleware
app.use(express.json());
app.use(express.static(__dirname));

/**
 * Validate database name (prevent SQL injection)
 */
function isValidDatabaseName(dbName) {
    return /^[a-zA-Z0-9_]+$/.test(dbName);
}

/**
 * Create MySQL connection pool
 */
async function getConnection(database = null) {
    try {
        const config = { ...DB_CONFIG };
        if (database) {
            config.database = database;
        }
        const connection = await mysql.createConnection(config);
        return connection;
    } catch (error) {
        throw new Error(`Database connection failed: ${error.message}`);
    }
}

/**
 * Send JSON response
 */
function sendResponse(res, statusCode, status, data = null, message = null) {
    const response = { status };
    if (data !== null) response.data = data;
    if (message !== null) response.message = message;
    
    res.status(statusCode).json(response);
}

/**
 * API: Get all available databases
 */
app.get('/api/databases', async (req, res) => {
    try {
        const connection = await getConnection();
        
        const [rows] = await connection.query('SHOW DATABASES');
        let databases = rows.map(row => row.Database);
        
        // Filter out system databases
        const systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        databases = databases.filter(db => !systemDbs.includes(db));
        
        connection.end();
        
        sendResponse(res, 200, 'success', databases);
    } catch (error) {
        console.error('Error fetching databases:', error);
        sendResponse(res, 500, 'error', null, `Failed to fetch databases: ${error.message}`);
    }
});

/**
 * API: Get users from selected database
 */
app.get('/api/users', async (req, res) => {
    try {
        const { db } = req.query;
        
        if (!db) {
            return sendResponse(res, 400, 'error', null, 'Database name not provided');
        }
        
        // Validate database name
        if (!isValidDatabaseName(db)) {
            return sendResponse(res, 400, 'error', null, 'Invalid database name format');
        }
        
        // Check if database exists
        let connection = await getConnection();
        const [dbs] = await connection.query('SHOW DATABASES');
        const allowedDatabases = dbs.map(row => row.Database);
        connection.end();
        
        if (!allowedDatabases.includes(db)) {
            return sendResponse(res, 400, 'error', null, 'Database does not exist');
        }
        
        // Connect to selected database
        connection = await getConnection(db);
        
        // Check if bm_users table exists
        const [tables] = await connection.query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'bm_users'",
            [db]
        );
        
        if (tables.length === 0) {
            connection.end();
            return sendResponse(res, 404, 'error', null, 'Table bm_users not found in this database');
        }
        
        // Fetch users from bm_users table
        try {
            const [users] = await connection.query('SELECT username, password FROM bm_users');
            connection.end();
            
            if (users.length === 0) {
                return sendResponse(res, 200, 'success', [], 'No records found');
            }
            
            sendResponse(res, 200, 'success', users);
        } catch (error) {
            connection.end();
            sendResponse(res, 500, 'error', null, `Failed to fetch users: ${error.message}`);
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        sendResponse(res, 500, 'error', null, `An error occurred: ${error.message}`);
    }
});

/**
 * API: Get online portal users from selected database
 */
app.get('/api/online-orders', async (req, res) => {
    try {
        const { db } = req.query;
        
        if (!db) {
            return sendResponse(res, 400, 'error', null, 'Database name not provided');
        }
        
        // Validate database name
        if (!isValidDatabaseName(db)) {
            return sendResponse(res, 400, 'error', null, 'Invalid database name format');
        }
        
        // Check if database exists
        let connection = await getConnection();
        const [dbs] = await connection.query('SHOW DATABASES');
        const allowedDatabases = dbs.map(row => row.Database);
        connection.end();
        
        if (!allowedDatabases.includes(db)) {
            return sendResponse(res, 400, 'error', null, 'Database does not exist');
        }
        
        // Connect to selected database
        connection = await getConnection(db);
        
        // Check if bm_customercontactinfo table exists
        const [tables] = await connection.query(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'bm_customercontactinfo'",
            [db]
        );
        
        if (tables.length === 0) {
            connection.end();
            return sendResponse(res, 404, 'error', null, 'Table bm_customercontactinfo not found in this database');
        }
        
        // Fetch online portal users from bm_customercontactinfo table
        try {
            const [users] = await connection.query(
                'SELECT cci_username, cci_password FROM bm_customercontactinfo WHERE cci_isonlineportal = 1 AND cci_status = 0'
            );
            connection.end();
            
            if (users.length === 0) {
                return sendResponse(res, 200, 'success', [], 'No records found');
            }
            
            sendResponse(res, 200, 'success', users);
        } catch (error) {
            connection.end();
            sendResponse(res, 500, 'error', null, `Failed to fetch online orders: ${error.message}`);
        }
    } catch (error) {
        console.error('Error fetching online orders:', error);
        sendResponse(res, 500, 'error', null, `An error occurred: ${error.message}`);
    }
});

/**
 * Serve index.html for root path
 */
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

/**
 * Start server
 */
app.listen(PORT, () => {
    console.log(`🚀 Database User Viewer is running at http://localhost:${PORT}`);
    console.log(`📁 Open your browser and navigate to http://localhost:${PORT}`);
});
