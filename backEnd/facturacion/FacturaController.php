<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Factura.php';

class FacturaController {
    private $db;
    private $factura;
    private $isv_rate = 0.15; // ISV Honduras 15%

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->factura = new Factura($this->db);
    }

    private function getInputData() {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        } else {
            $data = json_decode(file_get_contents("php://input"), true);
            return $data ?: [];
        }
    }

    private function existeRegistro($tabla, $campo_id, $valor) {
        $query = "SELECT COUNT(*) AS total FROM $tabla WHERE $campo_id = :valor";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':valor', $valor);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    private function numeroFacturaExiste($numero, $excluir_id = null) {
        $query = "SELECT COUNT(*) AS total FROM facturas WHERE numero_factura = :num";
        if ($excluir_id !== null) {
            $query .= " AND id_factura <> :id";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':num', $numero);
        if ($excluir_id !== null) {
            $stmt->bindParam(':id', $excluir_id);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] > 0;
    }

    /**
     * Valida y prepara los detalles de la factura.
     * Cada detalle debe tener id_producto O id_combo (excluyentes).
     * Calcula subtotal_linea, isv_linea y total_linea por detalle,
     * y los totales agregados de la factura.
     */
    private function validateAndPrepareDetalles($detalles) {
        if (empty($detalles) || !is_array($detalles)) {
            throw new Exception('Se requieren detalles en la factura.');
        }

        // Recolectar IDs por tipo
        $ids_productos = [];
        $ids_combos = [];
        foreach ($detalles as $det) {
            $id_p = isset($det['id_producto']) && $det['id_producto'] !== null && $det['id_producto'] !== ''
                    ? (int)$det['id_producto'] : null;
            $id_c = isset($det['id_combo']) && $det['id_combo'] !== null && $det['id_combo'] !== ''
                    ? (int)$det['id_combo'] : null;

            if ($id_p === null && $id_c === null) {
                throw new Exception("Cada detalle debe incluir id_producto o id_combo.");
            }
            if ($id_p !== null && $id_c !== null) {
                throw new Exception("Un detalle no puede tener id_producto e id_combo al mismo tiempo.");
            }
            if ($id_p !== null) $ids_productos[] = $id_p;
            if ($id_c !== null) $ids_combos[] = $id_c;
        }

        // Consultar productos existentes
        $productos_existentes = [];
        if (!empty($ids_productos)) {
            $ids_productos = array_values(array_unique($ids_productos));
            $ph = implode(',', array_fill(0, count($ids_productos), '?'));
            $stmt = $this->db->prepare("SELECT id_producto, nombre FROM productos WHERE id_producto IN ($ph)");
            foreach ($ids_productos as $k => $id) {
                $stmt->bindValue($k+1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productos_existentes[$row['id_producto']] = $row;
            }
        }

        // Consultar combos existentes
        $combos_existentes = [];
        if (!empty($ids_combos)) {
            $ids_combos = array_values(array_unique($ids_combos));
            $ph = implode(',', array_fill(0, count($ids_combos), '?'));
            $stmt = $this->db->prepare("SELECT id_combo, nombre FROM combo WHERE id_combo IN ($ph)");
            foreach ($ids_combos as $k => $id) {
                $stmt->bindValue($k+1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $combos_existentes[$row['id_combo']] = $row;
            }
        }

        $detalles_preparados = [];
        $subtotal_total = 0;
        $isv_total = 0;
        $total_total = 0;

        foreach ($detalles as $det) {
            $id_producto = isset($det['id_producto']) && $det['id_producto'] !== null && $det['id_producto'] !== ''
                           ? (int)$det['id_producto'] : null;
            $id_combo = isset($det['id_combo']) && $det['id_combo'] !== null && $det['id_combo'] !== ''
                        ? (int)$det['id_combo'] : null;

            if ($id_producto !== null && !isset($productos_existentes[$id_producto])) {
                throw new Exception("Producto con ID $id_producto no encontrado.");
            }
            if ($id_combo !== null && !isset($combos_existentes[$id_combo])) {
                throw new Exception("Combo con ID $id_combo no encontrado.");
            }

            $cantidad = (int)($det['cantidad'] ?? 0);
            if ($cantidad <= 0) {
                $ref = $id_producto !== null ? "producto $id_producto" : "combo $id_combo";
                throw new Exception("La cantidad debe ser mayor a 0 para el $ref.");
            }

            if (!isset($det['precio_unitario'])) {
                throw new Exception("Cada detalle debe incluir precio_unitario.");
            }
            $precio_unitario = (float)$det['precio_unitario'];
            if ($precio_unitario < 0) {
                throw new Exception("El precio_unitario no puede ser negativo.");
            }

            $descuento = (float)($det['descuento'] ?? 0);
            if ($descuento < 0) {
                throw new Exception("El descuento no puede ser negativo.");
            }

            $subtotal_linea = round(($cantidad * $precio_unitario) - $descuento, 2);
            if ($subtotal_linea < 0) {
                throw new Exception("El descuento excede el subtotal del ítem.");
            }
            $isv_linea = round($subtotal_linea * $this->isv_rate, 2);
            $total_linea = round($subtotal_linea + $isv_linea, 2);

            $detalles_preparados[] = [
                'id_producto' => $id_producto,
                'id_combo' => $id_combo,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio_unitario,
                'descuento' => $descuento,
                'subtotal_linea' => $subtotal_linea,
                'isv_linea' => $isv_linea,
                'total_linea' => $total_linea
            ];

            $subtotal_total += $subtotal_linea;
            $isv_total += $isv_linea;
            $total_total += $total_linea;
        }

        return [
            'detalles' => $detalles_preparados,
            'subtotal' => round($subtotal_total, 2),
            'isv' => round($isv_total, 2),
            'total' => round($total_total, 2)
        ];
    }

    // POST – Crear factura
    public function create() {
        $data = $this->getInputData();

        // Generar número automático si no viene
        if (empty($data['numero_factura'])) {
            $data['numero_factura'] = 'FAC-' . date('YmdHis') . '-' . rand(1000, 9999);
        }

        // Validar campos obligatorios
        $required = ['id_pedido', 'id_empleado', 'id_metodo_pago', 'detalles'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Datos incompletos: $field es requerido."]);
                return;
            }
        }

        // Validar existencia de referencias
        if (!$this->existeRegistro('pedidos', 'id_pedido', $data['id_pedido'])) {
            http_response_code(400);
            echo json_encode(["message" => "Pedido con ID {$data['id_pedido']} no encontrado."]);
            return;
        }
        if (!$this->existeRegistro('empleados', 'id_empleado', $data['id_empleado'])) {
            http_response_code(400);
            echo json_encode(["message" => "Empleado con ID {$data['id_empleado']} no encontrado."]);
            return;
        }
        if (!$this->existeRegistro('metodo_pago', 'id_metodo_pago', $data['id_metodo_pago'])) {
            http_response_code(400);
            echo json_encode(["message" => "Método de pago con ID {$data['id_metodo_pago']} no encontrado."]);
            return;
        }

        // Validar número de factura único
        if ($this->numeroFacturaExiste($data['numero_factura'])) {
            http_response_code(400);
            echo json_encode(["message" => "El número de factura '{$data['numero_factura']}' ya existe."]);
            return;
        }

        // Validar y preparar detalles
        try {
            $preparado = $this->validateAndPrepareDetalles($data['detalles']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => $e->getMessage()]);
            return;
        }

        $this->factura->numero_factura = $data['numero_factura'];
        $this->factura->id_pedido = $data['id_pedido'];
        $this->factura->id_empleado = $data['id_empleado'];
        $this->factura->id_metodo_pago = $data['id_metodo_pago'];
        $this->factura->subtotal = $preparado['subtotal'];
        $this->factura->isv = $preparado['isv'];
        $this->factura->total = $preparado['total'];
        $this->factura->estado_factura = $data['estado_factura'] ?? 'emitida';
        $this->factura->rtn_cliente = $data['rtn_cliente'] ?? null;
        $this->factura->observaciones = $data['observaciones'] ?? null;
        $this->factura->detalles = $preparado['detalles'];

        if ($this->factura->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Factura creada exitosamente.",
                "id_factura" => $this->factura->id_factura,
                "subtotal" => $preparado['subtotal'],
                "isv" => $preparado['isv'],
                "total" => $preparado['total']
            ]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo crear la factura."]);
        }
    }

    // GET – Listar todas
    public function read() {
        $stmt = $this->factura->read();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron facturas."]);
            return;
        }

        $facturas = [];
        foreach ($rows as $row) {
            $id = $row['id_factura'];
            if (!isset($facturas[$id])) {
                $facturas[$id] = [
                    "id_factura" => $row['id_factura'],
                    "numero_factura" => $row['numero_factura'],
                    "id_pedido" => $row['id_pedido'],
                    "id_empleado" => $row['id_empleado'],
                    "id_metodo_pago" => $row['id_metodo_pago'],
                    "subtotal" => $row['subtotal'],
                    "isv" => $row['isv'],
                    "total" => $row['total'],
                    "fecha_hora_factura" => $row['fecha_hora_factura'],
                    "estado_factura" => $row['estado_factura'],
                    "rtn_cliente" => $row['rtn_cliente'],
                    "observaciones" => $row['observaciones'],
                    "detalles" => []
                ];
            }
            if ($row['id_producto'] !== null || $row['id_combo'] !== null) {
                $facturas[$id]['detalles'][] = [
                    "id_factura_detalle" => $row['id_factura_detalle'],
                    "id_producto" => $row['id_producto'],
                    "id_combo" => $row['id_combo'],
                    "tipo" => $row['tipo'],
                    "nombre" => $row['item_nombre'],
                    "cantidad" => $row['cantidad'],
                    "precio_unitario" => $row['precio_unitario'],
                    "descuento" => $row['descuento'],
                    "subtotal_linea" => $row['subtotal_linea'],
                    "isv_linea" => $row['isv_linea'],
                    "total_linea" => $row['total_linea']
                ];
            }
        }

        http_response_code(200);
        echo json_encode(["registros" => array_values($facturas)]);
    }

    // GET – Leer una factura por ID
    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $factura = $this->factura->readOne($id);
        if ($factura) {
            http_response_code(200);
            echo json_encode($factura);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Factura no encontrada."]);
        }
    }

    // PUT – Actualizar factura
    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_factura']) ? $data['id_factura'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de factura no proporcionado."]);
            return;
        }

        $current = $this->factura->readOne($id);
        if (!$current) {
            http_response_code(404);
            echo json_encode(["message" => "Factura no encontrada."]);
            return;
        }

        // Preparar detalles si vienen
        if (isset($data['detalles']) && is_array($data['detalles'])) {
            try {
                $preparado = $this->validateAndPrepareDetalles($data['detalles']);
                $this->factura->detalles = $preparado['detalles'];
                $this->factura->subtotal = $preparado['subtotal'];
                $this->factura->isv = $preparado['isv'];
                $this->factura->total = $preparado['total'];
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(["message" => $e->getMessage()]);
                return;
            }
        } else {
            // Reconstruir detalles actuales incluyendo id_combo
            $actuales = [];
            foreach ($current['detalles'] as $d) {
                $actuales[] = [
                    'id_producto' => $d['id_producto'],
                    'id_combo' => $d['id_combo'],
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio_unitario'],
                    'descuento' => $d['descuento'],
                    'subtotal_linea' => $d['subtotal_linea'],
                    'isv_linea' => $d['isv_linea'],
                    'total_linea' => $d['total_linea']
                ];
            }
            $this->factura->detalles = $actuales;
            $this->factura->subtotal = $current['subtotal'];
            $this->factura->isv = $current['isv'];
            $this->factura->total = $current['total'];
        }

        // Validar numero_factura único si se cambia
        $nuevo_num = $data['numero_factura'] ?? $current['numero_factura'];
        if ($nuevo_num !== $current['numero_factura'] && $this->numeroFacturaExiste($nuevo_num, $id)) {
            http_response_code(400);
            echo json_encode(["message" => "El número de factura '$nuevo_num' ya existe."]);
            return;
        }

        $this->factura->id_factura = $id;
        $this->factura->numero_factura = $nuevo_num;
        $this->factura->id_pedido = $data['id_pedido'] ?? $current['id_pedido'];
        $this->factura->id_empleado = $data['id_empleado'] ?? $current['id_empleado'];
        $this->factura->id_metodo_pago = $data['id_metodo_pago'] ?? $current['id_metodo_pago'];
        $this->factura->estado_factura = $data['estado_factura'] ?? $current['estado_factura'];
        $this->factura->rtn_cliente = $data['rtn_cliente'] ?? $current['rtn_cliente'];
        $this->factura->observaciones = $data['observaciones'] ?? $current['observaciones'];

        if ($this->factura->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Factura actualizada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar la factura."]);
        }
    }

    // DELETE – Eliminar factura
    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_factura']) ? $data['id_factura'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de factura no proporcionado."]);
            return;
        }
        $this->factura->id_factura = $id;
        if ($this->factura->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Factura eliminada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar la factura."]);
        }
    }
}
?>