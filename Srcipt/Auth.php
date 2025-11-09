<?php
class Auth
{
    private $db;
    private $logDir;

    public function __construct()
    {
        include __DIR__ . '/db_connect.php';
        $this->db = $conn;

        // Set log directory and ensure it exists
        $this->logDir = __DIR__ . '/../logs/';
        $this->ensureLogDirectory();
    }

    /**
     * Ensure log directory exists and is writable
     */
    private function ensureLogDirectory()
    {
        if (!is_dir($this->logDir)) {
            // Create the logs directory
            if (!mkdir($this->logDir, 0755, true)) {
                error_log("Failed to create log directory: " . $this->logDir);
                return false;
            }
        }

        // Check if directory is writable
        if (!is_writable($this->logDir)) {
            error_log("Log directory is not writable: " . $this->logDir);
            return false;
        }

        return true;
    }

    public function login($username, $password)
    {
        // More reasonable rate limiting (10 attempts per 15 minutes)
        if ($this->isRateLimited($username)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
        }

        $stmt = $this->db->prepare("SELECT id, username, password, role, full_name, is_active FROM users WHERE username = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error. Please try again.'];
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!$user['is_active']) {
                $this->logAttempt($username, 'FAILED: Account inactive');
                return ['success' => false, 'message' => 'Account is deactivated.'];
            }

            if (password_verify($password, $user['password'])) {
                // Login successful
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Update last login
                $update_stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                $this->logAttempt($username, 'SUCCESS');
                $this->clearRateLimit($username);

                return ['success' => true, 'message' => 'Login successful!', 'role' => $user['role']];
            }
        }

        $this->logAttempt($username, 'FAILED: Invalid credentials');
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    public function checkAuth()
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ../login.php');
            exit();
        }

        // Session timeout (8 hours)
        if (time() - $_SESSION['login_time'] > 28800) {
            $this->logout();
            header('Location: ../login.php?timeout=1');
            exit();
        }

        // Update session time
        $_SESSION['login_time'] = time();
    }

    public function hasRole($requiredRole) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    
    // Role hierarchy: admin > security > receptionist
    $roleHierarchy = [
        'admin' => ['admin', 'security', 'receptionist'],
        'security' => ['security', 'receptionist'],
        'receptionist' => ['receptionist']
    ];
    
    // Check if user's role has permission to access the required role
    if (isset($roleHierarchy[$userRole])) {
        return in_array($requiredRole, $roleHierarchy[$userRole]);
    }
    
    return false;
}

    public function logout()
    {
        // Log logout action
        if (isset($_SESSION['username'])) {
            $this->logAttempt($_SESSION['username'], 'LOGOUT');
        }

        session_destroy();
        session_start();
    }

    private function isRateLimited($username)
    {
        $key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        $attempts = $_SESSION[$key] ?? 0;

        // More reasonable: 10 attempts max
        if ($attempts >= 10) {
            return true;
        }

        $_SESSION[$key] = $attempts + 1;
        return false;
    }

    private function clearRateLimit($username)
    {
        $key = 'login_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
        unset($_SESSION[$key]);
    }

    private function logAttempt($username, $status)
    {
        $logEntry = date('Y-m-d H:i:s') . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | User: " . $username . " | " . $status . PHP_EOL;

        $logFile = $this->logDir . 'auth.log';

        try {
            // Try to write to log file
            if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
                // If writing fails, log to PHP error log instead
                error_log("Auth log write failed: " . $logEntry);
            }
        } catch (Exception $e) {
            // Fallback to PHP error log
            error_log("Auth log error: " . $e->getMessage() . " - Entry: " . $logEntry);
        }
    }

    /**
     * Public method to clear rate limits (for admin use)
     */
    public function clearAllRateLimits()
    {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'login_attempts_') === 0) {
                unset($_SESSION[$key]);
            }
        }
        return true;
    }
}
?>