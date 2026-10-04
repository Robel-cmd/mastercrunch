<?php

require_once __DIR__.'/../pedido/PedidoController.php';
require_once __DIR__.'/../pedido/DetallePedController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// por defecto producto en caso de que no hay parametro | entidad
$resource = isset($_GET['entity']) ? $_GET['entity'] : 'pedido';

// 
if (isset($_GET['DetallePedido']) && $_GET['DetallePedido'] == 'true') {
    $resource = 'DetallePedido';
}

switch ($resource) {
    case 'pedido': // 
        $controller = new PedidoController();
        break;
    case 'DetallePedido':
        $controller = new DetallePedController();
        break;
    default:
        http_response_code(400);
        echo json_encode(["message" => "Recurso no soportado."]);
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
