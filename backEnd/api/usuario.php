<?php

// IMPORTANTE (Fase 2): cuando exista el login, aquí se agregará al inicio:
//   require_once __DIR__ . '/../config/Sesion.php';
//   Sesion::requerirNivel(Rol::NIVEL_ADMIN);   // solo el administrador gestiona usuarios
// por ahora el CRUD queda abierto, igual que el resto de la API.

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../usuario/EmpleadoController.php';
require_once __DIR__ . '/../usuario/RolController.php';

$method = $_SERVER['REQUEST_METHOD'];

// Si es POST y viene _method, lo sobreescribimos (compatibilidad con formularios)
if ($method == 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// Determinar el recurso solicitado: por defecto 'empleado'
$resource = isset($_GET['entity']) ? $_GET['entity'] : 'empleado';

switch ($resource) {
    case 'empleado':
        $controller = new EmpleadoController();
        break;
    case 'rol':
        $controller = new RolController();
        break;
    default:
        http_response_code(400);
        echo json_encode(["message" => "Recurso no soportado. Use entity=empleado o entity=rol."]);
        exit();
}

// Ejecutar la acción según el método HTTP
switch ($method) {
    case 'POST':
        $controller->create();
        break;
    case 'GET':
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $controller->readOne();
        } else {
            $controller->read();
        }
        break;
    case 'PUT':
        $controller->update();
        break;
    case 'DELETE':
        $controller->delete();
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido."]);
        break;
}
?>
