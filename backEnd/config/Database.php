<?php

class Database {
    private $host = "localhost";
    private $db_name = "master_crunch_db";
    private $username = "master";
    private $password = "1234";
    public $conn;

    public function getConnection() {
    try {
        $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("set names utf8");
    } catch (PDOException $e) {
        // Lanzar una excepción personalizada o relanzar
        throw new Exception("Error de conexión: " . $e->getMessage());
    }
    return $this->conn;
}

}

