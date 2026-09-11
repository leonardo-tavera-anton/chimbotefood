<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$password = "";
$dbname = "restaurante_db";

$conexion = new mysqli($host, $user, $password, $dbname);

if ($conexion->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(["status" => "error", "http_code" => 500, "error" => "DATABASE_ERROR", "message" => "Error interno del servidor"]));
}

$conexion->set_charset("utf8mb4");

// Función reutilizable para la capa de seguridad SOA
function validarToken() {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($token === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (hash_equals('Bearer chimbote_seguro_2026', trim($token))) {
        return;
    }

    if ($token !== 'Bearer chimbote_seguro_2026') {
        responderError('No autorizado. Token inválido o ausente.', 401);
    }
}

// Función reutilizable para unificar las respuestas de error JSON
function responderError($mensaje, $codigoHttp = 400) {
    http_response_code($codigoHttp);
    $mensajes = [
        400 => 'Solicitud incorrecta',
        401 => 'No autorizado',
        403 => 'Prohibido',
        404 => 'Recurso no encontrado',
        405 => 'Método no permitido',
        409 => 'Conflicto',
        422 => 'Entidad no procesable',
        500 => 'Error interno del servidor'
    ];
    $errores = [
        400 => 'SOLICITUD_INCORRECTA',
        401 => 'NO_AUTORIZADO',
        403 => 'ACCESO_PROHIBIDO',
        404 => 'RECURSO_NO_ENCONTRADO',
        405 => 'METODO_NO_PERMITIDO',
        409 => 'CONFLICTO',
        422 => 'ENTIDAD_NO_PROCESABLE',
        500 => 'ERROR_INTERNO_DEL_SERVIDOR'
    ];
    echo json_encode([
        "status" => "error",
        "http_code" => $codigoHttp,
        "error" => $errores[$codigoHttp] ?? 'ERROR',
        "message" => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function validarMetodo($metodoEsperado) {
    if ($_SERVER['REQUEST_METHOD'] !== $metodoEsperado) {
        header('Allow: ' . $metodoEsperado);
        responderError("Este endpoint solo acepta el método $metodoEsperado.", 405);
    }
}

function leerEntrada() {
    $contenido = file_get_contents('php://input');
    if (!empty($_POST)) {
        return $_POST;
    }
    if ($contenido === '' || trim($contenido) === '') {
        return [];
    }

    $entrada = json_decode($contenido, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($entrada)) {
        responderError('El cuerpo debe contener un JSON válido.', 400);
    }
    return $entrada;
}
?>