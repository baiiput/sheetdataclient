<?php
// ========================================
// 🗄️ DATABASE CONNECTION CLASS
// ========================================

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ];

            // Add MySQL specific options only if PDO MySQL extension is loaded
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET;
            }

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Set charset using query if constant not available
            if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $this->connection->exec("SET NAMES " . DB_CHARSET);
            }

        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $errorMsg = 'Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql . ' | Params: ' . json_encode($params);
            $this->logError($errorMsg);

            // Show detailed error in debug mode
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw new Exception($errorMsg);
            }

            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('Insert failed: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Failed to insert data.');
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $params = array_merge($data, $whereParams);

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Update failed: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Failed to update data.');
        }
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Delete failed: ' . $e->getMessage() . ' | Table: ' . $table);
            throw new Exception('Failed to delete data.');
        }
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function count($table, $where = '1=1', $params = []) {
        // Add backticks if not already present
        if (strpos($table, '`') === false) {
            $table = "`{$table}`";
        }
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return (int) $result['total'];
    }

    private function logError($message) {
        if (LOG_ENABLED) {
            $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
        }

        if (DEBUG_MODE) {
            error_log($message);
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function untuk mendapatkan database instance
function db() {
    return Database::getInstance();
}
