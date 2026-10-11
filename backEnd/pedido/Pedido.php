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

    /**
     * Lee un pedido por su ID.
     * Devuelve el array asociativo o false si no existe / hay error.
     */
    public function readOne($id) {
        if (!is_numeric($id) || (int)$id <= 0) {
            return false;
        }

        $query = "SELECT id_pedido, fecha_hora_pedido, fecha_hora_entrega, cliente, estado, id_empleado, tipo_pedido, observaciones
                  FROM " . $this->table_name . "
                  WHERE id_pedido = :id_pedido
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_pedido', (int)$id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row === false ? false : $row;
        } catch (PDOException $e) {
            error_log('Error consultando pedido: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Construye el WHERE común para los filtros de listado.
     * Filtros soportados: estado, tipo_pedido, cliente (parcial),
     * fecha_desde y fecha_hasta (sobre fecha_hora_pedido, inclusive).
     */
    private function buildFiltros($filtros) {
        $where = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = "estado = :estado";
            $params[':estado'] = strtolower(trim((string)$filtros['estado']));
        }
        if (!empty($filtros['tipo_pedido'])) {
            $where[] = "tipo_pedido = :tipo_pedido";
            $params[':tipo_pedido'] = strtolower(trim((string)$filtros['tipo_pedido']));
        }
        if (!empty($filtros['cliente'])) {
            $where[] = "cliente LIKE :cliente";
            $params[':cliente'] = '%' . trim((string)$filtros['cliente']) . '%';
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[] = "fecha_hora_pedido >= :fecha_desde";
            $params[':fecha_desde'] = trim((string)$filtros['fecha_desde']) . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "fecha_hora_pedido <= :fecha_hasta";
            $params[':fecha_hasta'] = trim((string)$filtros['fecha_hasta']) . ' 23:59:59';
        }

        return [
            'sql'    => $where ? ' WHERE ' . implode(' AND ', $where) : '',
            'params' => $params
        ];
    }

    /**
     * Listado paginado con filtros opcionales.
     * $filtros: estado, tipo_pedido, cliente, fecha_desde, fecha_hasta, limit, offset.
     * Devuelve el stmt o false.
     */
    public function readPaginated($filtros = []) {
        $limite = isset($filtros['limit']) ? (int)$filtros['limit'] : 20;
        $offset = isset($filtros['offset']) ? (int)$filtros['offset'] : 0;

        if ($limite <= 0) $limite = 20;
        if ($limite > 100) $limite = 100; // tope de seguridad
        if ($offset < 0) $offset = 0;

        $filtro = $this->buildFiltros($filtros);

        $query = "SELECT id_pedido, fecha_hora_pedido, fecha_hora_entrega, cliente, estado, id_empleado, tipo_pedido, observaciones
                  FROM " . $this->table_name . $filtro['sql'] . "
                  ORDER BY fecha_hora_pedido DESC, id_pedido DESC
                  LIMIT :limite OFFSET :offset";
        $stmt = $this->conn->prepare($query);

        foreach ($filtro['params'] as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando pedidos paginados: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuenta el total de pedidos que coinciden con los filtros.
     * Se usa en vez de rowCount() (poco fiable en SELECT con MySQL).
     */
    public function count($filtros = []) {
        $filtro = $this->buildFiltros($filtros);

        $query = "SELECT COUNT(*) FROM " . $this->table_name . $filtro['sql'];
        $stmt = $this->conn->prepare($query);

        foreach ($filtro['params'] as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }

        try {
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Error contando pedidos: ' . $e->getMessage());
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

