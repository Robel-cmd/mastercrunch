<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Empleado.php';

class EmpleadoController {
    private $db;
    private $empleado;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->empleado = new Empleado($this->db);
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

        if (
            empty($data['nombre']) ||
            empty($data['apellido']) ||
            empty($data['usuario']) ||
            !isset($data['contrasena']) ||
            empty($data['id_rol'])
        ) {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Se requieren: nombre, apellido, usuario, contrasena e id_rol."]);
            return;
        }

        if (!Empleado::isValidUsuario($data['usuario'])) {
            http_response_code(400);
            echo json_encode(["message" => "El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion bajo o guion."]);
            return;
        }

        if (!Empleado::isValidContrasena($data['contrasena'])) {
            http_response_code(400);
            echo json_encode(["message" => "La contraseña debe tener al menos 6 caracteres."]);
            return;
        }

        $this->empleado->nombre = $data['nombre'];
        $this->empleado->apellido = $data['apellido'];
        $this->empleado->numero_telefono = isset($data['numero_telefono']) ? $data['numero_telefono'] : null;
        $this->empleado->usuario = $data['usuario'];
        $this->empleado->contrasena = $data['contrasena'];
        $this->empleado->id_rol = $data['id_rol'];
        $this->empleado->fecha_contratacion = isset($data['fecha_contratacion']) ? $data['fecha_contratacion'] : null;

        if (!$this->empleado->existsRol($this->empleado->id_rol)) {
            http_response_code(400);
            echo json_encode(["message" => "El rol indicado no existe."]);
            return;
        }

        if ($this->empleado->existsUsuario($this->empleado->usuario)) {
            http_response_code(409);
            echo json_encode(["message" => "Ya existe un empleado con ese usuario."]);
            return;
        }

        if ($this->empleado->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Empleado creado exitosamente.",
                "id_empleado" => (int)$this->empleado->id_empleado
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo crear el empleado."]);
        }
    }

    public function read() {
        $incluirInactivos = isset($_GET['incluirInactivos']) && $_GET['incluirInactivos'] == 'true';

        $stmt = $this->empleado->read($incluirInactivos);

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar empleados."]);
            return;
        }

        $empleados = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $empleados[] = $row;
        }

        http_response_code(200);
        echo json_encode(["registros" => $empleados]);
    }

    public function readOne() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }

        $empleado = $this->empleado->readOne($id);

        if ($empleado === false) {
            http_response_code(404);
            echo json_encode(["message" => "Empleado no encontrado."]);
            return;
        }

        http_response_code(200);
        echo json_encode($empleado);
    }

    public function update() {
        $data = $this->getInputData();
        $id = isset($data['id_empleado']) ? $data['id_empleado'] : (isset($_GET['id']) ? $_GET['id'] : null);

        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID de empleado no proporcionado o inválido."]);
            return;
        }

        $actual = $this->empleado->readOne($id);
        if ($actual === false) {
            http_response_code(404);
            echo json_encode(["message" => "Empleado no encontrado."]);
            return;
        }

        $this->empleado->id_empleado = $id;
        $this->empleado->nombre = isset($data['nombre']) ? $data['nombre'] : $actual['nombre'];
        $this->empleado->apellido = isset($data['apellido']) ? $data['apellido'] : $actual['apellido'];
        $this->empleado->numero_telefono = isset($data['numero_telefono']) ? $data['numero_telefono'] : $actual['numero_telefono'];
        $this->empleado->usuario = isset($data['usuario']) ? $data['usuario'] : $actual['usuario'];
        $this->empleado->id_rol = isset($data['id_rol']) ? $data['id_rol'] : $actual['id_rol'];
        $this->empleado->activo = isset($data['activo']) ? $data['activo'] : $actual['activo'];
        // La contraseña solo se cambia si se envía; si no, se conserva el hash actual.
        $this->empleado->contrasena = isset($data['contrasena']) ? $data['contrasena'] : null;

        if (!$this->empleado->existsRol($this->empleado->id_rol)) {
            http_response_code(400);
            echo json_encode(["message" => "El rol indicado no existe."]);
            return;
        }

        if ($this->empleado->existsUsuario($this->empleado->usuario, $id)) {
            http_response_code(409);
            echo json_encode(["message" => "Ya existe otro empleado con ese usuario."]);
            return;
        }

        if ($this->empleado->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Empleado actualizado exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo actualizar el empleado."]);
        }
    }

    /**
     * Baja lógica (activo = 0). No borra el registro porque los pedidos
     * históricos lo referencian; solo deja de aparecer en listados.
     */
    public function delete() {
        $data = $this->getInputData();
        $id = isset($data['id_empleado']) ? $data['id_empleado'] : (isset($_GET['id']) ? $_GET['id'] : null);

        if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID de empleado no proporcionado o inválido."]);
            return;
        }

        $this->empleado->id_empleado = $id;

        if ($this->empleado->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Empleado desactivado exitosamente."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "No se pudo desactivar el empleado."]);
        }
    }
}
?>
