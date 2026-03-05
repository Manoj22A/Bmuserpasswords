# Database User Viewer Tool

A simple internal tool for viewing users from a remote MySQL database. Built with HTML, CSS, JavaScript, jQuery, and PHP.

## Features

✅ Dynamic database selection via dropdown
✅ AJAX-based user interface (no page reloads)
✅ Secure PDO prepared statements
✅ Database name validation against `SHOW DATABASES` whitelist
✅ HTTP Basic Authentication
✅ Error handling for missing databases, tables, and connection failures
✅ Responsive design with loading indicators
✅ Single API endpoint handling multiple actions
✅ XSS protection with HTML escaping

## Files

- **index.html** - Frontend interface with jQuery AJAX
- **api.php** - Backend API endpoint
- **config.php** - Configuration and database connection helpers

## Setup Instructions

### 1. Prerequisites

- PHP 7.0+ with PDO MySQL extension
- MySQL server accessible at `3.6.173.181:3306`
- A web server (Apache, Nginx, etc.)

### 2. Configuration

Edit `config.php` and update the following:

```php
define('DB_HOST', '3.6.173.181');        // MySQL server IP
define('DB_PORT', 3306);                  // MySQL port
define('DB_ROOT_USER', 'root');           // MySQL root username
define('DB_ROOT_PASSWORD', 'your_password'); // MySQL root password
define('AUTH_USER', 'admin');             // Tool authentication username
define('AUTH_PASSWORD', 'admin123');      // Tool authentication password
```

### 3. File Placement

Place all three files in your web root directory:
- `config.php`
- `api.php`
- `index.html`

### 4. Set Permissions

Ensure proper file permissions:
```bash
chmod 644 index.html api.php config.php
```

### 5. Access the Tool

Open your browser and navigate to:
```
http://your-server/index.html
```

When prompted, use the credentials you configured:
- Username: `admin` (default)
- Password: `admin123` (default)

## How It Works

### Step 1: Load Databases
When the page loads, the tool:
- Authenticates via HTTP Basic Auth
- Fetches all available databases using `SHOW DATABASES`
- Filters out system databases (information_schema, mysql, performance_schema, sys)
- Populates the dropdown menu

### Step 2: Select Database
When you select a database:
- AJAX request is sent to `api.php?action=getUsers&db=database_name`
- Database name is validated for security
- System checks if the database exists
- Connects to the selected database
- Checks if `bm_users` table exists

### Step 3: Display Users
On success:
- Fetches all records from `bm_users` (username, password columns)
- Displays results in a responsive table
- Shows row count

On error:
- Displays appropriate error message
- Examples: "Database does not exist", "Table not found", "Connection failed"

## API Endpoints

### Get Databases
```
GET /api.php?action=getDatabases
Response: {
  "status": "success",
  "data": ["database1", "database2", "database3"]
}
```

### Get Users
```
GET /api.php?action=getUsers&db=database_name
Response: {
  "status": "success",
  "data": [
    {"username": "user1", "password": "pass1"},
    {"username": "user2", "password": "pass2"}
  ]
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description here"
}
```

## Security Features

1. **HTTP Basic Authentication**
   - All requests require username/password authentication
   - Credentials checked on every API call

2. **PDO Prepared Statements**
   - All queries use parameterized statements
   - Prevents SQL injection attacks

3. **Database Name Validation**
   - Database names validated against regex pattern
   - Names must match `SHOW DATABASES` result
   - Prevents injection attacks

4. **Frontend Security**
   - HTML escaping on all user-facing data
   - XSS protection for displayed passwords
   - CSRF token could be added for extra protection (future enhancement)

5. **Credential Protection**
   - Database credentials stored in `config.php` (never exposed in frontend)
   - `config.php` should be restricted and never publicly accessible

## Usage Tips

### Refresh Data
Click the "Refresh Data" button to reload users from the currently selected database without changing the selection.

### Clear Results
Click "Clear Results" to hide the table and start fresh.

### Troubleshooting

**"Authentication required"**
- Browser did not send credentials
- Click "Cancel" on auth prompt if asked, clear browser cache, and reload

**"Invalid credentials"**
- Username or password in `config.php` is incorrect
- Check the `AUTH_USER` and `AUTH_PASSWORD` values

**"Database connection failed"**
- MySQL server not accessible at the configured IP/port
- Check network connectivity
- Verify MySQL credentials in `config.php`

**"Table bm_users not found"**
- The selected database doesn't have a `bm_users` table
- Select a different database

**"No records found"**
- The `bm_users` table exists but has no data
- This is not an error; it's expected behavior

## Future Enhancements

- [ ] CSRF token protection
- [ ] Session-based authentication (instead of HTTP Basic Auth)
- [ ] Password hashing and salting
- [ ] Audit logs for API calls
- [ ] Export to CSV functionality
- [ ] Per-database access control
- [ ] Table/column selection interface
- [ ] Search and filter capabilities
- [ ] Pagination for large result sets

## Requirements Met

✅ HTML frontend with CSS styling
✅ jQuery for AJAX requests
✅ Core PHP (no frameworks)
✅ PDO database connections
✅ Dynamic database dropdown using `SHOW DATABASES`
✅ Fetch users with `SELECT username, password FROM bm_users`
✅ Table display of results
✅ Error handling for missing databases/tables/connections
✅ Security: PDO prepared statements, database validation, no credential exposure
✅ Basic authentication
✅ Single API endpoint (`api.php`) with multiple actions
✅ No page reloads (all AJAX)
✅ Loading indicators
✅ Responsive design

## License

Internal tool - For authorized use only.
