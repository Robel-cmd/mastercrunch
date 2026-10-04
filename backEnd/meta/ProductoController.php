<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Categoria.php';

class ProductoController {
    private $db;
    private $producto;
    private $upload_dir;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->producto = new Producto($this->db);
        // Ruta a la carpeta uploads/productos 
        $this->upload_dir = __DIR__ . '/../../uploads/productos/';
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
                $nombreUnico = uniqid('prod_') . '.' . $extension;
                $destino = $this->upload_dir . $nombreUnico;
                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $data['url_imagen'] = 'uploads/productos/' . $nombreUnico;
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

    public function create() {
        $data = $this->getInputData();
        if (empty($data['codigo_interno']) || empty($data['nombre']) ||
            !isset($data['precio']) || empty($data['id_categoria'])) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requieren: codigo_interno, nombre, precio, id_categoria."]);
            return;
        }

        $this->producto->codigo_interno = $data['codigo_interno'];
        $this->producto->nombre         = $data['nombre'];
        $this->producto->precio         = $data['precio'];
        $this->producto->id_categoria   = $data['id_categoria'];
        $this->producto->url_imagen     = isset($data['url_imagen']) ? $data['url_imagen'] : null;
        $this->producto->disponibilidad = isset($data['disponibilidad']) ? $data['disponibilidad'] : 1;
        $this->producto->es_extra       = isset($data['es_extra']) ? $data['es_extra'] : 0;

        if ($this->producto->create()) {
            http_response_code(201);
            echo json_encode(["message" => "Producto creado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo crear el producto."]);
        }
    }

    public function read() {
        $stmt = $this->producto->read();
        $num = $stmt->rowCount();
        if ($num > 0) {
            $productos_arr = ["registros" => []];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $productos_arr["registros"][] = [
                    "id_producto"      => $id_producto,
                    "codigo_interno"   => $codigo_interno,
                    "nombre"           => $nombre,
                    "precio"           => $precio,
                    "id_categoria"     => $id_categoria,
                    "categoria_nombre" => $categoria_nombre,
                    "url_imagen"       => $url_imagen,
                    "disponibilidad"   => $disponibilidad,
                    "es_extra"         => $es_extra,
                    "fecha_creacion"   => $fecha_creacion
                ];
            }
            http_response_code(200);
            echo json_encode($productos_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron productos."]);
        }
    }

    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $producto = $this->producto->readOne($id);
        if ($producto) {
            http_response_code(200);
            echo json_encode($producto);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Producto no encontrado."]);
        }
    }

    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_producto']) ? $data['id_producto'] : (isset($_GET['id']) ? $_GET['id'] : null);
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID de producto no proporcionado."]);
            return;
        }

        $current = $this->producto->readOne($id);
        if (!$current) {
            http_response_code(404);
            echo json_encode(["message" => "Producto no encontrado."]);
            return;
        }

        $this->producto->id_producto = $id;
        $this->producto->codigo_interno = isset($data['codigo_interno']) ? $data['codigo_interno'] : $current['codigo_interno'];
        $this->producto->nombre = isset($data['nombre']) ? $data['nombre'] : $current['nombre'];
        $this->producto->precio = isset($data['precio']) ? $data['precio'] : $current['precio'];
        $this->producto->id_categoria = isset($data['id_categoria']) ? $data['id_categoria'] : $current['id_categoria'];

        if (isset($data['url_imagen']) && !empty($data['url_imagen'])) {
            $this->producto->url_imagen = $data['url_imagen'];
        } else {
            $this->producto->url_imagen = $current['url_imagen'];
        }

        $this->producto->disponibilidad = isset($data['disponibilidad']) ? $data['disponibilidad'] : $current['disponibilidad'];
        $this->producto->es_extra = isset($data['es_extra']) ? $data['es_extra'] : $current['es_extra'];

        if ($this->producto->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Producto actualizado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar el producto."]);
        }
    }

    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_producto']) ? $data['id_producto'] : null;
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["message" => "ID del producto no proporcionado."]);
            return;
        }
        $this->producto->id_producto = $id;
        if ($this->producto->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Producto eliminado exitosamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar el producto."]);
        }
    }

    public function getCategorias() {
        $categoria = new Categoria($this->db);
        $stmt = $categoria->read();
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
            echo json_encode(["message" => "No hay categorías activas."]);
        }
    }
}
?>