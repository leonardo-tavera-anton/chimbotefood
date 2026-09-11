<?php
require_once 'conexion.php';

// Procesamiento de formularios directos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_cupon') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo = $_POST['tipo_descuento'] ?? 'porcentaje';
        $valor = (float)($_POST['valor_descuento'] ?? 0);
        $minimo = (float)($_POST['monto_minimo'] ?? 0);

        if (!empty($codigo) && $valor > 0) {
            $stmt = $conexion->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor_descuento, monto_minimo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $codigo, $tipo, $valor, $minimo);
            $stmt->execute();
            header("Location: index.php");
            exit();
        }
    }
}

$cupones = $conexion->query("SELECT * FROM cupones ORDER BY id_cupon DESC");
$pedidos = $conexion->query("SELECT p.*, c.nombre_cliente, r.nombre_restaurante FROM pedidos p JOIN clientes c ON p.id_cliente = c.id_cliente JOIN restaurantes r ON p.id_restaurante = r.id_restaurante ORDER BY p.id_pedido DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChimboteFood SOA - Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1 { color: #1a252f; border-bottom: 2px solid #007bff; padding-bottom: 8px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e1e4e8; }
        .form-group { margin-bottom: 10px; }
        label { display: block; font-weight: bold; font-size: 13px; margin-bottom: 3px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>Panel de Administración - ChimboteFood SOA</h1>
    
    <div class="grid">
        <div class="card">
            <h3>Registrar Nuevo Cupón</h3>
            <form method="POST">
                <input type="hidden" name="action" value="crear_cupon">
                <div class="form-group"><label>Código:</label><input type="text" name="codigo" placeholder="EJ. PROMO2026" required></div>
                <div class="form-group">
                    <label>Tipo Descuento:</label>
                    <select name="tipo_descuento">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto_fijo">Monto Fijo (S/)</option>
                    </select>
                </div>
                <div class="form-group"><label>Valor:</label><input type="number" step="0.01" name="valor_descuento" required></div>
                <div class="form-group"><label>Monto Mínimo (S/):</label><input type="number" step="0.01" name="monto_minimo" value="0.00"></div>
                <button type="submit">Guardar Cupón</button>
            </form>
        </div>

        <div class="card">
            <h3>Estado del Ecosistema SOA</h3>
            <p><strong>Servicios Activos:</strong> 6 APIs REST (JSON)</p>
            <p><strong>Seguridad:</strong> Bearer Token (`chimbote_seguro_2026`)</p>
            <p><strong>Base de Datos:</strong> `restaurante_db` (MySQL)</p>
        </div>
    </div>

    <h2>Cupones Disponibles</h2>
    <table>
        <tr><th>ID</th><th>Código</th><th>Tipo</th><th>Valor</th><th>Monto Mínimo</th></tr>
        <?php while($c = $cupones->fetch_assoc()): ?>
            <tr>
                <td>#<?= $c['id_cupon']; ?></td>
                <td><strong><?= $c['codigo']; ?></strong></td>
                <td><?= $c['tipo_descuento']; ?></td>
                <td><?= $c['valor_descuento']; ?></td>
                <td>S/ <?= $c['monto_minimo']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Historial de Pedidos Procesados</h2>
    <table>
        <tr><th>ID</th><th>Cliente</th><th>Restaurante</th><th>Producto</th><th>Total</th><th>ETA</th></tr>
        <?php while($p = $pedidos->fetch_assoc()): ?>
            <tr>
                <td>#<?= $p['id_pedido']; ?></td>
                <td><?= $p['nombre_cliente']; ?></td>
                <td><?= $p['nombre_restaurante']; ?></td>
                <td><?= $p['producto']; ?></td>
                <td><strong>S/ <?= $p['monto_total']; ?></strong></td>
                <td><?= $p['eta_minutos_total']; ?> min</td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>