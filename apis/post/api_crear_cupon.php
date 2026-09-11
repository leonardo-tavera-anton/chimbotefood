<?php
//METODO POST
require_once '../../conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarMetodo('POST');
validarToken();

$input = leerEntrada();

$codigo = strtoupper(trim($input['codigo'] ?? ''));
$tipo = $input['tipo_descuento'] ?? 'porcentaje';
$valor = (float)($input['valor_descuento'] ?? 0);
$minimo = (float)($input['monto_minimo'] ?? 0);

if (!empty($codigo) && in_array($tipo, ['porcentaje', 'monto_fijo'], true) && $valor > 0 && $minimo >= 0) {
    $stmt = $conexion->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor_descuento, monto_minimo) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        responderError('No se pudo preparar la creación del cupón.', 500);
    }
    $stmt->bind_param("ssdd", $codigo, $tipo, $valor, $minimo);
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["status" => "success", "http_code" => 201, "message" => "Cupón creado correctamente", "id_cupon" => $stmt->insert_id]);
    } else {
        responderError($conexion->errno === 1062 ? 'El código de cupón ya existe.' : 'No se pudo crear el cupón.', $conexion->errno === 1062 ? 409 : 500);
    }
} else {
    responderError('Parámetros de cupón inválidos. Revise código, tipo, valor y monto mínimo.', 422);
}