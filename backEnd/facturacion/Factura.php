<?php
class Factura {
    private $conn;
    private $table_name = "facturas";
    private $detalle_table = "factura_detalle";

    public $id_factura;
    public $numero_factura;
    public $id_pedido;
    public $id_empleado;
    public $id_metodo_pago;
    public $subtotal;
    public $isv;
    public $total;
    public $fecha_hora_factura;
    public $estado_factura;
    public $rtn_cliente;
    public $observaciones;
    public $detalles = []; // cada detalle puede tener id_producto O id_combo

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear factura y sus detalles
    public function create() {
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table_name . "
                      SET numero_factura=:numero_factura, id_pedido=:id_pedido,
                          id_empleado=:id_empleado, id_metodo_pago=:id_metodo_pago,
                          subtotal=:subtotal, isv=:isv, total=:total,
                          estado_factura=:estado_factura, rtn_cliente=:rtn_cliente,
                          observaciones=:observaciones";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_factura', $this->numero_factura);
            $stmt->bindParam(':id_pedido', $this->id_pedido);
            $stmt->bindParam(':id_empleado', $this->id_empleado);
            $stmt->bindParam(':id_metodo_pago', $this->id_metodo_pago);
            $stmt->bindParam(':subtotal', $this->subtotal);
            $stmt->bindParam(':isv', $this->isv);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':estado_factura', $this->estado_factura);
            $stmt->bindParam(':rtn_cliente', $this->rtn_cliente);
            $stmt->bindParam(':observaciones', $this->observaciones);
            if (!$stmt->execute()) {
                throw new Exception('Error al crear factura');
            }
            $this->id_factura = $this->conn->lastInsertId();

