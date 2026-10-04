<?php
class Pedido {
    private $conn;
    private $table_name = "pedidos";

    public $id;
    public $fecha_hora_pedido;
    public $fecha_hora_entrega;
    public $cliente;
    public $id_empleado;
    public $estado;
    public $tipo_pedido;
    public $observaciones;

    private const ESTADOS_PERMITIDOS = [
        'recibido',
        'pendiente',
        'preparacion',
        'entregado',
        'cancelado'
    ];

    private const TIPOS_PEDIDO_PERMITIDOS = [
        'local',
        'domicilio',
        'para_llevar'
    ];

    public static function getEstadosPermitidos() {
        return self::ESTADOS_PERMITIDOS;
    }

    public static function getTiposPedidoPermitidos() {
        return self::TIPOS_PEDIDO_PERMITIDOS;
    }

    public function __construct($db){
        $this->conn = $db;
    }

    private function normalizeString($value) {
        return is_null($value) ? null : trim((string) $value);
    }

    private function isValidEmpleadoId() {
        if (!isset($this->id_empleado) || !is_numeric($this->id_empleado) || (int)$this->id_empleado <= 0) {
            return false;
        }

        $query = "SELECT 1 FROM empleados WHERE id_empleado = :id_empleado LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_empleado', (int) $this->id_empleado, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log('Error validando id_empleado del pedido: ' . $e->getMessage());
            return false;
        }
    }

    private function isValidEstado() {
        if (!isset($this->estado) || !is_string($this->estado)) {
            return false;
        }

        $this->estado = strtolower($this->normalizeString($this->estado));
        return in_array($this->estado, self::ESTADOS_PERMITIDOS, true);
    }

    private function isValidTipoPedido() {
        if (!isset($this->tipo_pedido) || !is_string($this->tipo_pedido)) {
            return false;
        }

        $this->tipo_pedido = strtolower($this->normalizeString($this->tipo_pedido));
        return in_array($this->tipo_pedido, self::TIPOS_PEDIDO_PERMITIDOS, true);
    }

    public function create(){
        if (!$this->isValidEmpleadoId() || !$this->isValidEstado() || !$this->isValidTipoPedido()) {
            return false;
        }

        $this->cliente = isset($this->cliente) ? $this->normalizeString($this->cliente) : 'Cliente no especificado';
        if ($this->cliente === '') {
            $this->cliente = 'Cliente no especificado';
        }

        $this->observaciones = $this->observaciones !== null ? $this->normalizeString($this->observaciones) : null;

        $query = "INSERT INTO " . $this->table_name . "
                      (cliente, id_empleado, estado, tipo_pedido, observaciones)
                      VALUES 
                      (:cliente, :id_empleado, :estado, :tipo_pedido, :observaciones)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':cliente', $this->cliente);
        $stmt->bindValue(':id_empleado', (int) $this->id_empleado, PDO::PARAM_INT);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':tipo_pedido', $this->tipo_pedido);
        $stmt->bindParam(':observaciones', $this->observaciones);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error creando pedido: ' . $e->getMessage());
            return false;
        }
    }

    public function read() {
        $query = "SELECT id_pedido, fecha_hora_pedido, fecha_hora_entrega, cliente, estado, id_empleado, tipo_pedido, observaciones FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando pedidos: ' . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (!isset($this->id) || !is_numeric($this->id) || (int) $this->id <= 0) {
            return false;
        }

        if (!$this->isValidEmpleadoId() || !$this->isValidEstado() || !$this->isValidTipoPedido()) {
            return false;
        }

        $this->cliente = isset($this->cliente) ? $this->normalizeString($this->cliente) : 'Cliente no especificado';
        if ($this->cliente === '') {
            $this->cliente = 'Cliente no especificado';
        }

        $this->observaciones = $this->observaciones !== null ? $this->normalizeString($this->observaciones) : null;

        $query = "UPDATE " . $this->table_name . "
                  SET cliente = :cliente,
                      estado = :estado,
                      id_empleado = :id_empleado,
                      tipo_pedido = :tipo_pedido,
                      observaciones = :observaciones
                  WHERE id_pedido = :id_pedido";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':cliente', $this->cliente);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindValue(':id_empleado', (int) $this->id_empleado, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_pedido', $this->tipo_pedido);
        $stmt->bindParam(':observaciones', $this->observaciones);
        $stmt->bindValue(':id_pedido', (int) $this->id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error actualizando pedido: ' . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_pedido = :id_pedido";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id_pedido', (int) $this->id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error eliminando pedido: ' . $e->getMessage());
            return false;
        }
    }
}

