<?php
require_once '../../conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarMetodo('POST');
validarToken();

$input = leerEntrada();
$id_cliente = (int)($input['id_cliente'] ?? 0);
$id_restaurante = (int)($input['id_restaurante'] ?? 0);
$producto = trim($input['producto'] ?? '');
$subtotal = (float)($input['subtotal'] ?? 0);
$codigo = strtoupper(trim($input['codigo_cupon'] ?? $input['codigo'] ?? ''));

if ($id_cliente <= 0 || $id_restaurante <= 0 || $producto === '' || $subtotal <= 0 || $codigo === '') {
    responderError('Debe proporcionar cliente, restaurante, producto, subtotal y código de cupón.', 422);
}

$cliente = $conexion->prepare('SELECT distancia_km FROM clientes WHERE id_cliente = ?');
$restaurante = $conexion->prepare('SELECT tiempo_preparacion_base FROM restaurantes WHERE id_restaurante = ?');
if (!$cliente || !$restaurante) {
    responderError('No se pudo preparar la validación del pedido.', 500);
}

$cliente->bind_param('i', $id_cliente);
$restaurante->bind_param('i', $id_restaurante);
if (!$cliente->execute()) {
    responderError('No se pudo verificar el cliente.', 500);
}
$clienteResultado = $cliente->get_result();
if ($clienteResultado->num_rows === 0) {
    responderError('El cliente indicado no fue encontrado.', 404);
}
$clienteDatos = $clienteResultado->fetch_assoc();

if (!$restaurante->execute()) {
    responderError('No se pudo verificar el restaurante.', 500);
}
$restauranteResultado = $restaurante->get_result();
if ($restauranteResultado->num_rows === 0) {
    responderError('El restaurante indicado no fue encontrado.', 404);
}
$restauranteDatos = $restauranteResultado->fetch_assoc();

$cupon = $conexion->prepare('SELECT codigo, tipo_descuento, valor_descuento, monto_minimo FROM cupones WHERE codigo = ? AND activo = 1 LIMIT 1');
if (!$cupon) {
    responderError('No se pudo preparar la validación del cupón.', 500);
}
$cupon->bind_param('s', $codigo);
if (!$cupon->execute()) {
    responderError('No se pudo validar el cupón.', 500);
}

$cuponResultado = $cupon->get_result();
if ($cuponResultado->num_rows === 0) {
    responderError('El cupón no existe, está inactivo o no es válido.', 404);
}

$cuponDatos = $cuponResultado->fetch_assoc();
$montoMinimo = (float)$cuponDatos['monto_minimo'];
if ($subtotal < $montoMinimo) {
    responderError(sprintf('El cupón no aplica. El subtotal debe ser como mínimo S/ %.2f.', $montoMinimo), 422);
}

$descuento = $cuponDatos['tipo_descuento'] === 'porcentaje'
    ? $subtotal * ((float)$cuponDatos['valor_descuento'] / 100)
    : (float)$cuponDatos['valor_descuento'];
$descuento = min($subtotal, max(0, $descuento));
$montoTotal = $subtotal - $descuento;
$eta = (int)$restauranteDatos['tiempo_preparacion_base'] + ceil((float)$clienteDatos['distancia_km'] * 4) + 5;
$codigoAplicado = $cuponDatos['codigo'];

$stmt = $conexion->prepare('INSERT INTO pedidos (id_cliente, id_restaurante, producto, subtotal, codigo_cupon, descuento_aplicado, monto_total, eta_minutos_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    responderError('No se pudo preparar el registro del pedido.', 500);
}
$stmt->bind_param('iisdsddi', $id_cliente, $id_restaurante, $producto, $subtotal, $codigoAplicado, $descuento, $montoTotal, $eta);

if (!$stmt->execute()) {
    responderError('El cupón aplica, pero no se pudo guardar el pedido.', 500);
}

http_response_code(201);
echo json_encode([
    'status' => 'success',
    'http_code' => 201,
    'message' => 'Cupón aplicado y pedido guardado en el historial.',
    'data' => [
        'id_pedido' => $stmt->insert_id,
        'codigo_cupon' => $cuponDatos['codigo'],
        'subtotal' => $subtotal,
        'descuento_aplicado' => $descuento,
        'monto_total' => $montoTotal,
        'eta_minutos_total' => $eta
    ]
], JSON_UNESCAPED_UNICODE);
?>
