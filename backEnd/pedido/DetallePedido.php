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

    /**
     * Valida la cantidad: debe ser un entero >= 1.
     * Acepta 5, "5", 5.0 y "05"; rechaza 0, -3, 2.5 y "abc".
     */
    public static function isValidCantidadValue($value) {
        return is_numeric($value)
            && (float)$value >= 1
            && floor((float)$value) === (float)$value;
    }

    /**
     * Obtiene el precio vigente desde la base de datos (fuente de verdad).
     * El precio NUNCA se toma del cliente: se lee de productos.precio o
     * combo.precio_total según corresponda.
     * Devuelve el precio o false si el producto/combo no existe.
     */
    public function resolverPrecioUnitario($idProducto = null, $idCombo = null) {
        if (!empty($idProducto) && $this->isValidId($idProducto)) {
            $query = "SELECT precio FROM productos WHERE id_producto = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$idProducto, PDO::PARAM_INT);
            $stmt->execute();
            $precio = $stmt->fetchColumn();
            return $precio === false ? false : (float)$precio;
        }

        if (!empty($idCombo) && $this->isValidId($idCombo)) {
            $query = "SELECT precio_total FROM combo WHERE id_combo = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$idCombo, PDO::PARAM_INT);
            $stmt->execute();
            $precio = $stmt->fetchColumn();
            return $precio === false ? false : (float)$precio;
        }

        return false;
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

        if (!self::isValidCantidadValue($this->cantidad)) {
            return false;
        }

        // El precio SIEMPRE lo determina el servidor; el valor enviado por el
        // cliente se ignora por completo (evita manipular montos).
        $precio = $this->resolverPrecioUnitario($this->id_producto, $this->id_combo);
        if ($precio === false) {
            return false;
        }
        $this->precio_unitario = $precio;

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

        if (!self::isValidCantidadValue($this->cantidad)) {
            return false;
        }

        // Igual que en create(): el precio lo fija el servidor.
        $precio = $this->resolverPrecioUnitario($this->id_producto, $this->id_combo);
        if ($precio === false) {
            return false;
        }
        $this->precio_unitario = $precio;

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

