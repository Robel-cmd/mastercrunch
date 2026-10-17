<?php
class Rol {
    private $conn;
    private $table_name = "roles";

    public $id_rol;
    public $nombre;
    public $descripcion;
    public $nivel_acceso;

    /**
     * Niveles de acceso del sistema (menor número = mayor acceso).
     *
     * Regla por módulo que se aplicará cuando exista el login (Fase 2):
     *   - usuarios  -> mínimo NIVEL_ADMIN     (solo admin gestiona empleados/roles)
     *   - pedidos   -> mínimo NIVEL_EMPLEADO  (escritura; cocinero solo consulta)
     *   - metas     -> mínimo NIVEL_EMPLEADO  (admin y empleado)
     *   - cocina    -> todos (NIVEL_COCINERO incluido)
     *
     * A futuro, cuando existan las tablas de permisos (Opción C), este
     * escalonado se reemplaza por una consulta granular; la idea de niveles
     * se mantiene solo como "acceso por defecto".
     */
    const NIVEL_ADMIN    = 1; // acceso total al sistema
    const NIVEL_EMPLEADO = 2; // pedidos + consulta de cocina + metas
    const NIVEL_COCINERO = 3; // solo ordenes de cocina

    public function __construct($db) {
        $this->conn = $db;
    }

    private function normalizeString($value) {
        return is_null($value) ? null : trim((string) $value);
    }

    public function create() {
        $this->nombre = $this->normalizeString($this->nombre);
        $this->descripcion = $this->normalizeString($this->descripcion);
        $nivel = isset($this->nivel_acceso) ? (int)$this->nivel_acceso : self::NIVEL_COCINERO;

        if (empty($this->nombre) || $nivel <= 0) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  (nombre, descripcion, nivel_acceso)
                  VALUES (:nombre, :descripcion, :nivel_acceso)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindValue(':nivel_acceso', $nivel, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                $this->id_rol = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error creando rol: ' . $e->getMessage());
            return false;
        }
    }

    public function read() {
        $query = "SELECT id_rol, nombre, descripcion, nivel_acceso
                  FROM " . $this->table_name . "
                  ORDER BY nivel_acceso, id_rol";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Error consultando roles: ' . $e->getMessage());
            return false;
        }
    }

    public function readOne($id) {
        if (!is_numeric($id) || (int)$id <= 0) {
            return false;
        }

        $query = "SELECT id_rol, nombre, descripcion, nivel_acceso
                  FROM " . $this->table_name . "
                  WHERE id_rol = :id_rol
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_rol', (int)$id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row === false ? false : $row;
        } catch (PDOException $e) {
            error_log('Error consultando rol: ' . $e->getMessage());
            return false;
        }
    }

    public function update() {
        if (!isset($this->id_rol) || !is_numeric($this->id_rol) || (int)$this->id_rol <= 0) {
            return false;
        }

        $this->nombre = $this->normalizeString($this->nombre);
        $this->descripcion = $this->normalizeString($this->descripcion);
        $nivel = isset($this->nivel_acceso) ? (int)$this->nivel_acceso : self::NIVEL_COCINERO;

        if (empty($this->nombre) || $nivel <= 0) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      descripcion = :descripcion,
                      nivel_acceso = :nivel_acceso
                  WHERE id_rol = :id_rol";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindValue(':nivel_acceso', $nivel, PDO::PARAM_INT);
        $stmt->bindValue(':id_rol', (int)$this->id_rol, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error actualizando rol: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina el rol. Falla (devuelve false) si hay empleados usándolo,
     * porque la FOREIGN KEY lo impide: en ese caso conviene reasignarlos
     * a otro rol antes de borrar.
     */
    public function delete() {
        if (!isset($this->id_rol) || !is_numeric($this->id_rol) || (int)$this->id_rol <= 0) {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id_rol = :id_rol";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_rol', (int)$this->id_rol, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            // 1451 = FOREIGN KEY constraint: el rol todavía tiene empleados asignados
            if ((string)$e->getCode() === '1451') {
                return false;
            }
            error_log('Error eliminando rol: ' . $e->getMessage());
            return false;
        }
    }
}
?>
