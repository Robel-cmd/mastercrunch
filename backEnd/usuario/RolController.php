<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Rol.php';

class RolController {
    private $db;
    private $rol;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->rol = new Rol($this->db);
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

    public function create() {
        $data = $this->getInputData();

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(["message" => "El campo nombre es obligatorio."]);
            return;
        }

        $this->rol->nombre = $data['nombre'];
        $this->rol->descripcion = isset($data['descripcion']) ? $data['descripcion'] : null;
        $this->rol->nivel_acceso = isset($data['nivel_acceso']) ? $data['nivel_acceso'] : Rol::NIVEL_COCINERO;

        if ((int)$this->rol->nivel_acceso <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "nivel_acceso debe ser un número mayor a 0 (1 = mayor acceso)."]);
            return;
        }

        if ($this->rol->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Rol creado exitosamente.",
                "id_rol" => (int)$this->rol->id_rol
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo crear el rol."]);
        }
    }

    public function read() {
        $stmt = $this->rol->read();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar roles."]);
            return;
        }

        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $row;
        }

        http_response_code(200);
        echo json_encode(["registros" => $roles]);
    }

    public function readOne() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }

        $rol = $this->rol->readOne($id);

        if ($rol === false) {
            http_response_code(404);
            echo json_encode(["message" => "Rol no encontrado."]);
            return;
        }

        http_response_code(200);
        echo json_encode($rol);
    }

    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_rol']) ? $data['id_rol'] : (isset($_GET['id']) ? $_GET['id'] : null);

        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID de rol no proporcionado o inválido."]);
            return;
        }

        $actual = $this->rol->readOne($id);
        if ($actual === false) {
            http_response_code(404);
            echo json_encode(["message" => "Rol no encontrado."]);
            return;
        }

        $this->rol->id_rol = $id;
        $this->rol->nombre = isset($data['nombre']) ? $data['nombre'] : $actual['nombre'];
        $this->rol->descripcion = isset($data['descripcion']) ? $data['descripcion'] : $actual['descripcion'];
        $this->rol->nivel_acceso = isset($data['nivel_acceso']) ? $data['nivel_acceso'] : $actual['nivel_acceso'];

        if ($this->rol->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Rol actualizado exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo actualizar el rol."]);
        }
    }

    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_rol']) ? $data['id_rol'] : (isset($_GET['id']) ? $_GET['id'] : null);

        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID de rol no proporcionado o inválido."]);
            return;
        }

        $this->rol->id_rol = $id;

        if ($this->rol->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Rol eliminado exitosamente."]);
        } else {
            http_response_code(409);
            echo json_encode(["message" => "No se pudo eliminar el rol: tiene empleados asignados. Reasígneelos antes de eliminarlo."]);
        }
    }
}
?>
