<?php
//METODO POST
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarToken();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$codigo = strtoupper(trim($input['codigo'] ?? ''));
$tipo = $input['tipo_descuento'] ?? 'porcentaje';
$valor = (float)($input['valor_descuento'] ?? 0);
$minimo = (float)($input['monto_minimo'] ?? 0);

if (!empty($codigo) && $valor > 0) {
    $stmt = $conexion->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor_descuento, monto_minimo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $codigo, $tipo, $valor, $minimo);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Cupón creado correctamente", "id_cupon" => $stmt->insert_id]);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $conexion->error]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Parámetros de cupón inválidos"]);
}