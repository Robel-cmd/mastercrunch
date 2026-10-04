<?php

// ver si el archivo existe 
function cargarVariablesEntorno($ruta) {
    if (!file_exists($ruta)) {
        return false; 
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue; // Ignorar comentarios

        list($nombre, $valor) = explode('=', $linea, 2);
        $_ENV[trim($nombre)] = trim($valor);
    }
}

// Ejecutamos la lectura del archivo .env
cargarVariablesEntorno(__DIR__ . '../../.env');

class Database {
    // 2. Ahora las variables ya no tienen los datos fijos, leen el .env
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Al crear el objeto, cargamos los datos del .env (o valores por defecto si no existen)
        $this->host     = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name  = $_ENV['DB_NAME'] ?? 'master_crunch_db';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    public function getConnection() {
        try {
            // 3. Tu conexión PDO intacta y automatizada
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
        return $this->conn;
    }
}
