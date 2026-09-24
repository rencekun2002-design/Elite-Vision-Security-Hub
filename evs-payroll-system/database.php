<?php
/**
 * Database Connection and Management
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->initializeDatabase();
        } catch (PDOException $e) {
            logMessage('Database connection error: ' . $e->getMessage(), 'error');
            die('Database connection failed');
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeDatabase()
    {
        if (!file_exists(DB_PATH)) {
            $this->createDatabase();
        } else {
            // Verify tables exist
            $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
            if (empty($tables)) {
                $this->createDatabase();
            }
        }
    }

    private function createDatabase()
    {
        try {
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $this->pdo->exec($schema);
            logMessage('Database initialized successfully', 'info');

            // Insert default settings
            $this->insertDefaultSettings();
        } catch (Exception $e) {
            logMessage('Database initialization error: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function insertDefaultSettings()
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM settings');
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result['count'] == 0) {
                $stmt = $this->pdo->prepare('
                    INSERT INTO settings (company_name, company_email, company_phone, address, tax_rate, smtp_from_email)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    COMPANY_NAME,
                    COMPANY_EMAIL,
                    COMPANY_PHONE,
                    COMPANY_ADDRESS,
                    DEFAULT_TAX_RATE,
                    SMTP_FROM_EMAIL
                ]);
            }
        } catch (Exception $e) {
            logMessage('Error inserting default settings: ' . $e->getMessage(), 'error');
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function query($sql)
    {
        return $this->pdo->query($sql);
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}

// Get database instance
$db = Database::getInstance();
?>