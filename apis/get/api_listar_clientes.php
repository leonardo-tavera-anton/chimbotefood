<?php
//METODO GET
require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');
validarToken();

$res = $conexion->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
echo json_encode(["status" => "success", "data" => $res->fetch_all(MYSQLI_ASSOC)]);