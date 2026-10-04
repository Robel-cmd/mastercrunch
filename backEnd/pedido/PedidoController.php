<?php

require_once __DIR__.'/../config/Database.php';
require_once __DIR__.'/Pedido.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

class PedidoController {
    private $db;
    private $pedido;

    function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->pedido = new Pedido($this->db);
    }

    private function isAllowedEstado($estado) {
        return in_array(strtolower(trim((string) $estado)), Pedido::getEstadosPermitidos(), true);
    }

    private function isAllowedTipoPedido($tipoPedido) {
        return in_array(strtolower(trim((string) $tipoPedido)), Pedido::getTiposPedidoPermitidos(), true);
    }

    private function isValidEmpleadoId($idEmpleado) {
        return is_numeric($idEmpleado) && (int)$idEmpleado > 0;
    }

    public function create(){
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        $idEmpleado = isset($data->id_empleado) ? $data->id_empleado : null;
        $cliente = isset($data->cliente) && trim((string)$data->cliente) !== '' ? trim((string)$data->cliente) : 'Cliente no especificado';
        $estado = isset($data->estado) ? $data->estado : null;
        $tipoPedido = isset($data->tipo_pedido) ? $data->tipo_pedido : null;
        $observaciones = isset($data->observaciones) ? $data->observaciones : null;

        if (
            !$this->isValidEmpleadoId($idEmpleado) ||
            empty($estado) ||
            !$this->isAllowedEstado($estado) ||
            empty($tipoPedido) ||
            !$this->isAllowedTipoPedido($tipoPedido)
        ) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos inválidos. Debe enviar id_empleado válido, estado y tipo_pedido permitidos."
            ]);
            return;
        }

        $this->pedido->cliente = $cliente;
        $this->pedido->id_empleado = $idEmpleado;
        $this->pedido->estado = $estado;
        $this->pedido->tipo_pedido = $tipoPedido;
        $this->pedido->observaciones = $observaciones;

        if ($this->pedido->create()) {
            http_response_code(201);
            echo json_encode(["message" => "pedido creado existosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "problema con la creacion"]);
        }
    }

    public function read()
    {
        $stmt = $this->pedido->read();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar pedidos"]);
            return;
        }

        $num = $stmt->rowCount();

        if ($num > 0) {
            $pedidos = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedido = [
                    "id_pedido" => $row["id_pedido"],
                    "fecha_hora_pedido" => $row["fecha_hora_pedido"],
                    "fecha_hora_entrega" => $row["fecha_hora_entrega"],
                    "id_empleado" => $row["id_empleado"],
                    "estado" => $row["estado"],
                    "tipo_pedido" => $row["tipo_pedido"],
                    "observaciones" => $row["observaciones"]
                ];

                $pedidos[] = $pedido;
            }

            http_response_code(200);
            echo json_encode($pedidos);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron pedidos"]);
        }
    }

    public function readOne()
    {
        $stmt = $this->pedido->read();

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar pedidos"]);
            return;
        }

        $num = $stmt->rowCount();

        if ($num > 0) {
            $pedidos = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedido = [
                    "id_pedido" => $row["id_pedido"],
                    "fecha_hora_pedido" => $row["fecha_hora_pedido"],
                    "fecha_hora_entrega" => $row["fecha_hora_entrega"],
                    "id_empleado" => $row["id_empleado"],
                    "estado" => $row["estado"],
                    "tipo_pedido" => $row["tipo_pedido"],
                    "observaciones" => $row["observaciones"]
                ];

                $pedidos[] = $pedido;
            }

            http_response_code(200);
            echo json_encode($pedidos);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No se encontraron pedidos"]);
        }
    }

    public function update()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data)) {
            http_response_code(400);
            echo json_encode(["message" => "JSON inválido"]);
            return;
        }

        if (
            empty($data->id_pedido) ||
            !$this->isValidEmpleadoId($data->id_empleado ?? null) ||
            empty($data->estado) ||
            !$this->isAllowedEstado($data->estado) ||
            empty($data->tipo_pedido) ||
            !$this->isAllowedTipoPedido($data->tipo_pedido)
        ) {
            http_response_code(400);
            echo json_encode([
                "message" => "Datos incompletos o inválidos. Debe incluir id_pedido, id_empleado válido, estado y tipo_pedido permitidos."
            ]);
            return;
        }

        $this->pedido->id = $data->id_pedido;
        $this->pedido->cliente = isset($data->cliente) && trim((string)$data->cliente) !== '' ? trim((string)$data->cliente) : 'Cliente no especificado';
        $this->pedido->estado = $data->estado;
        $this->pedido->id_empleado = $data->id_empleado;
        $this->pedido->tipo_pedido = $data->tipo_pedido;
        $this->pedido->observaciones = isset($data->observaciones) ? $data->observaciones : null;

        if ($this->pedido->update()) {
            http_response_code(200);
            echo json_encode(["message" => "Pedido actualizado exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo actualizar el pedido"]);
        }
    }

    public function delete()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!is_object($data) || empty($data->id_pedido)) {
            http_response_code(400);
            echo json_encode(["message" => "Debe proporcionar el id del pedido"]);
            return;
        }

        $this->pedido->id = $data->id_pedido;

        if ($this->pedido->delete()) {
            http_response_code(200);
            echo json_encode(["message" => "Pedido eliminado exitosamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "No se pudo eliminar el pedido"]);
        }
    }
}