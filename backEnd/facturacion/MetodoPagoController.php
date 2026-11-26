<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/MetodoPago.php';

class MetodoPagoController {
    private $db;
    private $metodoPago;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->metodoPago = new MetodoPago($this->db);
    }

    // GET – Listar todos los métodos de pago activos
    public function read() {
        $stmt = $this->metodoPago->read();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            http_response_code(200);
            echo json_encode(["registros" => $rows]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No hay métodos de pago activos."]);
        }
    }

    // GET – Leer un método de pago por ID
    public function readOne() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }
        $metodo = $this->metodoPago->readOne($id);
        if ($metodo) {
            http_response_code(200);
            echo json_encode($metodo);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Método de pago no encontrado."]);
        }
    }
}
?>