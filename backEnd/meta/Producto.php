<?php

class Producto {
    private $conn;
    private $table_name = "productos";

    // Propiedades  de la clase
    public $id_producto;
    public $codigo_interno;
    public $nombre;
    public $precio;
    public $id_categoria;
    public $url_imagen;
    public $disponibilidad;
    public $es_extra;
    public $fecha_creacion; 

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear un nuevo producto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET codigo_interno = :codigo_interno,
                      nombre = :nombre,
                      precio = :precio,
                      id_categoria = :id_categoria,
                      url_imagen = :url_imagen,
                      disponibilidad = :disponibilidad,
                      es_extra = :es_extra";

        $stmt = $this->conn->prepare($query);

        // Sanitizar entradas
        $this->codigo_interno = htmlspecialchars(strip_tags($this->codigo_interno));
        $this->nombre         = htmlspecialchars(strip_tags($this->nombre));
        $this->precio         = htmlspecialchars(strip_tags($this->precio));
        $this->id_categoria   = htmlspecialchars(strip_tags($this->id_categoria));
        $this->url_imagen     = htmlspecialchars(strip_tags($this->url_imagen));
        $this->disponibilidad = htmlspecialchars(strip_tags($this->disponibilidad));
        $this->es_extra       = htmlspecialchars(strip_tags($this->es_extra));

        // Vincular parámetros
        $stmt->bindParam(":codigo_interno", $this->codigo_interno);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":url_imagen", $this->url_imagen);
        $stmt->bindParam(":disponibilidad", $this->disponibilidad);
        $stmt->bindParam(":es_extra", $this->es_extra);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Leer todos los productos
 // backEnd/meta/Producto.php

    public function read() {
        $query = "SELECT p.id_producto, p.codigo_interno, p.nombre, p.precio,
                        p.id_categoria, c.nombre AS categoria_nombre,
                        p.url_imagen, p.disponibilidad, p.es_extra, p.fecha_creacion
                FROM " . $this->table_name . " p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                ORDER BY p.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Actualizar un producto existente
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET codigo_interno = :codigo_interno,
                      nombre = :nombre,
                      precio = :precio,
                      id_categoria = :id_categoria,
                      url_imagen = :url_imagen,
                      disponibilidad = :disponibilidad,
                      es_extra = :es_extra
                  WHERE id_producto = :id_producto";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->codigo_interno = htmlspecialchars(strip_tags($this->codigo_interno));
        $this->nombre         = htmlspecialchars(strip_tags($this->nombre));
        $this->precio         = htmlspecialchars(strip_tags($this->precio));
        $this->id_categoria   = htmlspecialchars(strip_tags($this->id_categoria));
        $this->url_imagen     = htmlspecialchars(strip_tags($this->url_imagen));
        $this->disponibilidad = htmlspecialchars(strip_tags($this->disponibilidad));
        $this->es_extra       = htmlspecialchars(strip_tags($this->es_extra));
        $this->id_producto    = htmlspecialchars(strip_tags($this->id_producto));

        // Vincular
        $stmt->bindParam(":codigo_interno", $this->codigo_interno);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":url_imagen", $this->url_imagen);
        $stmt->bindParam(":disponibilidad", $this->disponibilidad);
        $stmt->bindParam(":es_extra", $this->es_extra);
        $stmt->bindParam(":id_producto", $this->id_producto);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Eliminar un producto
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);

        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $stmt->bindParam(":id_producto", $this->id_producto);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

public function readOne($id) {
    $query = "SELECT p.id_producto, p.codigo_interno, p.nombre, p.precio,
                     p.id_categoria, c.nombre AS categoria_nombre,
                     p.url_imagen, p.disponibilidad, p.es_extra, p.fecha_creacion
              FROM " . $this->table_name . " p
              LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
              WHERE p.id_producto = :id
              LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC); // puede ser false si no existe
}

}
?>