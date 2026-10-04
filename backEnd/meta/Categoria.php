<?php
class Categoria {
    private $conn;
    private $table_name = "categoria";

    public $id_categoria;
    public $nombre;
    public $descripcion;
    public $activo;
    public $imagen;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todas (activas por defecto, o todas si $incluirInactivas es true)
    public function read($incluirInactivas = false) {
        $query = "SELECT id_categoria, nombre, descripcion, activo, imagen
                  FROM " . $this->table_name;
        if (!$incluirInactivas) {
            $query .= " WHERE activo = 1";
        }
        $query .= " ORDER BY id_categoria DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener una categoría por ID
    public function readOne($id) {
        $query = "SELECT id_categoria, nombre, descripcion, activo, imagen
                  FROM " . $this->table_name . "
                  WHERE id_categoria = :id
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nombre = :nombre, descripcion = :descripcion,
                      activo = :activo, imagen = :imagen";
        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->activo = intval($this->activo);
        $this->imagen = htmlspecialchars(strip_tags($this->imagen));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":imagen", $this->imagen);

        return $stmt->execute();
    }

    // Actualizar
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre, descripcion = :descripcion,
                      activo = :activo, imagen = :imagen
                  WHERE id_categoria = :id_categoria";
        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->activo = intval($this->activo);
        $this->imagen = htmlspecialchars(strip_tags($this->imagen));
        $this->id_categoria = intval($this->id_categoria);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":imagen", $this->imagen);
        $stmt->bindParam(":id_categoria", $this->id_categoria);

        return $stmt->execute();
    }

    // Eliminar (físico)
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_categoria = :id_categoria";
        $stmt = $this->conn->prepare($query);
        $this->id_categoria = intval($this->id_categoria);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        return $stmt->execute();
    }
}
?>