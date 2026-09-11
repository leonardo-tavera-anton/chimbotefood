<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "restaurante_db";

$conexion = new mysqli($host, $user, $password, $dbname);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Error de BD: " . $conexion->connect_error]));
}

$conexion->set_charset("utf8mb4");

// Función reutilizable para la capa de seguridad SOA
function validarToken() {
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if ($token !== 'Bearer chimbote_seguro_2026') {
        http_response_code(401);
        echo json_encode([
            "status" => "error", 
            "message" => "Acceso no autorizado. Token inválido o ausente."
        ]);
        exit();
    }
}