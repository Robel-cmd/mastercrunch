<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Meta.php';

class MetaController {
    private $db;
    private $meta;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->meta = new Meta($this->db);
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

    // Crear (individual o masivo)
    public function create() {
        $data = $this->getInputData();

        if (isset($data['metas']) && is_array($data['metas'])) {
            $fechaGlobal = isset($data['fecha']) ? $data['fecha'] : null;

            $metasPreparadas = [];
            foreach ($data['metas'] as $m) {
                if (empty($m['fecha']) && $fechaGlobal) {
                    $m['fecha'] = $fechaGlobal;
                }
                $metasPreparadas[] = $m;
            }

            $resultado = $this->meta->createOrUpdateBatch($metasPreparadas);
            http_response_code(200);
            echo json_encode([
                "message" => "Proceso con multiples registros completado.",
                "creadas" => $resultado['creadas'] . " registros realizados",
                "actualizadas" => $resultado['actualizadas'] . " registros actualizados" ,
                "errores" => $resultado['errores']
            ]);
            return;
        }

        if (empty($data['id_producto']) || empty($data['fecha']) || !isset($data['cantidad_meta'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos: id_producto, fecha, cantidad_meta son requeridos."]);
            return;
        }

        $this->meta->id_producto = $data['id_producto'];
        $this->meta->fecha = $data['fecha'];
        $this->meta->cantidad_meta = $data['cantidad_meta'];
        $this->meta->ventas_reales = isset($data['ventas_reales']) ? $data['ventas_reales'] : 0;

        if ($this->meta->createOrUpdate()) {
            http_response_code(201);
            echo json_encode(["message" => "Meta guardada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo guardar la meta."]);
        }
    }

    public function read() {
        $stmt = $this->meta->read();
        $num = $stmt->rowCount();
        if ($num > 0) {
            $metas_arr = ["registros" => []];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metas_arr["registros"][] = $row;
            }
            http_response_code(200);
            echo json_encode($metas_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron metas."]);
        }
    }

    // Leer todas las metas de una fecha
    public function readByDate() {
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
        if (empty($fecha)) {
            http_response_code(400);
            echo json_encode(["message" => "El parámetro fecha es requerido."]);
            return;
        }

        $this->meta->fecha = $fecha;
        $stmt = $this->meta->readByDate();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $metas_arr = ["registros" => []];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metas_arr["registros"][] = $row;
            }
            http_response_code(200);
            echo json_encode($metas_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron metas para la fecha $fecha."]);
        }
    }

    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $this->meta->id_meta = $id;
        $meta = $this->meta->readOne();
        if ($meta) {
            http_response_code(200);
            echo json_encode($meta);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Meta no encontrada."]);
        }
    }

    public function readByProductAndDate() {
        $id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : 0;
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
        if ($id_producto <= 0 || empty($fecha)) {
            http_response_code(400);
            echo json_encode(["message" => "id_producto y fecha son requeridos."]);
            return;
        }
        $this->meta->id_producto = $id_producto;
        $this->meta->fecha = $fecha;
        $meta = $this->meta->readByProductAndDate();
        if ($meta) {
            http_response_code(200);
            echo json_encode($meta);
        } else {
            http_response_code(200);
            echo json_encode([
                "id_meta" => null,
                "id_producto" => $id_producto,
                "fecha" => $fecha,
                "cantidad_meta" => 0,
                "ventas_reales" => 0
            ]);
        }
    }

    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_meta']) ? $data['id_meta'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de meta no proporcionado."]);
            return;
        }

        $this->meta->id_meta = $id;
        $current = $this->meta->readOne();
        if (!$current) {
            http_response_code(404);
            echo json_encode(["message" => "Meta no encontrada."]);
            return;
        }

        $this->meta->id_producto = isset($data['id_producto']) ? $data['id_producto'] : $current['id_producto'];
        $this->meta->fecha = isset($data['fecha']) ? $data['fecha'] : $current['fecha'];
        $this->meta->cantidad_meta = isset($data['cantidad_meta']) ? $data['cantidad_meta'] : $current['cantidad_meta'];
        $this->meta->ventas_reales = isset($data['ventas_reales']) ? $data['ventas_reales'] : $current['ventas_reales'];

        if ($this->meta->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Meta actualizada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar la meta."]);
        }
    }

    // NUEVO: Actualizar en lote todas las metas de una fecha
    public function updateByDate() {
        $data = $this->getInputData();
        $fecha = isset($data['fecha']) ? $data['fecha'] : (isset($_GET['fecha']) ? $_GET['fecha'] : null);

        if (empty($fecha)) {
            http_response_code(400);
            echo json_encode(["message" => "El parámetro fecha es requerido."]);
            return;
        }
        if (empty($data['metas']) || !is_array($data['metas'])) {
            http_response_code(400);
            echo json_encode(["message" => "Se requiere un array 'metas' con los cambios."]);
            return;
        }

        $this->meta->fecha = $fecha;
        $resultado = $this->meta->updateByDate($data['metas']);

        http_response_code(200);
        echo json_encode([
            "message" => "Actualización por fecha completada.",
            "fecha" => $fecha,
            "actualizadas" => $resultado['actualizadas'],
            "no_encontradas" => $resultado['no_encontradas'],
            "errores" => $resultado['errores']
        ]);
    }

    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_meta']) ? $data['id_meta'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de meta no proporcionado."]);
            return;
        }

        $this->meta->id_meta = $id;
        if ($this->meta->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Meta eliminada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar la meta."]);
        }
    }

    // NUEVO: Eliminar todas las metas de una fecha
    public function deleteByDate() {
        $data = $this->getInputData();
        $fecha = isset($data['fecha']) ? $data['fecha'] : (isset($_GET['fecha']) ? $_GET['fecha'] : null);

        if (empty($fecha)) {
            http_response_code(400);
            echo json_encode(["message" => "El parámetro fecha es requerido."]);
            return;
        }

        $this->meta->fecha = $fecha;
        $eliminadas = $this->meta->deleteByDate();

        if ($eliminadas === false) {
            http_response_code(503);
            echo json_encode(["message" => "No se pudieron eliminar las metas."]);
            return;
        }

        if ($eliminadas === 0) {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron metas para la fecha $fecha."]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            "message" => "Metas eliminadas exitosamente.",
            "fecha" => $fecha,
            "eliminadas" => $eliminadas
        ]);
    }
}
?>