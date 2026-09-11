<?php
$codigoHttp = (int)($_SERVER['REDIRECT_STATUS'] ?? 500);
$mensajes = [
    400 => ['SOLICITUD_INCORRECTA', 'La solicitud no es válida.'],
    401 => ['NO_AUTORIZADO', 'No tiene autorización para acceder a este recurso.'],
    403 => ['ACCESO_PROHIBIDO', 'El acceso a este recurso está prohibido.'],
    404 => ['RECURSO_NO_ENCONTRADO', 'El endpoint solicitado no fue encontrado. Verifique la URL y el nombre del archivo.'],
    405 => ['METODO_NO_PERMITIDO', 'El método HTTP utilizado no está permitido para este recurso.'],
    409 => ['CONFLICTO', 'La solicitud entra en conflicto con el estado actual del recurso.'],
    422 => ['ENTIDAD_NO_PROCESABLE', 'Los datos enviados no pueden ser procesados.'],
    500 => ['ERROR_INTERNO_DEL_SERVIDOR', 'Ocurrió un error interno del servidor.']
];

if (!isset($mensajes[$codigoHttp])) {
    $codigoHttp = 500;
}

http_response_code($codigoHttp);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'error',
    'http_code' => $codigoHttp,
    'error' => $mensajes[$codigoHttp][0],
    'message' => $mensajes[$codigoHttp][1]
], JSON_UNESCAPED_UNICODE);
exit;
?>