            // Insertar detalles (producto o combo)
            if (!empty($this->detalles)) {
                $queryDet = "INSERT INTO " . $this->detalle_table . "
                             SET id_factura=:id_factura,
                                 id_producto=:id_producto,
                                 id_combo=:id_combo,
                                 cantidad=:cantidad, precio_unitario=:precio_unitario,
                                 descuento=:descuento, subtotal_linea=:subtotal_linea,
                                 isv_linea=:isv_linea, total_linea=:total_linea";
                $stmtDet = $this->conn->prepare($queryDet);
                foreach ($this->detalles as $det) {
                    $stmtDet->bindParam(':id_factura', $this->id_factura);
                    $stmtDet->bindParam(':id_producto', $det['id_producto']);
                    $stmtDet->bindParam(':id_combo', $det['id_combo']);
                    $stmtDet->bindParam(':cantidad', $det['cantidad']);
                    $stmtDet->bindParam(':precio_unitario', $det['precio_unitario']);
                    $stmtDet->bindParam(':descuento', $det['descuento']);
                    $stmtDet->bindParam(':subtotal_linea', $det['subtotal_linea']);
                    $stmtDet->bindParam(':isv_linea', $det['isv_linea']);
                    $stmtDet->bindParam(':total_linea', $det['total_linea']);
                    if (!$stmtDet->execute()) {
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

    // Leer todas las facturas con sus detalles (agrupadas)
    public function read() {
        $query = "SELECT f.id_factura, f.numero_factura, f.id_pedido, f.id_empleado,
                         f.id_metodo_pago, f.subtotal, f.isv, f.total,
                         f.fecha_hora_factura, f.estado_factura, f.rtn_cliente, f.observaciones,
                         fd.id_factura_detalle, fd.id_producto, fd.id_combo,
                         fd.cantidad, fd.precio_unitario,
                         fd.descuento, fd.subtotal_linea, fd.isv_linea, fd.total_linea,
                         COALESCE(p.nombre, c.nombre) AS item_nombre,
                         CASE WHEN fd.id_combo IS NOT NULL THEN 'combo' ELSE 'producto' END AS tipo
                  FROM " . $this->table_name . " f
                  LEFT JOIN " . $this->detalle_table . " fd ON f.id_factura = fd.id_factura
                  LEFT JOIN productos p ON fd.id_producto = p.id_producto
                  LEFT JOIN combo c ON fd.id_combo = c.id_combo
                  ORDER BY f.id_factura DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer una factura con sus detalles
    public function readOne($id) {
        $query = "SELECT f.*
                  FROM " . $this->table_name . " f
                  WHERE f.id_factura = :id
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $queryDet = "SELECT fd.*,
                            COALESCE(p.nombre, c.nombre) AS item_nombre,
                            CASE WHEN fd.id_combo IS NOT NULL THEN 'combo' ELSE 'producto' END AS tipo
                     FROM " . $this->detalle_table . " fd
                     LEFT JOIN productos p ON fd.id_producto = p.id_producto
                     LEFT JOIN combo c ON fd.id_combo = c.id_combo
                     WHERE fd.id_factura = :id_factura
                     ORDER BY fd.id_factura_detalle ASC";
        $stmtDet = $this->conn->prepare($queryDet);
        $stmtDet->bindParam(':id_factura', $id);
        $stmtDet->execute();
        $row['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $row;
    }

    // Actualizar factura y sus detalles (reemplaza los detalles)
    public function update() {
        $this->conn->beginTransaction();
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET numero_factura=:numero_factura, id_pedido=:id_pedido,
                          id_empleado=:id_empleado, id_metodo_pago=:id_metodo_pago,
                          subtotal=:subtotal, isv=:isv, total=:total,
                          estado_factura=:estado_factura, rtn_cliente=:rtn_cliente,
                          observaciones=:observaciones
                      WHERE id_factura=:id_factura";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':numero_factura', $this->numero_factura);
            $stmt->bindParam(':id_pedido', $this->id_pedido);
            $stmt->bindParam(':id_empleado', $this->id_empleado);
            $stmt->bindParam(':id_metodo_pago', $this->id_metodo_pago);
            $stmt->bindParam(':subtotal', $this->subtotal);
            $stmt->bindParam(':isv', $this->isv);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':estado_factura', $this->estado_factura);
            $stmt->bindParam(':rtn_cliente', $this->rtn_cliente);
            $stmt->bindParam(':observaciones', $this->observaciones);
            $stmt->bindParam(':id_factura', $this->id_factura);
            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar factura');
            }

            // Eliminar detalles antiguos
            $queryDel = "DELETE FROM " . $this->detalle_table . " WHERE id_factura = :id_factura";
            $stmtDel = $this->conn->prepare($queryDel);
            $stmtDel->bindParam(':id_factura', $this->id_factura);
            if (!$stmtDel->execute()) {
                throw new Exception('Error al eliminar detalles antiguos');
            }

            // Insertar nuevos detalles
            if (!empty($this->detalles)) {
                $queryDet = "INSERT INTO " . $this->detalle_table . "
                             SET id_factura=:id_factura,
                                 id_producto=:id_producto,
                                 id_combo=:id_combo,
                                 cantidad=:cantidad, precio_unitario=:precio_unitario,
                                 descuento=:descuento, subtotal_linea=:subtotal_linea,
                                 isv_linea=:isv_linea, total_linea=:total_linea";
                $stmtDet = $this->conn->prepare($queryDet);
                foreach ($this->detalles as $det) {
                    $stmtDet->bindParam(':id_factura', $this->id_factura);
                    $stmtDet->bindParam(':id_producto', $det['id_producto']);
                    $stmtDet->bindParam(':id_combo', $det['id_combo']);
                    $stmtDet->bindParam(':cantidad', $det['cantidad']);
                    $stmtDet->bindParam(':precio_unitario', $det['precio_unitario']);
                    $stmtDet->bindParam(':descuento', $det['descuento']);
                    $stmtDet->bindParam(':subtotal_linea', $det['subtotal_linea']);
                    $stmtDet->bindParam(':isv_linea', $det['isv_linea']);
                    $stmtDet->bindParam(':total_linea', $det['total_linea']);
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

    // Eliminar factura (los detalles se eliminan en cascada por FK)
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_factura = :id_factura";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_factura', $this->id_factura);
        return $stmt->execute();
    }
}
?>