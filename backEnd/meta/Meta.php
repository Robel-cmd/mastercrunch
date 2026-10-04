<?php
class Meta {
    private $conn;
    private $table_name = "metas_diarias";

    public $id_meta;
    public $id_producto;
    public $fecha;
    public $cantidad_meta;
    public $ventas_reales;
    public $porc_comparacion;
    public $estadistica;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Calcula el porcentaje de comparación y la estadística
     * a partir de cantidad_meta y ventas_reales.
     */
    private function calcularPorcentajeYEstadistica() {
        $meta = (float)$this->cantidad_meta;
        $ventas = (float)$this->ventas_reales;

        // Evitar división por cero
        if ($meta <= 0) {
            $this->porc_comparacion = 0.00;
            $this->estadistica = "Sin ventas/Nulo";
            return;
        }

        $porcentaje = round(($ventas / $meta) * 100, 2);
        $this->porc_comparacion = $porcentaje;

        if ($porcentaje > 100) {
            $this->estadistica = "Extraordinario";
        } elseif ($porcentaje == 100) {
            $this->estadistica = "Excelente";
        } elseif ($porcentaje >= 70) {
            $this->estadistica = "Muy bueno";
        } elseif ($porcentaje >= 50) {
            $this->estadistica = "Aceptable";
        } elseif ($porcentaje >= 30) {
            $this->estadistica = "Insuficiente";
        } elseif ($porcentaje >= 15) {
            $this->estadistica = "Deficiente";
        } elseif ($porcentaje > 0) {
            $this->estadistica = "Critico";
        } else {
            $this->estadistica = "Sin ventas/Nulo";
        }
    }

