<?php

require_once __DIR__.'/../cocina/OrdenController.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['entity']) ? $_GET['entity'] : 'orden';

switch ($resource) {
    case 'orden':
    case 'cocina':
        $controller = new OrdenController();
        break;
    default:
        http_response_code(400);
        echo json_encode(["message" => "Recurso no soportado."]);
        exit();
}

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
        if (isset($_GET['action']) && $_GET['action'] == 'entregar') {
            $controller->entregar();
        } else {
            $controller->update();
        }
        break;
    case 'DELETE':
        $controller->delete();
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido."]);
        break;
}
