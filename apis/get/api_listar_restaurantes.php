<?php
require_once '../../conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarMetodo('GET');
validarToken();

$res = $conexion->query("SELECT * FROM restaurantes");
if (!$res) {
	responderError('No se pudieron consultar los restaurantes.', 500);
}
echo json_encode(["status" => "success", "data" => $res->fetch_all(MYSQLI_ASSOC)]);
?>