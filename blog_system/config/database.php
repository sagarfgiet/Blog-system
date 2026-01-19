<?php
/**
 * Database Configuration
 * Using PDO for secure database connections
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    // Database credentials - CHANGE THESE!
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'blog_system';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    
    // Prevent direct instantiation
    private function __construct() {
        try {
            // Create PDO connection
            $dsn = 'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true, // Optional: persistent connections
            ];
            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error and show user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Sorry, we're experiencing technical difficulties. Please try again later.");
        }
    }
    
    // Singleton pattern - only one instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get PDO connection
    public function getConnection() {
        return $this->pdo;
    }
    
    // Helper method for prepared statements
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
}

// Create a global function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

$url = parse_url(getenv("MYSQL_URL"));

$host = $url["host"];
$user = $url["user"];
$pass = $url["pass"];
$db   = ltrim($url["path"], "/");
$port = $url["port"];

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
  die("Database Connection Error: " . $conn->connect_error);
}


?>
