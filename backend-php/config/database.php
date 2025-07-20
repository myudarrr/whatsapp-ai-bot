<?php
/**
 * Database Configuration Class
 * Mengelola koneksi ke database MySQL
 */
class Database {
    // Konfigurasi database
    private $host = 'localhost';
    private $db_name = 'whatsapp_bot';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $conn;

    /**
     * Membuat koneksi ke database
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }

    /**
     * Menutup koneksi database
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Memulai transaksi database
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaksi
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaksi
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Mendapatkan ID terakhir yang diinsert
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Test koneksi database
     * @return bool
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Database Helper Functions
 */
class DatabaseHelper {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Execute prepared statement dengan parameter
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    /**
     * Fetch single row
     * @param string $query
     * @param array $params
     * @return array|false
     */
    public function fetchOne($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch multiple rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data dan return ID
     * @param string $table
     * @param array $data
     * @return string
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($query, $data);
        
        return $this->conn->lastInsertId();
    }

    /**
     * Update data
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Count rows
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($table, $where = '1=1', $params = []) {
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($query, $params);
        return (int) $result['count'];
    }

    /**
     * Check if record exists
     * @param string $table
     * @param string $where
     * @param array $params
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
}
?>
