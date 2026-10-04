<?php
class Combo {
    private $conn;
    private $table_name = "combo";

    public $id_combo;
    public $nombre;
    public $descripcion;
    public $precio_total;
    public $url_imagen_combo;
    public $activo;
    public $detalles = []; // array de objetos con id_producto, cantidad, precio_individual

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear combo y sus detalles
    public function create() {
        $this->conn->beginTransaction();
        try {
            // Insertar combo
            $query = "INSERT INTO " . $this->table_name . "
                      SET nombre=:nombre, descripcion=:descripcion,
                          precio_total=:precio_total, url_imagen_combo=:url_imagen_combo,
                          activo=:activo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':precio_total', $this->precio_total);
            $stmt->bindParam(':url_imagen_combo', $this->url_imagen_combo);
            $stmt->bindParam(':activo', $this->activo);
            if (!$stmt->execute()) {
                throw new Exception('Error al crear combo');
            }
            $this->id_combo = $this->conn->lastInsertId();

            // Insertar detalles
            if (!empty($this->detalles)) {
                $queryDetalle = "INSERT INTO combo_detalle (id_combo, id_producto, cantidad, precio_individual)
                                 VALUES (:id_combo, :id_producto, :cantidad, :precio_individual)";
                $stmtDetalle = $this->conn->prepare($queryDetalle);
                foreach ($this->detalles as $det) {
                    $stmtDetalle->bindParam(':id_combo', $this->id_combo);
                    $stmtDetalle->bindParam(':id_producto', $det['id_producto']);
                    $stmtDetalle->bindParam(':cantidad', $det['cantidad']);
                    $stmtDetalle->bindParam(':precio_individual', $det['precio_individual']);
                    if (!$stmtDetalle->execute()) {
                        throw new Exception('Error al insertar detalle');
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Leer todos los combos con sus detalles (opcional)
    public function read() {
        $query = "SELECT c.id_combo, c.nombre, c.descripcion, c.precio_total,
                         c.url_imagen_combo, c.activo
                  FROM " . $this->table_name . " c
                  ORDER BY c.id_combo DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un combo con sus detalles
    public function readOne($id) {
        $query = "SELECT c.id_combo, c.nombre, c.descripcion, c.precio_total,
                         c.url_imagen_combo, c.activo
                  FROM " . $this->table_name . " c
                  WHERE c.id_combo = :id
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Obtener detalles
        $queryDet = "SELECT id_producto, cantidad, precio_individual
                     FROM combo_detalle
                     WHERE id_combo = :id_combo";
        $stmtDet = $this->conn->prepare($queryDet);
        $stmtDet->bindParam(':id_combo', $id);
        $stmtDet->execute();
        $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        $row['detalles'] = $detalles;

        return $row;
    }

    // Actualizar combo y sus detalles (reemplazar detalles)
    public function update() {
        $this->conn->beginTransaction();
        try {
            // Actualizar combo
            $query = "UPDATE " . $this->table_name . "
                      SET nombre=:nombre, descripcion=:descripcion,
                          precio_total=:precio_total, url_imagen_combo=:url_imagen_combo,
                          activo=:activo
                      WHERE id_combo=:id_combo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':descripcion', $this->descripcion);
            $stmt->bindParam(':precio_total', $this->precio_total);
            $stmt->bindParam(':url_imagen_combo', $this->url_imagen_combo);
            $stmt->bindParam(':activo', $this->activo);
            $stmt->bindParam(':id_combo', $this->id_combo);
            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar combo');
            }

            // Eliminar detalles antiguos
            $queryDel = "DELETE FROM combo_detalle WHERE id_combo = :id_combo";
            $stmtDel = $this->conn->prepare($queryDel);
            $stmtDel->bindParam(':id_combo', $this->id_combo);
            if (!$stmtDel->execute()) {
                throw new Exception('Error al eliminar detalles antiguos');
            }

            // Insertar nuevos detalles
            if (!empty($this->detalles)) {
                $queryDet = "INSERT INTO combo_detalle (id_combo, id_producto, cantidad, precio_individual)
                             VALUES (:id_combo, :id_producto, :cantidad, :precio_individual)";
                $stmtDet = $this->conn->prepare($queryDet);
                foreach ($this->detalles as $det) {
                    $stmtDet->bindParam(':id_combo', $this->id_combo);
                    $stmtDet->bindParam(':id_producto', $det['id_producto']);
                    $stmtDet->bindParam(':cantidad', $det['cantidad']);
                    $stmtDet->bindParam(':precio_individual', $det['precio_individual']);
                    if (!$stmtDet->execute()) {
                        throw new Exception('Error al insertar detalle nuevo');
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Eliminar combo (los detalles se eliminan en cascada)
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_combo = :id_combo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_combo', $this->id_combo);
        return $stmt->execute();
    }
}
?>