    /**
     * Subconsulta reutilizable: ventas reales desde pedidos entregados.
     * Incluye:
     *   1) Ventas directas de productos (pd.id_producto IS NOT NULL)
     *   2) Ventas de productos a través de combos (pd.id_combo IS NOT NULL),
     *      multiplicando la cantidad del combo por la cantidad del componente
     *      en combo_detalle.
     * Solo cuenta pedidos con estado = 'entregado'.
     * Se usa en todas las lecturas para calcular ventas_reales en tiempo real.
     */
    private function getSubqueryVentas() {
        return "(SELECT ventas.id_producto,
                        ventas.fecha_venta,
                        SUM(ventas.total_vendido) AS total_vendido
                 FROM (
                     -- 1) Ventas directas de productos
                     SELECT pd.id_producto,
                            DATE(pe.fecha_hora_pedido) AS fecha_venta,
                            pd.cantidad AS total_vendido
                     FROM pedidos_detalle pd
                     INNER JOIN pedidos pe ON pd.id_pedido = pe.id_pedido
                     WHERE pe.estado = 'entregado'
                       AND pd.id_producto IS NOT NULL

                     UNION ALL

                     -- 2) Ventas de productos vía combos 
                     SELECT cd.id_producto,
                            DATE(pe.fecha_hora_pedido) AS fecha_venta,
                            cd.cantidad * pd.cantidad AS total_vendido
                     FROM pedidos_detalle pd
                     INNER JOIN pedidos pe ON pd.id_pedido = pe.id_pedido
                     INNER JOIN combo_detalle cd ON pd.id_combo = cd.id_combo
                     WHERE pe.estado = 'entregado'
                       AND pd.id_combo IS NOT NULL
                 ) AS ventas
                 GROUP BY ventas.id_producto, ventas.fecha_venta) v";
    }

    // Crear o actualizar meta (si ya existe para producto y fecha, actualiza)
    public function createOrUpdate() {
        $query = "SELECT id_meta FROM " . $this->table_name . " WHERE id_producto = :id_producto AND fecha = :fecha";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_meta = $row['id_meta'];
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public function create() {
        $this->calcularPorcentajeYEstadistica();

        $query = "INSERT INTO " . $this->table_name . " SET id_producto=:id_producto, fecha=:fecha, cantidad_meta=:cantidad_meta, ventas_reales=:ventas_reales, porc_comparacion=:porc_comparacion, estadistica=:estadistica";
        $stmt = $this->conn->prepare($query);
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $this->fecha = htmlspecialchars(strip_tags($this->fecha));
        $this->cantidad_meta = htmlspecialchars(strip_tags($this->cantidad_meta));
        $this->ventas_reales = htmlspecialchars(strip_tags($this->ventas_reales));
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':cantidad_meta', $this->cantidad_meta);
        $stmt->bindParam(':ventas_reales', $this->ventas_reales);
        $stmt->bindParam(':porc_comparacion', $this->porc_comparacion);
        $stmt->bindParam(':estadistica', $this->estadistica);
        if ($stmt->execute()) {
            $this->id_meta = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $this->calcularPorcentajeYEstadistica();

        $query = "UPDATE " . $this->table_name . " SET id_producto=:id_producto, fecha=:fecha, cantidad_meta=:cantidad_meta, ventas_reales=:ventas_reales, porc_comparacion=:porc_comparacion, estadistica=:estadistica WHERE id_meta=:id_meta";
        $stmt = $this->conn->prepare($query);
        $this->id_meta = htmlspecialchars(strip_tags($this->id_meta));
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $this->fecha = htmlspecialchars(strip_tags($this->fecha));
        $this->cantidad_meta = htmlspecialchars(strip_tags($this->cantidad_meta));
        $this->ventas_reales = htmlspecialchars(strip_tags($this->ventas_reales));
        $stmt->bindParam(':id_meta', $this->id_meta);
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':cantidad_meta', $this->cantidad_meta);
        $stmt->bindParam(':ventas_reales', $this->ventas_reales);
        $stmt->bindParam(':porc_comparacion', $this->porc_comparacion);
        $stmt->bindParam(':estadistica', $this->estadistica);
        return $stmt->execute();
    }

    /**
     * Crear o actualizar metas de forma masiva (batch) en una sola transacción.
     */
    public function createOrUpdateBatch($metas) {
        $this->conn->beginTransaction();
        $creadas = 0;
        $actualizadas = 0;
        $errores = [];

        try {
            $queryCheck = "SELECT id_meta FROM " . $this->table_name . " WHERE id_producto = :id_producto AND fecha = :fecha";
            $stmtCheck = $this->conn->prepare($queryCheck);

            $queryInsert = "INSERT INTO " . $this->table_name . " SET id_producto=:id_producto, fecha=:fecha, cantidad_meta=:cantidad_meta, ventas_reales=:ventas_reales, porc_comparacion=:porc_comparacion, estadistica=:estadistica";
            $stmtInsert = $this->conn->prepare($queryInsert);

            $queryUpdate = "UPDATE " . $this->table_name . " SET cantidad_meta=:cantidad_meta, ventas_reales=:ventas_reales, porc_comparacion=:porc_comparacion, estadistica=:estadistica WHERE id_meta=:id_meta";
            $stmtUpdate = $this->conn->prepare($queryUpdate);

            foreach ($metas as $m) {
                $id_producto   = $m['id_producto'] ?? null;
                $fecha         = $m['fecha'] ?? null;
                $cantidad_meta = $m['cantidad_meta'] ?? 0;
                $ventas_reales = $m['ventas_reales'] ?? 0;

                if (empty($id_producto) || empty($fecha)) {
                    $errores[] = "Registro inválido: falta id_producto o fecha.";
                    continue;
                }

                $this->cantidad_meta = $cantidad_meta;
                $this->ventas_reales = $ventas_reales;
                $this->calcularPorcentajeYEstadistica();
                $porc = $this->porc_comparacion;
                $est  = $this->estadistica;

                $stmtCheck->bindParam(':id_producto', $id_producto);
                $stmtCheck->bindParam(':fecha', $fecha);
                $stmtCheck->execute();

                if ($stmtCheck->rowCount() > 0) {
                    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    $id_meta = $row['id_meta'];
                    $stmtUpdate->bindParam(':cantidad_meta', $cantidad_meta);
                    $stmtUpdate->bindParam(':ventas_reales', $ventas_reales);
                    $stmtUpdate->bindParam(':porc_comparacion', $porc);
                    $stmtUpdate->bindParam(':estadistica', $est);
                    $stmtUpdate->bindParam(':id_meta', $id_meta);
                    if ($stmtUpdate->execute()) {
                        $actualizadas++;
                    } else {
                        $errores[] = "Error al actualizar id_producto=$id_producto fecha=$fecha";
                    }
                } else {
                    $stmtInsert->bindParam(':id_producto', $id_producto);
                    $stmtInsert->bindParam(':fecha', $fecha);
                    $stmtInsert->bindParam(':cantidad_meta', $cantidad_meta);
                    $stmtInsert->bindParam(':ventas_reales', $ventas_reales);
                    $stmtInsert->bindParam(':porc_comparacion', $porc);
                    $stmtInsert->bindParam(':estadistica', $est);
                    if ($stmtInsert->execute()) {
                        $creadas++;
                    } else {
                        $errores[] = "Error al insertar id_producto=$id_producto fecha=$fecha";
                    }
                }
            }

            $this->conn->commit();
            return [
                "creadas" => $creadas,
                "actualizadas" => $actualizadas,
                "errores" => $errores
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                "creadas" => 0,
                "actualizadas" => 0,
                "errores" => ["Excepción: " . $e->getMessage()]
            ];
        }
    }

    /**
     * Lee todas las metas. Calcula ventas_reales, porc_comparacion y estadistica en tiempo real.
     */
    public function read() {
        $subquery = $this->getSubqueryVentas();
        $query = "SELECT m.id_meta, m.id_producto, p.nombre as producto_nombre, m.fecha, m.cantidad_meta,
                         COALESCE(v.total_vendido, 0) AS ventas_reales,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 0.00
                             ELSE ROUND((COALESCE(v.total_vendido, 0) / m.cantidad_meta) * 100, 2)
                         END AS porc_comparacion,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 'Sin ventas/Nulo'
                             WHEN COALESCE(v.total_vendido, 0) = 0 THEN 'Sin ventas/Nulo'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 > 100 THEN 'Extraordinario'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 = 100 THEN 'Excelente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 70 THEN 'Muy bueno'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 50 THEN 'Aceptable'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 30 THEN 'Insuficiente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 15 THEN 'Deficiente'
                             ELSE 'Critico'
                         END AS estadistica
                  FROM " . $this->table_name . " m
                  LEFT JOIN productos p ON m.id_producto = p.id_producto
                  LEFT JOIN $subquery ON v.id_producto = m.id_producto AND v.fecha_venta = m.fecha
                  ORDER BY m.fecha DESC, m.id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lee todas las metas de una fecha específica, con ventas_reales calculadas en tiempo real.
     */
    public function readByDate() {
        $subquery = $this->getSubqueryVentas();
        $query = "SELECT m.id_meta, m.id_producto, p.nombre as producto_nombre, m.fecha, m.cantidad_meta,
                         COALESCE(v.total_vendido, 0) AS ventas_reales,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 0.00
                             ELSE ROUND((COALESCE(v.total_vendido, 0) / m.cantidad_meta) * 100, 2)
                         END AS porc_comparacion,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 'Sin ventas/Nulo'
                             WHEN COALESCE(v.total_vendido, 0) = 0 THEN 'Sin ventas/Nulo'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 > 100 THEN 'Extraordinario'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 = 100 THEN 'Excelente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 70 THEN 'Muy bueno'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 50 THEN 'Aceptable'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 30 THEN 'Insuficiente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 15 THEN 'Deficiente'
                             ELSE 'Critico'
                         END AS estadistica
                  FROM " . $this->table_name . " m
                  LEFT JOIN productos p ON m.id_producto = p.id_producto
                  LEFT JOIN $subquery ON v.id_producto = m.id_producto AND v.fecha_venta = m.fecha
                  WHERE m.fecha = :fecha
                  ORDER BY m.id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lee una meta por su ID, con ventas_reales calculada en tiempo real.
     */
    public function readOne() {
        $subquery = $this->getSubqueryVentas();
        $query = "SELECT m.id_meta, m.id_producto, p.nombre as producto_nombre, m.fecha, m.cantidad_meta,
                         COALESCE(v.total_vendido, 0) AS ventas_reales,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 0.00
                             ELSE ROUND((COALESCE(v.total_vendido, 0) / m.cantidad_meta) * 100, 2)
                         END AS porc_comparacion,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 'Sin ventas/Nulo'
                             WHEN COALESCE(v.total_vendido, 0) = 0 THEN 'Sin ventas/Nulo'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 > 100 THEN 'Extraordinario'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 = 100 THEN 'Excelente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 70 THEN 'Muy bueno'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 50 THEN 'Aceptable'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 30 THEN 'Insuficiente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 15 THEN 'Deficiente'
                             ELSE 'Critico'
                         END AS estadistica
                  FROM " . $this->table_name . " m
                  LEFT JOIN productos p ON m.id_producto = p.id_producto
                  LEFT JOIN $subquery ON v.id_producto = m.id_producto AND v.fecha_venta = m.fecha
                  WHERE m.id_meta = :id_meta
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_meta', $this->id_meta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lee la meta de un producto en una fecha, con ventas_reales calculada en tiempo real.
     */
    public function readByProductAndDate() {
        $subquery = $this->getSubqueryVentas();
        $query = "SELECT m.id_meta, m.id_producto, p.nombre as producto_nombre, m.fecha, m.cantidad_meta,
                         COALESCE(v.total_vendido, 0) AS ventas_reales,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 0.00
                             ELSE ROUND((COALESCE(v.total_vendido, 0) / m.cantidad_meta) * 100, 2)
                         END AS porc_comparacion,
                         CASE
                             WHEN m.cantidad_meta <= 0 THEN 'Sin ventas/Nulo'
                             WHEN COALESCE(v.total_vendido, 0) = 0 THEN 'Sin ventas/Nulo'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 > 100 THEN 'Extraordinario'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 = 100 THEN 'Excelente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 70 THEN 'Muy bueno'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 50 THEN 'Aceptable'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 30 THEN 'Insuficiente'
                             WHEN (v.total_vendido / m.cantidad_meta) * 100 >= 15 THEN 'Deficiente'
                             ELSE 'Critico'
                         END AS estadistica
                  FROM " . $this->table_name . " m
                  LEFT JOIN productos p ON m.id_producto = p.id_producto
                  LEFT JOIN $subquery ON v.id_producto = m.id_producto AND v.fecha_venta = m.fecha
                  WHERE m.id_producto = :id_producto AND m.fecha = :fecha
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar en lote todas las metas de una fecha.
     */
    public function updateByDate($metas) {
        $this->conn->beginTransaction();
        $actualizadas = 0;
        $noEncontradas = 0;
        $errores = [];

        try {
            $queryCheck = "SELECT id_meta FROM " . $this->table_name . " WHERE id_producto = :id_producto AND fecha = :fecha";
            $stmtCheck = $this->conn->prepare($queryCheck);

            $queryUpdate = "UPDATE " . $this->table_name . " SET cantidad_meta=:cantidad_meta, ventas_reales=:ventas_reales, porc_comparacion=:porc_comparacion, estadistica=:estadistica WHERE id_meta=:id_meta";
            $stmtUpdate = $this->conn->prepare($queryUpdate);

            foreach ($metas as $m) {
                $id_producto   = $m['id_producto'] ?? null;
                $cantidad_meta = $m['cantidad_meta'] ?? null;
                $ventas_reales = $m['ventas_reales'] ?? null;

                if (empty($id_producto)) {
                    $errores[] = "Registro inválido: falta id_producto.";
                    continue;
                }

                $this->cantidad_meta = $cantidad_meta;
                $this->ventas_reales = $ventas_reales;
                $this->calcularPorcentajeYEstadistica();
                $porc = $this->porc_comparacion;
                $est  = $this->estadistica;

                $stmtCheck->bindParam(':id_producto', $id_producto);
                $stmtCheck->bindParam(':fecha', $this->fecha);
                $stmtCheck->execute();

                if ($stmtCheck->rowCount() > 0) {
                    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    $id_meta = $row['id_meta'];
                    $stmtUpdate->bindParam(':cantidad_meta', $cantidad_meta);
                    $stmtUpdate->bindParam(':ventas_reales', $ventas_reales);
                    $stmtUpdate->bindParam(':porc_comparacion', $porc);
                    $stmtUpdate->bindParam(':estadistica', $est);
                    $stmtUpdate->bindParam(':id_meta', $id_meta);
                    if ($stmtUpdate->execute()) {
                        $actualizadas++;
                    } else {
                        $errores[] = "Error al actualizar id_producto=$id_producto";
                    }
                } else {
                    $noEncontradas++;
                }
            }

            $this->conn->commit();
            return [
                "actualizadas" => $actualizadas,
                "no_encontradas" => $noEncontradas,
                "errores" => $errores
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                "actualizadas" => 0,
                "no_encontradas" => 0,
                "errores" => ["Excepción: " . $e->getMessage()]
            ];
        }
    }

    /**
     * Eliminar todas las metas de una fecha.
     */
    public function deleteByDate() {
        $query = "DELETE FROM " . $this->table_name . " WHERE fecha = :fecha";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $this->fecha);
        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_meta = :id_meta";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_meta', $this->id_meta);
        return $stmt->execute();
    }
}
?>