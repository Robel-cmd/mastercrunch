-- *************** TRIGGER *****************

-- Trigger para poder traer la hora

DELIMITER //

CREATE TRIGGER trg_ordenes_cocina_entregado
BEFORE UPDATE ON ordenes_cocina
FOR EACH ROW
BEGIN
    -- Cambiamos el estado de New a entregado si el estado de Old es diferente de entregado
    IF NEW.estado_cocina = 'entregado' AND OLD.estado_cocina <> 'entregado' THEN
        -- Asignamos la hora actual
        SET NEW.hora_entrega = NOW();
    END IF;
END;

//
DELIMITER ;



-- ***************** PROCEDEMIENTOS *********************
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_registrar_venta`$$

CREATE PROCEDURE `sp_registrar_venta`(
    IN p_id_pedido   INT,
    IN p_id_producto INT,      -- NULL si es combo
    IN p_id_combo    INT,      -- NULL si es producto
    IN p_cantidad    INT,
    IN p_precio      DECIMAL(10,2),
    IN p_fecha       DATE
)
BEGIN
    -- 1. Insertar el detalle del pedido
    INSERT INTO pedidos_detalle
        (id_pedido, id_producto, id_combo, cantidad, precio_unitario, fecha)
    VALUES
        (p_id_pedido, p_id_producto, p_id_combo, p_cantidad, p_precio, p_fecha);

    -- 2. Si es PRODUCTO individual → descontar su meta
    IF p_id_producto IS NOT NULL THEN
        UPDATE metas_diarias
        SET ventas_reales = ventas_reales + p_cantidad
        WHERE id_producto = p_id_producto
          AND fecha = p_fecha;
    END IF;

    -- 3. Si es COMBO → descontar la meta de TODOS sus productos
    IF p_id_combo IS NOT NULL THEN
        UPDATE metas_diarias md
        JOIN combo_detalle cd ON cd.id_producto = md.id_producto
        SET md.ventas_reales = md.ventas_reales + (cd.cantidad * p_cantidad)
        WHERE cd.id_combo = p_id_combo
          AND md.fecha = p_fecha;
    END IF;
END$$

DELIMITER ;