<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Categoria.php';

class CategoriaController {
    private $db;
    private $categoria;
    private $upload_dir;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->categoria = new Categoria($this->db);
        $this->upload_dir = __DIR__ . '/../../uploads/categorias/';
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    // Obtener datos de entrada (JSON o multipart)
    private function getInputData() {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['imagen'];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nombreUnico = uniqid('cat_') . '.' . $extension;
                $destino = $this->upload_dir . $nombreUnico;
                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $data['imagen'] = 'uploads/categorias/' . $nombreUnico;
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

    // CREATE
    public function create() {
        $data = $this->getInputData();
        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(["message" => "El campo nombre es obligatorio."]);
            return;
        }

        $this->categoria->nombre = $data['nombre'];
        $this->categoria->descripcion = isset($data['descripcion']) ? $data['descripcion'] : null;
        $this->categoria->activo = isset($data['activo']) ? $data['activo'] : 1;
        $this->categoria->imagen = isset($data['imagen']) ? $data['imagen'] : null;

        if ($this->categoria->create()) {
            http_response_code(201);
            echo json_encode(["message" => "Categoría creada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo crear la categoría."]);
        }
    }

    // READ (todas) – incluye inactivas si se pide
    public function read() {
        $incluirInactivas = isset($_GET['incluirInactivas']) && $_GET['incluirInactivas'] == 'true';
        $stmt = $this->categoria->read($incluirInactivas);
        $num = $stmt->rowCount();
        if ($num > 0) {
            $categorias_arr = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categorias_arr[] = $row;
            }
            http_response_code(200);
            echo json_encode($categorias_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No hay categorías."]);
        }
    }

    // READ ONE
    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $categoria = $this->categoria->readOne($id);
        if ($categoria) {
            http_response_code(200);
            echo json_encode($categoria);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Categoría no encontrada."]);
        }
    }

    // UPDATE
    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_categoria']) ? $data['id_categoria'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de categoría no proporcionado."]);
            return;
        }

        $current = $this->categoria->readOne($id);
        if (!$current) {
            http_response_code(404);
            echo json_encode(["message" => "Categoría no encontrada."]);
            return;
        }

        $this->categoria->id_categoria = $id;
        $this->categoria->nombre = isset($data['nombre']) ? $data['nombre'] : $current['nombre'];
        $this->categoria->descripcion = isset($data['descripcion']) ? $data['descripcion'] : $current['descripcion'];
        $this->categoria->activo = isset($data['activo']) ? $data['activo'] : $current['activo'];

        // Manejo de imagen: si se sube nueva, se usa; si no, se conserva la actual
        if (isset($data['imagen']) && !empty($data['imagen'])) {
            $this->categoria->imagen = $data['imagen'];
        } else {
            $this->categoria->imagen = $current['imagen'];
        }

        if ($this->categoria->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Categoría actualizada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar la categoría."]);
        }
    }

    // DELETE (eliminar físicamente)
    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_categoria']) ? $data['id_categoria'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de categoría no proporcionado."]);
            return;
        }

        $this->categoria->id_categoria = $id;
        if ($this->categoria->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Categoría eliminada exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar la categoría."]);
        }
    }
}
?>
