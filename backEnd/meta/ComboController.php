<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Combo.php';

class ComboController {
    private $db;
    private $combo;
    private $upload_dir;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->combo = new Combo($this->db);
        $this->upload_dir = __DIR__ . '/../../uploads/combos/';
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    private function getInputData() {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['imagen'];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nombreUnico = uniqid('combo_') . '.' . $extension;
                $destino = $this->upload_dir . $nombreUnico;
                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $data['url_imagen_combo'] = 'uploads/combos/' . $nombreUnico;
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Error al guardar la imagen."]);
                    exit;
                }
            }
            return $data;
        } else {
            $data = json_decode(file_get_contents("php://input"), true);
            return $data ?: [];
        }
    }

    /**
     * Valida y prepara los detalles del combo.
     * - Obtiene precios actuales y disponibilidad de productos.
     * - Si no se envía precio_individual, usa el precio del producto.
     * - Verifica que todos los productos estén disponibles.
     * - Calcula la suma (cantidad * precio_individual) y la compara con precio_total.
     * - Redondea a 2 decimales para evitar errores de precisión.
     */
    private function validateAndPrepareDetails($detalles, $precio_total) {
        if (empty($detalles)) {
            throw new Exception('Se requieren detalles.');
        }

        $ids = array_column($detalles, 'id_producto');
        if (empty($ids)) {
            throw new Exception('No hay productos en los detalles.');
        }

        // Obtener precios y disponibilidad de los productos
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT id_producto, precio, disponibilidad FROM productos WHERE id_producto IN ($placeholders)";
        $stmt = $this->db->prepare($query);
        foreach ($ids as $k => $id) {
            $stmt->bindValue($k+1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[$row['id_producto']] = $row;
        }

        $suma = 0;
        $detalles_preparados = [];
        foreach ($detalles as $det) {
            $id_producto = $det['id_producto'];
            $cantidad = isset($det['cantidad']) ? (int)$det['cantidad'] : 1;

            // Verificar que el producto exista y esté disponible
            if (!isset($productos[$id_producto])) {
                throw new Exception("Producto con ID $id_producto no encontrado.");
            }
            if ($productos[$id_producto]['disponibilidad'] != 1) {
                throw new Exception("El producto con ID $id_producto no está disponible.");
            }

            // Si no se envía precio_individual, usar el precio actual del producto
            if (isset($det['precio_individual'])) {
                $precio_individual = (float)$det['precio_individual'];
            } else {
                $precio_individual = (float)$productos[$id_producto]['precio'];
            }

            $detalles_preparados[] = [
                'id_producto' => $id_producto,
                'cantidad' => $cantidad,
                'precio_individual' => $precio_individual
            ];
            $suma += $cantidad * $precio_individual;
        }

        // Redondear a 2 decimales para comparación exacta
        $suma = round($suma, 2);
        $precio_total = round($precio_total, 2);

        if (abs($suma - $precio_total) > 0.01) {
            throw new Exception("La suma de los precios individuales ($suma) no coincide con el precio total ($precio_total).");
        }

        return $detalles_preparados;
    }

    public function create() {
        $data = $this->getInputData();
        if (empty($data['nombre']) || !isset($data['precio_total']) || empty($data['detalles'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos: nombre, precio_total y detalles son requeridos."]);
            return;
        }

        try {
            $detalles_preparados = $this->validateAndPrepareDetails($data['detalles'], $data['precio_total']);
            $this->combo->detalles = $detalles_preparados;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => $e->getMessage()]);
            return;
        }

        $this->combo->nombre = $data['nombre'];
        $this->combo->descripcion = $data['descripcion'] ?? null;
        $this->combo->precio_total = $data['precio_total'];
        $this->combo->url_imagen_combo = $data['url_imagen_combo'] ?? null;
        $this->combo->activo = isset($data['activo']) ? $data['activo'] : 1;

        if ($this->combo->create()) {
            http_response_code(201);
            echo json_encode(["message" => "Combo creado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo crear el combo."]);
        }
    }

    public function read() {
        $query = "SELECT c.id_combo, c.nombre, c.descripcion, c.precio_total,
                         c.url_imagen_combo, c.activo,
                         cd.id_producto, cd.cantidad, cd.precio_individual,
                         p.nombre as producto_nombre
                  FROM combo c
                  LEFT JOIN combo_detalle cd ON c.id_combo = cd.id_combo
                  LEFT JOIN productos p ON cd.id_producto = p.id_producto
                  ORDER BY c.id_combo DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron combos."]);
            return;
        }

        $combos = [];
        foreach ($rows as $row) {
            $id = $row['id_combo'];
            if (!isset($combos[$id])) {
                $combos[$id] = [
                    "id_combo"         => $row['id_combo'],
                    "nombre"           => $row['nombre'],
                    "descripcion"      => $row['descripcion'],
                    "precio_total"     => $row['precio_total'],
                    "url_imagen_combo" => $row['url_imagen_combo'],
                    "activo"           => $row['activo'],
                    "detalles"         => []
                ];
            }
            if ($row['id_producto'] !== null) {
                $combos[$id]['detalles'][] = [
                    "id_producto"        => $row['id_producto'],
                    "producto_nombre"    => $row['producto_nombre'],
                    "cantidad"           => $row['cantidad'],
                    "precio_individual"  => $row['precio_individual']
                ];
            }
        }

        http_response_code(200);
        echo json_encode(["registros" => array_values($combos)]);
    }

    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $combo = $this->combo->readOne($id);
        if ($combo) {
            http_response_code(200);
            echo json_encode($combo);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Combo no encontrado."]);
        }
    }

    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_combo']) ? $data['id_combo'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de combo no proporcionado."]);
            return;
        }

        $current = $this->combo->readOne($id);
        if (!$current) {
            http_response_code(404);
            echo json_encode(["message" => "Combo no encontrado."]);
            return;
        }

        $this->combo->id_combo = $id;
        $this->combo->nombre = $data['nombre'] ?? $current['nombre'];
        $this->combo->descripcion = $data['descripcion'] ?? $current['descripcion'];
        $this->combo->precio_total = $data['precio_total'] ?? $current['precio_total'];
        $this->combo->url_imagen_combo = isset($data['url_imagen_combo']) && !empty($data['url_imagen_combo'])
                                        ? $data['url_imagen_combo']
                                        : $current['url_imagen_combo'];
        $this->combo->activo = $data['activo'] ?? $current['activo'];

        if (isset($data['detalles']) && is_array($data['detalles'])) {
            try {
                $precio_total_validar = $data['precio_total'] ?? $current['precio_total'];
                $detalles_preparados = $this->validateAndPrepareDetails($data['detalles'], $precio_total_validar);
                $this->combo->detalles = $detalles_preparados;
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(["message" => $e->getMessage()]);
                return;
            }
        } else {
            $this->combo->detalles = $current['detalles'] ?? [];
        }

        if ($this->combo->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Combo actualizado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar el combo."]);
        }
    }

    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_combo']) ? $data['id_combo'] : null;
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID del combo no proporcionado."]);
            return;
        }
        $this->combo->id_combo = $id;
        if ($this->combo->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Combo eliminado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar el combo."]);
        }
    }
}
?>