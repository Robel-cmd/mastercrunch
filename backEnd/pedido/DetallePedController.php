<?php

require_once __DIR__.'/../config/Database.php';
require_once __DIR__.'/DetallePedido.php';

class DetallePedController {
    private $db;
    private $detalle;

    function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->detalle = new DetallePedido($this->db);
    }

    private function isValidId($value) {
        return is_numeric($value) && (int)$value > 0;
    }

    private function isValidCantidad($value) {
        return is_numeric($value) && (int)$value > 0 && (string)(int)$value === (string)$value;
    }

    private function isValidPrecio($value) {
        return is_numeric($value) && (float)$value >= 0;
    }

    public function create(){
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        $idPedido = isset($data->id_pedido) ? $data->id_pedido : null;
        $idProducto = isset($data->id_producto) ? $data->id_producto : null;
        $idCombo = isset($data->id_combo) ? $data->id_combo : null;
        $cantidad = isset($data->cantidad) ? $data->cantidad : null;
        $precioUnitario = isset($data->precio_unitario) ? $data->precio_unitario : null;

        $hasProducto = $idProducto !== null && $idProducto !== '';
        $hasCombo = $idCombo !== null && $idCombo !== '';

        if (
            !$this->isValidId($idPedido) ||
            ($hasProducto === $hasCombo) ||
            !$this->isValidCantidad($cantidad) ||
            !$this->isValidPrecio($precioUnitario)
        ) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos inválidos. Debe enviar id_pedido válido, uno solo entre id_producto e id_combo, cantidad > 0 y precio_unitario >= 0."
            ]);
            return;
        }

        if ($hasProducto && !$this->isValidId($idProducto)) {
            http_response_code(400);
            echo json_encode(["message" => "id_producto inválido"]);
            return;
        }

        if ($hasCombo && !$this->isValidId($idCombo)) {
            http_response_code(400);
            echo json_encode(["message" => "id_combo inválido"]);
            return;
        }

        $this->detalle->id_pedido = $idPedido;
        $this->detalle->id_producto = $hasProducto ? $idProducto : null;
        $this->detalle->id_combo = $hasCombo ? $idCombo : null;
        $this->detalle->cantidad = (int)$cantidad;
        $this->detalle->precio_unitario = (float)$precioUnitario;

        if ($this->detalle->create()) {
            http_response_code(201);
            echo json_encode(["message" => "detalle creado exitosamente"]);
            return;
        }

        http_response_code(503);
        echo json_encode(["message" => "No se pudo crear el detalle"]);
    }

    public function read()
    {
        $stmt = $this->detalle->read();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error de base de datos al consultar detalles"]);
            return;
        }

        $num = $stmt->rowCount();

        if ($num > 0) {
            $detalles = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $detalle = [
                    "id_detalle" => $row["id_detalle"],
                    "id_pedido" => $row["id_pedido"],
                    "id_producto" => $row["id_producto"],
                    "id_combo" => $row["id_combo"],
                    "cantidad" => $row["cantidad"],
                    "precio_unitario" => $row["precio_unitario"]
                ];

                $detalles[] = $detalle;
            }

            http_response_code(200);
            echo json_encode($detalles);
            return;
        }

        http_response_code(404);
        echo json_encode(["message" => "No se encontraron detalles"]);
    }

    public function readOne()
    {
        $data = json_decode(file_get_contents("php://input"));
        $idDetalle = null;

        if (isset($_GET['id_detalle']) && $this->isValidId($_GET['id_detalle'])) {
            $idDetalle = (int) $_GET['id_detalle'];
        } elseif (isset($_GET['id']) && $this->isValidId($_GET['id'])) {
            $idDetalle = (int) $_GET['id'];
        } elseif (is_object($data) && isset($data->id_detalle) && $this->isValidId($data->id_detalle)) {
            $idDetalle = (int) $data->id_detalle;
        }

        if ($idDetalle === null) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar un id_detalle válido"]);
            return;
        }

        $stmt = $this->detalle->readOne($idDetalle);

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error de base de datos al consultar el detalle"]);
            return;
        }

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Detalle no encontrado"]);
            return;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $detalle = [
            "id_detalle" => $row["id_detalle"],
            "id_pedido" => $row["id_pedido"],
            "id_producto" => $row["id_producto"],
            "id_combo" => $row["id_combo"],
            "cantidad" => $row["cantidad"],
            "precio_unitario" => $row["precio_unitario"]
        ];

        http_response_code(200);
        echo json_encode($detalle);
    }

    public function update()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        if (!isset($data->id_detalle) || !$this->isValidId($data->id_detalle)) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar un id_detalle válido"]);
            return;
        }

        $idPedido = isset($data->id_pedido) ? $data->id_pedido : null;
        $idProducto = isset($data->id_producto) ? $data->id_producto : null;
        $idCombo = isset($data->id_combo) ? $data->id_combo : null;
        $cantidad = isset($data->cantidad) ? $data->cantidad : null;
        $precioUnitario = isset($data->precio_unitario) ? $data->precio_unitario : null;

        if (!$this->isValidId($idPedido)) {
            http_response_code(400);
            echo json_encode(["message" => "id_pedido inválido"]);
            return;
        }

        $hasProducto = $idProducto !== null && $idProducto !== '';
        $hasCombo = $idCombo !== null && $idCombo !== '';

        if (($hasProducto === $hasCombo) || !$this->isValidCantidad($cantidad) || !$this->isValidPrecio($precioUnitario)) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos inválidos. Debe definir uno solo entre id_producto e id_combo, cantidad > 0 y precio_unitario >= 0."
            ]);
            return;
        }

        if ($hasProducto && !$this->isValidId($idProducto)) {
            http_response_code(400);
            echo json_encode(["message" => "id_producto inválido"]);
            return;
        }

        if ($hasCombo && !$this->isValidId($idCombo)) {
            http_response_code(400);
            echo json_encode(["message" => "id_combo inválido"]);
            return;
        }

        $this->detalle->id_detalle = $data->id_detalle;
        $this->detalle->id_pedido = $idPedido;
        $this->detalle->id_producto = $hasProducto ? $idProducto : null;
        $this->detalle->id_combo = $hasCombo ? $idCombo : null;
        $this->detalle->cantidad = (int)$cantidad;
        $this->detalle->precio_unitario = (float)$precioUnitario;

        if ($this->detalle->update()) {
            http_response_code(200);
            echo json_encode(["message" => "detalle actualizado exitosamente"]);
            return;
        }

        http_response_code(503);
        echo json_encode(["message" => "No se pudo actualizar el detalle"]);
    }

    public function delete()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data) || !isset($data->id_detalle) || !$this->isValidId($data->id_detalle)) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar un id_detalle válido"]);
            return;
        }

        $this->detalle->id_detalle = $data->id_detalle;

        if ($this->detalle->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Detalle eliminado exitosamente"]);
            return;
        }

        http_response_code(503);
        echo json_encode(["message" => "No se pudo eliminar el detalle"]);
    }
}