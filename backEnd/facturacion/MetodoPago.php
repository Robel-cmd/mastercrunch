<?php
class MetodoPago {
    private $conn;
    private $table_name = "metodo_pago";

    public $id_metodo_pago;
    public $nombre;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Leer todos los métodos de pago activos
    public function read() {
        $query = "SELECT id_metodo_pago, nombre, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_metodo_pago";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un método de pago por ID
    public function readOne($id) {
        $query = "SELECT id_metodo_pago, nombre, activo
                  FROM " . $this->table_name . "
                  WHERE id_metodo_pago = :id
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>