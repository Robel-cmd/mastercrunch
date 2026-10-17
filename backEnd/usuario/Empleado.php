<?php
class Empleado {
    private $conn;
    private $table_name = "empleados";

    public $id_empleado;
    public $nombre;
    public $apellido;
    public $numero_telefono;
    public $usuario;
    public $contrasena;       // Solo en memoria: se hashea antes de guardar y NUNCA se devuelve
    public $id_rol;
    public $fecha_contratacion;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function normalizeString($value) {
        return is_null($value) ? null : trim((string) $value);
    }

    private function isValidId($value) {
        return is_numeric($value) && (int)$value > 0;
    }

    /** El usuario de acceso: letras, números, punto, guion bajo o guion (3 a 50). */
    public static function isValidUsuario($usuario) {
        return is_string($usuario) && preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario) === 1;
    }

    /** La contraseña en claro debe venir con al menos 6 caracteres. */
    public static function isValidContrasena($contrasena) {
        return is_string($contrasena) && strlen($contrasena) >= 6;
    }

    public function existsRol($idRol) {
        if (!$this->isValidId($idRol)) {
            return false;
        }
        $query = "SELECT 1 FROM roles WHERE id_rol = :id_rol LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_rol', (int)$idRol, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    /** ¿Ya existe un empleado con ese usuario? ($excluirId para ignorar al propio registro en ediciones). */
    public function existsUsuario($usuario, $excluirId = null) {
        $query = "SELECT id_empleado FROM " . $this->table_name . " WHERE usuario = :usuario";
        $params = [':usuario' => $usuario];
        if ($this->isValidId($excluirId)) {
            $query .= " AND id_empleado <> :excluir";
            $params[':excluir'] = (int)$excluirId;
        }
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }
        $stmt->execute();
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Crea el empleado. La contraseña se recibe en claro ($this->contrasena)
     * y se guarda SOLO como hash bcrypt; nunca se persiste ni se expone.
     */
    public function create() {
        $this->nombre = $this->normalizeString($this->nombre);
        $this->apellido = $this->normalizeString($this->apellido);
        $this->numero_telefono = $this->normalizeString($this->numero_telefono);
        $this->usuario = $this->normalizeString($this->usuario);

        if (empty($this->nombre) || empty($this->apellido) || empty($this->usuario)) {
            return false;
        }
        if (!self::isValidUsuario($this->usuario)) {
            return false;
        }
        if (!self::isValidContrasena($this->contrasena)) {
            return false;
        }
        if (!$this->existsRol($this->id_rol)) {
            return false;
        }
        if ($this->existsUsuario($this->usuario)) {
            return false;
        }

        $hash = password_hash($this->contrasena, PASSWORD_DEFAULT);
        $fecha = $this->normalizeString($this->fecha_contratacion);
        if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        $activo = isset($this->activo) ? (int)$this->activo : 1;

        $query = "INSERT INTO " . $this->table_name . "
                  (nombre, apellido, numero_telefono, usuario, contrasena_hash, id_rol, fecha_contratacion, activo)
                  VALUES
                  (:nombre, :apellido, :numero_telefono, :usuario, :contrasena_hash, :id_rol, :fecha_contratacion, :activo)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':numero_telefono', $this->numero_telefono);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindValue(':contrasena_hash', $hash);
        $stmt->bindValue(':id_rol', (int)$this->id_rol, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_contratacion', $fecha);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                $this->id_empleado = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            // 23000 = duplicado (usuario UNIQUE)
            error_log('Error creando empleado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Listado de empleados (sin el hash). Incluye los datos del rol.
     * $incluirInactivos = true muestra también los dados de baja.
     */
    public function read($incluirInactivos = false) {
        $where = $incluirInactivos ? "" : " WHERE e.activo = 1";
        $query = "SELECT e.id_empleado, e.nombre, e.apellido, e.numero_telefono, e.usuario,
                         e.id_rol, r.nombre AS rol_nombre, r.nivel_acceso AS rol_nivel,
                         e.fecha_contratacion, e.activo, e.ultimo_acceso
                  FROM " . $this->table_name . " e
                  LEFT JOIN roles r ON e.id_rol = r.id_rol" . $where . "
                  ORDER BY e.apellido, e.nombre";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando empleados: ' . $e->getMessage());
            return false;
        }
    }

    /** Empleado por ID (sin el hash). Devuelve el array o false. */
    public function readOne($id) {
        if (!$this->isValidId($id)) {
            return false;
        }

        $query = "SELECT e.id_empleado, e.nombre, e.apellido, e.numero_telefono, e.usuario,
                         e.id_rol, r.nombre AS rol_nombre, r.nivel_acceso AS rol_nivel,
                         e.fecha_contratacion, e.activo, e.ultimo_acceso
                  FROM " . $this->table_name . " e
                  LEFT JOIN roles r ON e.id_rol = r.id_rol
                  WHERE e.id_empleado = :id_empleado
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_empleado', (int)$id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row === false ? false : $row;
        } catch (PDOException $e) {
            error_log('Error consultando empleado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el empleado.
     * La contraseña solo se cambia si se envía una nueva ($this->contrasena no vacío);
     * si no viene, se conserva el hash actual.
     * El usuario no puede repetirse con otro empleado.
     */
    public function update() {
        if (!$this->isValidId($this->id_empleado)) {
            return false;
        }

        $actual = $this->readOne($this->id_empleado);
        if ($actual === false) {
            return false;
        }

        $this->nombre = $this->normalizeString($this->nombre);
        $this->apellido = $this->normalizeString($this->apellido);
        $this->numero_telefono = $this->normalizeString($this->numero_telefono);
        $this->usuario = $this->normalizeString($this->usuario);

        if (empty($this->nombre) || empty($this->apellido) || empty($this->usuario)) {
            return false;
        }
        if (!self::isValidUsuario($this->usuario)) {
            return false;
        }
        if (!$this->existsRol($this->id_rol)) {
            return false;
        }
        if ($this->existsUsuario($this->usuario, $this->id_empleado)) {
            return false;
        }

        $cambiarContrasena = !empty($this->contrasena);
        if ($cambiarContrasena && !self::isValidContrasena($this->contrasena)) {
            return false;
        }

        if ($cambiarContrasena) {
            $hash = password_hash($this->contrasena, PASSWORD_DEFAULT);
            $query = "UPDATE " . $this->table_name . "
                      SET nombre = :nombre, apellido = :apellido, numero_telefono = :numero_telefono,
                          usuario = :usuario, contrasena_hash = :contrasena_hash,
                          id_rol = :id_rol, activo = :activo
                      WHERE id_empleado = :id_empleado";
        } else {
            $query = "UPDATE " . $this->table_name . "
                      SET nombre = :nombre, apellido = :apellido, numero_telefono = :numero_telefono,
                          usuario = :usuario, id_rol = :id_rol, activo = :activo
                      WHERE id_empleado = :id_empleado";
        }

        $stmt = $this->conn->prepare($query);

        $activo = isset($this->activo) ? (int)$this->activo : (int)$actual['activo'];

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':numero_telefono', $this->numero_telefono);
        $stmt->bindParam(':usuario', $this->usuario);
        if ($cambiarContrasena) {
            $stmt->bindValue(':contrasena_hash', $hash);
        }
        $stmt->bindValue(':id_rol', (int)$this->id_rol, PDO::PARAM_INT);
        $stmt->bindValue(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindValue(':id_empleado', (int)$this->id_empleado, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error actualizando empleado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Borrado LÓGICO: no se borra el registro (los pedidos históricos lo
     * referencian), solo se marca inactivo. Devuelve true si ya estaba inactivo.
     */
    public function delete() {
        if (!$this->isValidId($this->id_empleado)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_empleado = :id_empleado";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_empleado', (int)$this->id_empleado, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error desactivando empleado: ' . $e->getMessage());
            return false;
        }
    }
}
?>
