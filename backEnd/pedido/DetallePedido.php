<?php
class DetallePedido {
    private $conn;
    private $table_name = "pedidos_detalle";

    public $id_detalle;
    public $id_pedido;
    public $id_producto;
    public $id_combo;
    public $cantidad;
    public $precio_unitario;

    public function __construct($db){
        $this->conn = $db;
    }

    private function isValidId($value) {
        return is_numeric($value) && (int)$value > 0;
    }

    private function existsPedido($idPedido) {
        $query = "SELECT 1 FROM pedidos WHERE id_pedido = :id_pedido LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    private function existsProducto($idProducto) {
        $query = "SELECT 1 FROM productos WHERE id_producto = :id_producto LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    private function existsCombo($idCombo) {
        $query = "SELECT 1 FROM combo WHERE id_combo = :id_combo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_combo', $idCombo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    public function create(){
        if (!$this->isValidId($this->id_pedido) || !$this->existsPedido($this->id_pedido)) {
            return false;
        }

        $hasProducto = !empty($this->id_producto) && $this->isValidId($this->id_producto);
        $hasCombo = !empty($this->id_combo) && $this->isValidId($this->id_combo);

        if ($hasProducto === $hasCombo) {
            return false;
        }

        if ($hasProducto && !$this->existsProducto($this->id_producto)) {
            return false;
        }

        if ($hasCombo && !$this->existsCombo($this->id_combo)) {
            return false;
        }

        if (!is_int((int) $this->cantidad) || (int)$this->cantidad <= 0) {
            return false;
        }

        if (!is_numeric($this->precio_unitario) || (float)$this->precio_unitario < 0) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                      (id_pedido, id_producto, id_combo, cantidad, precio_unitario)
                      VALUES 
                      (:id_pedido, :id_producto, :id_combo, :cantidad, :precio_unitario)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_pedido', $this->id_pedido, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $this->id_producto, $hasProducto ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':id_combo', $this->id_combo, $hasCombo ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error creando detalle pedido: ' . $e->getMessage());
            return false;
        }
    }

    public function read() {
        $query = "SELECT id_detalle, id_pedido, id_producto, id_combo, cantidad, precio_unitario FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando detalles: ' . $e->getMessage());
            return false;
        }
    }

    public function readOne($id_detalle) {
        $query = "SELECT id_detalle, id_pedido, id_producto, id_combo, cantidad, precio_unitario FROM " . $this->table_name . " WHERE id_detalle = :id_detalle";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_detalle', $id_detalle, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando detalle pedido: ' . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (!$this->isValidId($this->id_detalle)) {
            return false;
        }

        if (!$this->isValidId($this->id_pedido) || !$this->existsPedido($this->id_pedido)) {
            return false;
        }

        $hasProducto = !empty($this->id_producto) && $this->isValidId($this->id_producto);
        $hasCombo = !empty($this->id_combo) && $this->isValidId($this->id_combo);

        if ($hasProducto === $hasCombo) {
            return false;
        }

        if ($hasProducto && !$this->existsProducto($this->id_producto)) {
            return false;
        }

        if ($hasCombo && !$this->existsCombo($this->id_combo)) {
            return false;
        }

        if (!is_int((int) $this->cantidad) || (int)$this->cantidad <= 0) {
            return false;
        }

        if (!is_numeric($this->precio_unitario) || (float)$this->precio_unitario < 0) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET id_pedido = :id_pedido,
                      id_producto = :id_producto,
                      id_combo = :id_combo,
                      cantidad = :cantidad,
                      precio_unitario = :precio_unitario
                  WHERE id_detalle = :id_detalle";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_detalle', $this->id_detalle, PDO::PARAM_INT);
        $stmt->bindParam(':id_pedido', $this->id_pedido, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $this->id_producto, $hasProducto ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':id_combo', $this->id_combo, $hasCombo ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error actualizando detalle pedido: ' . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (!$this->isValidId($this->id_detalle)) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id_detalle = :id_detalle";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_detalle', $this->id_detalle, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error eliminando detalle pedido: ' . $e->getMessage());
            return false;
        }
    }
}

