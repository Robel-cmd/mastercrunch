<?php

require_once __DIR__.'/../config/Database.php';
require_once __DIR__.'/Orden.php';

class OrdenController {
    private $db;
    private $orden;

    function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->orden = new Orden($this->db);
    }

    private function isValidId($value) {
        return is_numeric($value) && (int)$value > 0;
    }

    private function isValidEstado($estado) {
        return in_array(strtolower(trim((string)$estado)), ['pendiente', 'preparacion', 'entregado', 'cancelado'], true);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        $idPedido = isset($data->id_pedido) ? $data->id_pedido : null;
        $cantidad = isset($data->cantidad) ? $data->cantidad : null;
        $estadoCocina = isset($data->estado_cocina) ? $data->estado_cocina : null;

        if (!$this->isValidId($idPedido) || !is_numeric($cantidad) || (int)$cantidad <= 0 || !$this->isValidEstado($estadoCocina)) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos inválidos. Debe enviar id_pedido válido, cantidad > 0 y estado_cocina permitido."
            ]);
            return;
        }

        $this->orden->id_pedido = $idPedido;
        $this->orden->cantidad = (int)$cantidad;
        $this->orden->estado_cocina = $estadoCocina;

        if ($this->orden->create()) {
            http_response_code(201);
            echo json_encode(["message" => "orden de cocina creada exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "problema con la creacion de la orden"]);
        }
    }

    public function entregar() {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data) || !$this->isValidId($data->id_orden_cocina ?? null)) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar el id de la orden"]);
            return;
        }

        $this->orden->id_orden_cocina = $data->id_orden_cocina;
        $this->orden->estado_cocina = isset($data->estado_cocina) ? $data->estado_cocina : 'entregado';
        $this->orden->hora_entrega = !empty($data->hora_entrega) ? $data->hora_entrega : date('Y-m-d H:i:s');

        if ($this->orden->entregar()) {
            http_response_code(200);
            echo json_encode(["message" => "Orden marcada como entregada"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo marcar como entregada la orden"]);
        }
    }

    public function read() {
        $stmt = $this->orden->read();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar órdenes de cocina"]);
            return;
        }

        $num = $stmt->rowCount();

        if ($num > 0) {
            $ordenes = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orden = [
                    "id_orden_cocina" => $row["id_orden_cocina"],
                    "id_pedido" => $row["id_pedido"],
                    "cantidad" => $row["cantidad"],
                    "estado_cocina" => $row["estado_cocina"],
                    "hora_recibido" => $row["hora_recibido"],
                    "hora_entrega" => $row["hora_entrega"]
                ];

                $ordenes[] = $orden;
            }

            http_response_code(200);
            echo json_encode($ordenes);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron órdenes de cocina"]);
        }
    }

    public function readOne() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar el id de la orden"]);
            return;
        }

        $this->orden->id_orden_cocina = $id;
        $stmt = $this->orden->readOne();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar la orden de cocina"]);
            return;
        }

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $orden = [
                "id_orden_cocina" => $row["id_orden_cocina"],
                "id_pedido" => $row["id_pedido"],
                "cantidad" => $row["cantidad"],
                "estado_cocina" => $row["estado_cocina"],
                "hora_recibido" => $row["hora_recibido"],
                "hora_entrega" => $row["hora_entrega"]
            ];

            http_response_code(200);
            echo json_encode($orden);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontró la orden de cocina"]);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        if (
            !$this->isValidId($data->id_orden_cocina ?? null) ||
            !$this->isValidId($data->id_pedido ?? null) ||
            !is_numeric($data->cantidad ?? null) ||
            (int)($data->cantidad ?? 0) <= 0 ||
            !$this->isValidEstado($data->estado_cocina ?? null)
        ) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos incompletos o inválidos. Debe incluir id_orden_cocina, id_pedido válido, cantidad > 0 y estado_cocina permitido."
            ]);
            return;
        }

        $this->orden->id_orden_cocina = $data->id_orden_cocina;
        $this->orden->id_pedido = $data->id_pedido;
        $this->orden->cantidad = (int)$data->cantidad;
        $this->orden->estado_cocina = $data->estado_cocina;

        if (!empty($data->hora_recibido)) {
            $this->orden->hora_recibido = $data->hora_recibido;
        }

        if (!empty($data->hora_entrega)) {
            $this->orden->hora_entrega = $data->hora_entrega;
        }

        if ($this->orden->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Orden de cocina actualizada exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar la orden de cocina"]);
        }
    }

    public function delete() {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data) || !$this->isValidId($data->id_orden_cocina ?? null)) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar el id de la orden"]);
            return;
        }

        $this->orden->id_orden_cocina = $data->id_orden_cocina;

        if ($this->orden->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Orden de cocina eliminada exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar la orden de cocina"]);
        }
    }
}
