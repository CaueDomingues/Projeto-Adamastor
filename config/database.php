<?php

class Database {
    private $host = "localhost";       
    private $db_name = "database_adamastor"; 
    private $username = "root";        
    private $password = "";            
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            error_log("Erro de conexão MySQL: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}

?>
