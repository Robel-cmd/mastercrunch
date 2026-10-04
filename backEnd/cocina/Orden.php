<?php
class Orden {
    private $conn;
    private $table_name = "ordenes_cocina";

    public $id_orden_cocina;
    public $id_pedido;
    public $cantidad;
    public $estado_cocina;
    public $hora_recibido;
    public $hora_entrega;

    private const ESTADOS_PERMITIDOS = [
        'pendiente',
        'preparacion',
        'entregado',
        'cancelado'
    ];

    public function __construct($db) {
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

    private function isValidEstado() {
        if (!isset($this->estado_cocina) || !is_string($this->estado_cocina)) {
            return false;
        }

        $this->estado_cocina = strtolower(trim($this->estado_cocina));
        return in_array($this->estado_cocina, self::ESTADOS_PERMITIDOS, true);
    }

    public function create() {
        if (!$this->isValidId($this->id_pedido) || !$this->existsPedido($this->id_pedido)) {
            return false;
        }

        if (!is_int((int)$this->cantidad) || (int)$this->cantidad <= 0) {
            return false;
        }

        if (!$this->isValidEstado()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  (id_pedido, cantidad, estado_cocina)
                  VALUES
                  (:id_pedido, :cantidad, :estado_cocina)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id_pedido', (int)$this->id_pedido, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', (int)$this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':estado_cocina', $this->estado_cocina);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error creando orden de cocina: ' . $e->getMessage());
            return false;
        }
    }

    public function read() {
        $query = "SELECT id_orden_cocina, id_pedido, cantidad, estado_cocina, hora_recibido, hora_entrega
                  FROM " . $this->table_name . "
                  ORDER BY id_orden_cocina DESC";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando órdenes de cocina: ' . $e->getMessage());
            return false;
        }
    }

    public function readOne() {
        $query = "SELECT id_orden_cocina, id_pedido, cantidad, estado_cocina, hora_recibido, hora_entrega
                  FROM " . $this->table_name . "
                  WHERE id_orden_cocina = :id_orden_cocina
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_orden_cocina', (int)$this->id_orden_cocina, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando orden de cocina: ' . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (!$this->isValidId($this->id_orden_cocina)) {
            return false;
        }

        if (!$this->isValidId($this->id_pedido) || !$this->existsPedido($this->id_pedido)) {
            return false;
        }

        if (!is_int((int)$this->cantidad) || (int)$this->cantidad <= 0) {
            return false;
        }

        if (!$this->isValidEstado()) {
            return false;
        }

        $this->hora_recibido = !empty($this->hora_recibido) ? $this->hora_recibido : date('Y-m-d H:i:s');
        $this->hora_entrega = !empty($this->hora_entrega) ? $this->hora_entrega : null;

        $query = "UPDATE " . $this->table_name . "
                  SET id_pedido = :id_pedido,
                      cantidad = :cantidad,
                      estado_cocina = :estado_cocina,
                      hora_recibido = :hora_recibido,
                      hora_entrega = :hora_entrega
                  WHERE id_orden_cocina = :id_orden_cocina";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id_orden_cocina', (int)$this->id_orden_cocina, PDO::PARAM_INT);
        $stmt->bindValue(':id_pedido', (int)$this->id_pedido, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', (int)$this->cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':estado_cocina', $this->estado_cocina);
        $stmt->bindParam(':hora_recibido', $this->hora_recibido);
        $stmt->bindParam(':hora_entrega', $this->hora_entrega);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error actualizando orden de cocina: ' . $e->getMessage());
            return false;
        }
    }

    public function entregar() {
        if (!$this->isValidId($this->id_orden_cocina)) {
            return false;
        }

        $this->estado_cocina = !empty($this->estado_cocina) ? strtolower(trim($this->estado_cocina)) : 'entregado';
        $this->hora_entrega = !empty($this->hora_entrega) ? $this->hora_entrega : date('Y-m-d H:i:s');

        $query = "UPDATE " . $this->table_name . "
                  SET estado_cocina = :estado_cocina,
                      hora_entrega = :hora_entrega
                  WHERE id_orden_cocina = :id_orden_cocina";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':estado_cocina', $this->estado_cocina);
        $stmt->bindParam(':hora_entrega', $this->hora_entrega);
        $stmt->bindValue(':id_orden_cocina', (int)$this->id_orden_cocina, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error entregando orden de cocina: ' . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (!$this->isValidId($this->id_orden_cocina)) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id_orden_cocina = :id_orden_cocina";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_orden_cocina', (int)$this->id_orden_cocina, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error eliminando orden de cocina: ' . $e->getMessage());
            return false;
        }
    }
}

