<?php

require_once __DIR__.'/../config/Database.php';
require_once __DIR__.'/Pedido.php';
require_once __DIR__.'/DetallePedido.php';

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

    /**
     * Crea un pedido y (opcionalmente) sus detalles en una sola transacción.
     *
     * JSON de entrada:
     * {
     *   "id_empleado": 1,
     *   "cliente": "Juan Pérez",
     *   "estado": "pendiente",
     *   "tipo_pedido": "local",
     *   "observaciones": "Sin cebolla",
     *   "detalles": [                       // opcional
     *     { "id_producto": 1, "cantidad": 2 },
     *     { "id_combo": 2, "cantidad": 1 }
     *   ]
     * }
     *
     * El precio_unitario de cada detalle lo fija el servidor (no se acepta del cliente).
     * Si "detalles" no viene, se comporta como antes (solo cabecera).
     */
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

        $detalles = (isset($data->detalles) && is_array($data->detalles)) ? $data->detalles : null;

        // Validar todos los detalles ANTES de abrir la transacción.
        if ($detalles !== null) {
            if (count($detalles) === 0) {
                http_response_code(400);
                echo json_encode(["message" => "El arreglo 'detalles' no puede estar vacío. Omítalo si solo desea crear la cabecera."]);
                return;
            }

            foreach ($detalles as $index => $detalle) {
                $error = $this->validarDetalle($detalle);
                if ($error !== null) {
                    http_response_code(400);
                    echo json_encode(["message" => "Detalle " . ($index + 1) . ": " . $error]);
                    return;
                }
            }
        }

        $this->pedido->cliente = $cliente;
        $this->pedido->id_empleado = $idEmpleado;
        $this->pedido->estado = $estado;
        $this->pedido->tipo_pedido = $tipoPedido;
        $this->pedido->observaciones = $observaciones;

        // Sin detalles: comportamiento simple de siempre.
        if ($detalles === null) {
            if ($this->pedido->create()) {
                http_response_code(201);
                echo json_encode(["message" => "pedido creado existosamente"]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "problema con la creacion"]);
            }
            return;
        }

        // Con detalles: TODO dentro de una transacción (todo o nada).
        $detalle = new DetallePedido($this->db);

        try {
            $this->db->beginTransaction();

            if (!$this->pedido->create()) {
                $this->db->rollBack();
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear el pedido."]);
                return;
            }

            $idPedido = (int)$this->db->lastInsertId();

            foreach ($detalles as $d) {
                $idProducto = is_array($d) ? (isset($d['id_producto']) ? $d['id_producto'] : null)
                                           : (isset($d->id_producto) ? $d->id_producto : null);
                $idCombo = is_array($d) ? (isset($d['id_combo']) ? $d['id_combo'] : null)
                                        : (isset($d->id_combo) ? $d->id_combo : null);
                $cantidad = is_array($d) ? (isset($d['cantidad']) ? $d['cantidad'] : null)
                                         : (isset($d->cantidad) ? $d->cantidad : null);

                $hasProducto = $idProducto !== null && $idProducto !== '';

                $detalle->id_pedido = $idPedido;
                $detalle->id_producto = $hasProducto ? $idProducto : null;
                $detalle->id_combo = $hasProducto ? null : $idCombo;
                $detalle->cantidad = (int)$cantidad;

                if (!$detalle->create()) {
                    $this->db->rollBack();
                    http_response_code(400);
                    echo json_encode([
                        "message" => "No se pudo guardar un detalle (producto o combo inexistente). Se revirtió el pedido completo.",
                        "id_pedido" => $idPedido
                    ]);
                    return;
                }
            }

            $this->db->commit();

            http_response_code(201);
            echo json_encode([
                "message" => "Pedido y " . count($detalles) . " detalle(s) creados exitosamente.",
                "id_pedido" => $idPedido,
                "detalles_creados" => count($detalles)
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error creando pedido transaccional: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Error interno al crear el pedido. Se revirtieron los cambios."]);
        }
    }

    /**
     * Valida un elemento del arreglo 'detalles'.
     * Devuelve null si es válido o el mensaje de error si no lo es.
     * El precio NO se valida aquí: lo resuelve el servidor.
     */
    private function validarDetalle($detalle) {
        if (!is_object($detalle) && !is_array($detalle)) {
            return "formato inválido.";
        }

        $get = function($clave) use ($detalle) {
            if (is_array($detalle)) {
                return isset($detalle[$clave]) ? $detalle[$clave] : null;
            }
            return isset($detalle->$clave) ? $detalle->$clave : null;
        };

        $idProducto = $get('id_producto');
        $idCombo = $get('id_combo');
        $cantidad = $get('cantidad');

        $hasProducto = $idProducto !== null && $idProducto !== '';
        $hasCombo = $idCombo !== null && $idCombo !== '';

        if ($hasProducto === $hasCombo) {
            return "debe indicar uno solo entre id_producto e id_combo.";
        }

        $idItem = $hasProducto ? $idProducto : $idCombo;
        if (!is_numeric($idItem) || (int)$idItem <= 0) {
            return ($hasProducto ? "id_producto" : "id_combo") . " inválido.";
        }

        if (!DetallePedido::isValidCantidadValue($cantidad)) {
            return "cantidad debe ser un entero >= 1.";
        }

        return null;
    }

    /**
     * Listado de pedidos con filtros y paginación.
     * Parámetros GET opcionales:
     *   estado, tipo_pedido, cliente, fecha_desde, fecha_hasta (YYYY-MM-DD),
     *   page (>=1, por defecto 1), limit (1-100, por defecto 20).
     */
    public function read()
    {
        $filtros = [
            'estado'      => isset($_GET['estado']) ? $_GET['estado'] : null,
            'tipo_pedido' => isset($_GET['tipo_pedido']) ? $_GET['tipo_pedido'] : null,
            'cliente'     => isset($_GET['cliente']) ? $_GET['cliente'] : null,
            'fecha_desde' => isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null,
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null,
        ];

        // Validar filtros enum: si mandan algo raro, 400 en vez de lista vacía silenciosa.
        if (!empty($filtros['estado']) && !$this->isAllowedEstado($filtros['estado'])) {
            http_response_code(400);
            echo json_encode(["message" => "estado inválido. Use: " . implode(', ', Pedido::getEstadosPermitidos()) . "."]);
            return;
        }
        if (!empty($filtros['tipo_pedido']) && !$this->isAllowedTipoPedido($filtros['tipo_pedido'])) {
            http_response_code(400);
            echo json_encode(["message" => "tipo_pedido inválido. Use: " . implode(', ', Pedido::getTiposPedidoPermitidos()) . "."]);
            return;
        }

        $pagina = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($pagina < 1) $pagina = 1;

        $limite = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
        if ($limite < 1) $limite = 20;
        if ($limite > 100) $limite = 100;

        $filtros['limit'] = $limite;
        $filtros['offset'] = ($pagina - 1) * $limite;

        $total = $this->pedido->count($filtros);
        if ($total === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar pedidos"]);
            return;
        }

        $stmt = $this->pedido->readPaginated($filtros);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error al consultar pedidos"]);
            return;
        }

        $pedidos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pedidos[] = $row;
        }

        $totalPaginas = ($limite > 0) ? (int)ceil($total / $limite) : 0;

        http_response_code(200);
        echo json_encode([
            "registros" => $pedidos,
            "paginacion" => [
                "pagina"       => $pagina,
                "por_pagina"   => $limite,
                "total"        => $total,
                "total_paginas"=> $totalPaginas
            ]
        ]);
    }

    public function readOne()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!is_numeric($id) || (int)$id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "ID inválido."]);
            return;
        }

        $pedido = $this->pedido->readOne($id);

        if ($pedido === false) {
            http_response_code(404);
            echo json_encode(["message" => "Pedido no encontrado."]);
            return;
        }

        http_response_code(200);
        echo json_encode($pedido);
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