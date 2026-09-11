<?php
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarToken();

$res = $conexion->query("SELECT * FROM cupones WHERE activo = 1 ORDER BY id_cupon DESC");
echo json_encode(["status" => "success", "data" => $res->fetch_all(MYSQLI_ASSOC)]);