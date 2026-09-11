<?php
//METODO POST
require_once '../../conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarMetodo('POST');
validarToken();

$input = leerEntrada();

$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_restaurante = (int)($input['id_restaurante'] ?? 0);
$producto = $input['producto'] ?? '';
$subtotal = (float)($input['subtotal'] ?? 0);
$monto_total = (float)($input['monto_total'] ?? 0);
$eta = (int)($input['eta_minutos_total'] ?? 30);

if ($id_cliente > 0 && $id_restaurante > 0 && $subtotal > 0 && $monto_total > 0 && $eta > 0 && trim($producto) !== '') {
    $cliente = $conexion->query("SELECT id_cliente FROM clientes WHERE id_cliente = $id_cliente");
    $restaurante = $conexion->query("SELECT id_restaurante FROM restaurantes WHERE id_restaurante = $id_restaurante");
    if (!$cliente || !$restaurante) {
        responderError('No se pudo verificar el cliente o restaurante.', 500);
    }
    if ($cliente->num_rows === 0) {
        responderError('El cliente indicado no fue encontrado.', 404);
    }
    if ($restaurante->num_rows === 0) {
        responderError('El restaurante indicado no fue encontrado.', 404);
    }

    $sql = "INSERT INTO pedidos (id_cliente, id_restaurante, producto, subtotal, monto_total, eta_minutos_total, fecha_pedido) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        responderError('No se pudo preparar la creación del pedido.', 500);
    }

    $stmt->bind_param("iisddi", $id_cliente, $id_restaurante, $producto, $subtotal, $monto_total, $eta);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success", 
            "http_code" => 201,
            "message" => "Pedido creado correctamente", 
            "id_pedido" => $stmt->insert_id
        ]);
    } else {
        responderError('No se pudo crear el pedido.', 500);
    }
} else {
    responderError('Parámetros de pedido inválidos o incompletos.', 422);
}
?>