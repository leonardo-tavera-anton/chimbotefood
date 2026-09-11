<?php
// METODO POST
require_once '../../conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarMetodo('POST');
validarToken();

$input = leerEntrada();
$codigo = strtoupper(trim($input['codigo'] ?? ''));

if (!empty($codigo)) {
    $stmt = $conexion->prepare("SELECT * FROM cupones WHERE codigo = ? LIMIT 1");
    if (!$stmt) {
        responderError('No se pudo preparar la validación del cupón.', 500);
    }
    $stmt->bind_param("s", $codigo);
    if (!$stmt->execute()) {
        responderError('No se pudo validar el cupón.', 500);
    }
    $resultado = $stmt->get_result();

    if ($row = $resultado->fetch_assoc()) {
        echo json_encode([
            "status" => "success", 
            "message" => "Cupón válido", 
            "data" => $row
        ]);
    } else {
        responderError('El cupón no existe o es inválido.', 404);
    }
} else {
    responderError('Debe proporcionar un código de cupón.', 422);
}
?>