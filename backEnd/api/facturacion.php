<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../facturacion/FacturaController.php';
require_once __DIR__ . '/../facturacion/MetodoPagoController.php';

$method = $_SERVER['REQUEST_METHOD'];

// Si es POST y viene _method, lo sobreescribimos
if ($method == 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// Determinar el recurso solicitado: por defecto 'factura'
$resource = isset($_GET['entity']) ? $_GET['entity'] : 'factura';

switch ($resource) {
    case 'factura':
        $controller = new FacturaController();
        break;
    case 'metodo_pago':
        $controller = new MetodoPagoController();
        break;
    default:
        http_response_code(400);
        echo json_encode(["message" => "Recurso no soportado."]);
        exit();
}

// Ejecutar la acción según el método HTTP
switch ($method) {
    case 'POST':
        if ($resource !== 'factura') {
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido para este recurso."]);
            break;
        }
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
        if ($resource !== 'factura') {
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido para este recurso."]);
            break;
        }
        $controller->update();
        break;
    case 'DELETE':
        if ($resource !== 'factura') {
            http_response_code(405);
            echo json_encode(["message" => "Método no permitido para este recurso."]);
            break;
        }
        $controller->delete();
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido."]);
        break;
}
?>