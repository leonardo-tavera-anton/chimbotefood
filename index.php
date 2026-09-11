<?php
require_once 'conexion.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Procesamiento de acciones (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cupón: Crear o Actualizar
    if ($action === 'guardar_cupon') {
        $id = (int)($_POST['id_cupon'] ?? 0);
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo = $_POST['tipo_descuento'] ?? 'porcentaje';
        $valor = (float)($_POST['valor_descuento'] ?? 0);
        $minimo = (float)($_POST['monto_minimo'] ?? 0);

        if (!empty($codigo) && $valor > 0) {
            if ($id > 0) {
                $stmt = $conexion->prepare("UPDATE cupones SET codigo=?, tipo_descuento=?, valor_descuento=?, monto_minimo=? WHERE id_cupon=?");
                $stmt->bind_param("ssddi", $codigo, $tipo, $valor, $minimo, $id);
            } else {
                $stmt = $conexion->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor_descuento, monto_minimo) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssdd", $codigo, $tipo, $valor, $minimo);
            }
            $stmt->execute();
        }
        header("Location: index.php");
        exit();
    }

    // 2. Cliente: Crear, Actualizar o Generar Aleatorio
    if ($action === 'guardar_cliente') {
        $id = (int)($_POST['id_cliente'] ?? 0);
        $nombre = trim($_POST['nombre_cliente'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $distancia = (float)($_POST['distancia_km'] ?? 0);

        if (!empty($nombre)) {
            if ($id > 0) {
                $stmt = $conexion->prepare("UPDATE clientes SET nombre_cliente=?, direccion=?, distancia_km=? WHERE id_cliente=?");
                $stmt->bind_param("ssdi", $nombre, $direccion, $distancia, $id);
            } else {
                $stmt = $conexion->prepare("INSERT INTO clientes (nombre_cliente, direccion, distancia_km) VALUES (?, ?, ?)");
                $stmt->bind_param("ssd", $nombre, $direccion, $distancia);
            }
            $stmt->execute();
        }
        header("Location: index.php");
        exit();
    }

    if ($action === 'crear_cliente_aleatorio') {
        $nombres = ['Carlos Pérez', 'Lucía Gómez', 'Mateo Silva', 'Valeria Rojas', 'Diego Torres', 'Andrea Ramos'];
        $calles = ['Av. Pardo', 'Jr. Espinar', 'Urb. Bellamar', 'Av. José Gálvez', 'Nuevo Chimbote Mz. A'];
        $nombre = $nombres[array_rand($nombres)] . ' ' . rand(10, 99);
        $direccion = $calles[array_rand($calles)] . ' #' . rand(100, 900);
        $distancia = round(rand(10, 60) / 10, 2);

        $stmt = $conexion->prepare("INSERT INTO clientes (nombre_cliente, direccion, distancia_km) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $nombre, $direccion, $distancia);
        $stmt->execute();
        header("Location: index.php");
        exit();
    }

    // 3. Restaurante: Crear, Actualizar o Generar Aleatorio
    if ($action === 'guardar_restaurante') {
        $id = (int)($_POST['id_restaurante'] ?? 0);
        $nombre = trim($_POST['nombre_restaurante'] ?? '');
        $prep = (int)($_POST['tiempo_preparacion_base'] ?? 15);

        if (!empty($nombre)) {
            if ($id > 0) {
                $stmt = $conexion->prepare("UPDATE restaurantes SET nombre_restaurante=?, tiempo_preparacion_base=? WHERE id_restaurante=?");
                $stmt->bind_param("sii", $nombre, $prep, $id);
            } else {
                $stmt = $conexion->prepare("INSERT INTO restaurantes (nombre_restaurante, tiempo_preparacion_base) VALUES (?, ?)");
                $stmt->bind_param("si", $nombre, $prep);
            }
            $stmt->execute();
        }
        header("Location: index.php");
        exit();
    }

    if ($action === 'crear_restaurante_aleatorio') {
        $nombres_rest = ['Brasa Chimbote', 'El Pez Dorado', 'Broaster King', 'Chifa Pekín Express', 'Antojitos Criollos'];
        $nombre = $nombres_rest[array_rand($nombres_rest)] . ' ' . rand(1, 50);
        $prep = rand(10, 30);

        $stmt = $conexion->prepare("INSERT INTO restaurantes (nombre_restaurante, tiempo_preparacion_base) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre, $prep);
        $stmt->execute();
        header("Location: index.php");
        exit();
    }

    // 4. Eliminaciones
    if ($action === 'eliminar') {
        $tipo = $_POST['tipo'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            if ($tipo === 'cliente') {
                $conexion->query("DELETE FROM clientes WHERE id_cliente = $id");
            } elseif ($tipo === 'restaurante') {
                $conexion->query("DELETE FROM restaurantes WHERE id_restaurante = $id");
            } elseif ($tipo === 'cupon') {
                $conexion->query("DELETE FROM cupones WHERE id_cupon = $id");
            }
        }
        header("Location: index.php");
        exit();
    }

    // 5. Simular Pedido
    if ($action === 'crear_pedido_rapido') {
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_restaurante = (int)($_POST['id_restaurante'] ?? 0);
        $producto = trim($_POST['producto'] ?? 'Plato Combinado Especial');
        $subtotal = (float)($_POST['subtotal'] ?? 45.00);
        $codigo_cupon = strtoupper(trim($_POST['codigo_cupon'] ?? ''));

        if ($id_cliente > 0 && $id_restaurante > 0 && $subtotal > 0) {
            $cli = $conexion->query("SELECT distancia_km FROM clientes WHERE id_cliente = $id_cliente")->fetch_assoc();
            $rest = $conexion->query("SELECT tiempo_preparacion_base FROM restaurantes WHERE id_restaurante = $id_restaurante")->fetch_assoc();

            if ($cli && $rest) {
                $distancia = (float)$cli['distancia_km'];
                $tiempo_prep = (int)$rest['tiempo_preparacion_base'];
                $eta_total = $tiempo_prep + ceil($distancia * 4) + 5;

                $descuento = 0.00;
                $cupon_aplicado = NULL;

                if (!empty($codigo_cupon)) {
                    $stmt_c = $conexion->prepare("SELECT * FROM cupones WHERE codigo = ? AND activo = 1");
                    $stmt_c->bind_param("s", $codigo_cupon);
                    $stmt_c->execute();
                    $res_c = $stmt_c->get_result();

                    if ($res_c->num_rows > 0) {
                        $cup = $res_c->fetch_assoc();
                        if ($subtotal >= (float)$cup['monto_minimo']) {
                            $cupon_aplicado = $cup['codigo'];
                            $descuento = ($cup['tipo_descuento'] === 'porcentaje') 
                                ? $subtotal * ((float)$cup['valor_descuento'] / 100) 
                                : (float)$cup['valor_descuento'];
                        }
                    }
                }

                $monto_total = max(0, $subtotal - $descuento);

                $stmt = $conexion->prepare("INSERT INTO pedidos (id_cliente, id_restaurante, producto, subtotal, codigo_cupon, descuento_aplicado, monto_total, eta_minutos_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdsddi", $id_cliente, $id_restaurante, $producto, $subtotal, $cupon_aplicado, $descuento, $monto_total, $eta_total);
                $stmt->execute();
            }
        }
        header("Location: index.php");
        exit();
    }
}

// Obtener datos para edición si se solicita por GET
$edit_cupon = null;
$edit_cliente = null;
$edit_restaurante = null;

if (isset($_GET['edit']) && isset($_GET['tipo'])) {
    $id = (int)$_GET['edit'];
    $tipo = $_GET['tipo'];
    if ($tipo === 'cupon') {
        $res = $conexion->query("SELECT * FROM cupones WHERE id_cupon = $id");
        $edit_cupon = $res->fetch_assoc();
    } elseif ($tipo === 'cliente') {
        $res = $conexion->query("SELECT * FROM clientes WHERE id_cliente = $id");
        $edit_cliente = $res->fetch_assoc();
    } elseif ($tipo === 'restaurante') {
        $res = $conexion->query("SELECT * FROM restaurantes WHERE id_restaurante = $id");
        $edit_restaurante = $res->fetch_assoc();
    }
}

// Consultas generales
$cupones = $conexion->query("SELECT * FROM cupones ORDER BY id_cupon DESC");
$clientes = $conexion->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
$restaurantes = $conexion->query("SELECT * FROM restaurantes ORDER BY id_restaurante DESC");
$pedidos = $conexion->query("SELECT p.*, c.nombre_cliente, r.nombre_restaurante FROM pedidos p JOIN clientes c ON p.id_cliente = c.id_cliente JOIN restaurantes r ON p.id_restaurante = r.id_restaurante ORDER BY p.id_pedido DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChimboteFood SOA - CRUD GRUPO E</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1 { color: #1a252f; border-bottom: 2px solid #007bff; padding-bottom: 8px; margin-top: 0; }
        h2 { color: #2c3e50; margin-top: 30px; font-size: 1.25rem; border-left: 4px solid #007bff; padding-left: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .card { background: #f8f9fa; padding: 18px; border-radius: 8px; border: 1px solid #e1e4e8; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; font-size: 12px; margin-bottom: 4px; color: #555; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        button { background: #007bff; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        button:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; padding: 5px 10px; width: auto; font-size: 12px; }
        .btn-danger:hover { background: #c82333; }
        .btn-edit { background: #17a2b8; padding: 5px 10px; width: auto; font-size: 12px; color: white; text-decoration: none; display: inline-block; border-radius: 4px; font-weight: bold;}
        .btn-edit:hover { background: #138496; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        .badge { background: #e2e3e5; color: #383d41; padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .acciones-inline { display: flex; gap: 5px; align-items: center; }
    </style>
</head>
<body>
<div class="container">
    <h1>Dashboard de Control - ChimboteFood SOA (GRUPO E)</h1>
    
    <div class="grid">
        <!-- 1. Formulario Cupón -->
        <div class="card">
            <h3>🎟️ <?= $edit_cupon ? 'Editar Cupón #' . $edit_cupon['id_cupon'] : 'Crear Cupón' ?></h3>
            <form method="POST">
                <input type="hidden" name="action" value="guardar_cupon">
                <?php if($edit_cupon): ?>
                    <input type="hidden" name="id_cupon" value="<?= $edit_cupon['id_cupon']; ?>">
                <?php endif; ?>
                <div class="form-group"><label>Código:</label><input type="text" name="codigo" value="<?= $edit_cupon['codigo'] ?? '' ?>" placeholder="EJ. VERANO2026" required></div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo_descuento">
                        <option value="porcentaje" <?= ($edit_cupon['tipo_descuento'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="monto_fijo" <?= ($edit_cupon['tipo_descuento'] ?? '') === 'monto_fijo' ? 'selected' : '' ?>>Monto Fijo (S/)</option>
                    </select>
                </div>
                <div class="form-group"><label>Valor:</label><input type="number" step="0.01" name="valor_descuento" value="<?= $edit_cupon['valor_descuento'] ?? '' ?>" placeholder="15.00" required></div>
                <div class="form-group"><label>Monto Mínimo (S/):</label><input type="number" step="0.01" name="monto_minimo" value="<?= $edit_cupon['monto_minimo'] ?? '0.00' ?>"></div>
                <button type="submit"><?= $edit_cupon ? 'Actualizar Cupón' : 'Guardar Cupón' ?></button>
                <?php if($edit_cupon): ?>
                    <a href="index.php" style="display:block; text-align:center; margin-top:8px; font-size:12px; color:#666;">Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- 2. Formulario Cliente -->
        <div class="card">
            <h3>👤 <?= $edit_cliente ? 'Editar Cliente #' . $edit_cliente['id_cliente'] : 'Crear / Generar Cliente' ?></h3>
            <form method="POST" style="margin-bottom: 10px;">
                <input type="hidden" name="action" value="guardar_cliente">
                <?php if($edit_cliente): ?>
                    <input type="hidden" name="id_cliente" value="<?= $edit_cliente['id_cliente']; ?>">
                <?php endif; ?>
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_cliente" value="<?= $edit_cliente['nombre_cliente'] ?? '' ?>" placeholder="Ej. Juan Pérez" required></div>
                <div class="form-group"><label>Dirección:</label><input type="text" name="direccion" value="<?= $edit_cliente['direccion'] ?? '' ?>" placeholder="Ej. Av. Pardo 123" required></div>
                <div class="form-group"><label>Distancia (km):</label><input type="number" step="0.1" name="distancia_km" value="<?= $edit_cliente['distancia_km'] ?? '3.0' ?>" required></div>
                <button type="submit"><?= $edit_cliente ? 'Actualizar Cliente' : 'Guardar Cliente' ?></button>
                <?php if($edit_cliente): ?>
                    <a href="index.php" style="display:block; text-align:center; margin-top:8px; font-size:12px; color:#666;">Cancelar edición</a>
                <?php endif; ?>
            </form>
            <?php if(!$edit_cliente): ?>
            <form method="POST">
                <input type="hidden" name="action" value="crear_cliente_aleatorio">
                <button type="submit" class="btn-warning">+ Generar Aleatorio</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- 3. Formulario Restaurante y Simulación -->
        <div class="card">
            <h3>🏪 <?= $edit_restaurante ? 'Editar Restaurante #' . $edit_restaurante['id_restaurante'] : 'Restaurante y Pedido' ?></h3>
            <form method="POST" style="margin-bottom: 15px;">
                <input type="hidden" name="action" value="guardar_restaurante">
                <?php if($edit_restaurante): ?>
                    <input type="hidden" name="id_restaurante" value="<?= $edit_restaurante['id_restaurante']; ?>">
                <?php endif; ?>
                <div class="form-group"><label>Nombre Local:</label><input type="text" name="nombre_restaurante" value="<?= $edit_restaurante['nombre_restaurante'] ?? '' ?>" placeholder="Ej. Pizza Mostra" required></div>
                <div class="form-group"><label>Tiempo Base (min):</label><input type="number" name="tiempo_preparacion_base" value="<?= $edit_restaurante['tiempo_preparacion_base'] ?? '15' ?>" required></div>
                <button type="submit"><?= $edit_restaurante ? 'Actualizar Restaurante' : 'Guardar Restaurante' ?></button>
                <?php if($edit_restaurante): ?>
                    <a href="index.php" style="display:block; text-align:center; margin-top:8px; font-size:12px; color:#666;">Cancelar edición</a>
                <?php endif; ?>
            </form>
            <?php if(!$edit_restaurante): ?>
            <form method="POST">
                <input type="hidden" name="action" value="crear_restaurante_aleatorio">
                <button type="submit" class="btn-warning">+ Generar Local Aleatorio</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4. Simular Pedido con Autocompletado de Cupones -->
    <div class="card" style="margin-top: 20px;">
        <h3>🛒 Simular Pedido con Cupón</h3>
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; align-items: end;">
            <input type="hidden" name="action" value="crear_pedido_rapido">
            <div class="form-group" style="margin-bottom:0;">
                <label>Cliente:</label>
                <select name="id_cliente" required>
                    <?php 
                    $clientes->data_seek(0);
                    while($cli = $clientes->fetch_assoc()): ?>
                        <option value="<?= $cli['id_cliente']; ?>"><?= $cli['nombre_cliente']; ?> (<?= $cli['distancia_km']; ?> km)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Restaurante:</label>
                <select name="id_restaurante" required>
                    <?php 
                    $restaurantes->data_seek(0);
                    while($res = $restaurantes->fetch_assoc()): ?>
                        <option value="<?= $res['id_restaurante']; ?>"><?= $res['nombre_restaurante']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;"><label>Producto:</label><input type="text" name="producto" value="Salchipapa Mixta Familiar" required></div>
            <div class="form-group" style="margin-bottom:0;"><label>Subtotal (S/):</label><input type="number" step="0.01" name="subtotal" value="55.00" required></div>
            
            <div class="form-group" style="margin-bottom:0;">
                <label>Buscar / Seleccionar Cupón:</label>
                <input type="text" name="codigo_cupon" list="lista-cupones" placeholder="Escribe para buscar..." autocomplete="off">
                <datalist id="lista-cupones">
                    <?php 
                    $cupones->data_seek(0);
                    while($cup = $cupones->fetch_assoc()): ?>
                        <option value="<?= $cup['codigo']; ?>">Min: S/ <?= $cup['monto_minimo']; ?></option>
                    <?php endwhile; ?>
                </datalist>
            </div>

            <div><button type="submit" class="btn-success" style="margin-top:0;">Procesar Pedido</button></div>
        </form>
    </div>

    <!-- TABLAS CON ACCIONES DE EDITAR Y ELIMINAR -->
    <h2>📋 Listado de Clientes</h2>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>Dirección</th><th>Distancia</th><th>Acciones</th></tr>
        <?php 
        $clientes->data_seek(0);
        while($c = $clientes->fetch_assoc()): ?>
            <tr>
                <td>#<?= $c['id_cliente']; ?></td>
                <td><strong><?= $c['nombre_cliente']; ?></strong></td>
                <td><?= $c['direccion']; ?></td>
                <td><?= $c['distancia_km']; ?> km</td>
                <td>
                    <div class="acciones-inline">
                        <a href="index.php?edit=<?= $c['id_cliente']; ?>&tipo=cliente" class="btn-edit">Editar</a>
                        <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');" style="margin:0;">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="tipo" value="cliente">
                            <input type="hidden" name="id" value="<?= $c['id_cliente']; ?>">
                            <button type="submit" class="btn-danger">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🏪 Listado de Restaurantes / Locales</h2>
    <table>
        <tr><th>ID</th><th>Nombre del Local</th><th>Tiempo Base</th><th>Acciones</th></tr>
        <?php 
        $restaurantes->data_seek(0);
        while($r = $restaurantes->fetch_assoc()): ?>
            <tr>
                <td>#<?= $r['id_restaurante']; ?></td>
                <td><strong><?= $r['nombre_restaurante']; ?></strong></td>
                <td><?= $r['tiempo_preparacion_base']; ?> min</td>
                <td>
                    <div class="acciones-inline">
                        <a href="index.php?edit=<?= $r['id_restaurante']; ?>&tipo=restaurante" class="btn-edit">Editar</a>
                        <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este restaurante?');" style="margin:0;">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="tipo" value="restaurante">
                            <input type="hidden" name="id" value="<?= $r['id_restaurante']; ?>">
                            <button type="submit" class="btn-danger">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🎟️ Cupones Activos</h2>
    <table>
        <tr><th>ID</th><th>Código</th><th>Tipo</th><th>Valor</th><th>Monto Mínimo</th><th>Acciones</th></tr>
        <?php 
        $cupones->data_seek(0);
        while($c = $cupones->fetch_assoc()): ?>
            <tr>
                <td>#<?= $c['id_cupon']; ?></td>
                <td><span class="badge badge-success"><?= $c['codigo']; ?></span></td>
                <td><?= $c['tipo_descuento']; ?></td>
                <td><?= $c['valor_descuento']; ?></td>
                <td>S/ <?= $c['monto_minimo']; ?></td>
                <td>
                    <div class="acciones-inline">
                        <a href="index.php?edit=<?= $c['id_cupon']; ?>&tipo=cupon" class="btn-edit">Editar</a>
                        <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este cupón?');" style="margin:0;">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="tipo" value="cupon">
                            <input type="hidden" name="id" value="<?= $c['id_cupon']; ?>">
                            <button type="submit" class="btn-danger">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>🚀 Historial de Pedidos Procesados</h2>
    <table>
        <tr><th>ID</th><th>Cliente</th><th>Restaurante</th><th>Producto</th><th>Cupón Usado</th><th>Descuento</th><th>Total Pagar</th><th>ETA Total</th></tr>
        <?php while($p = $pedidos->fetch_assoc()): ?>
            <tr>
                <td>#<?= $p['id_pedido']; ?></td>
                <td><?= $p['nombre_cliente']; ?></td>
                <td><?= $p['nombre_restaurante']; ?></td>
                <td><?= $p['producto']; ?></td>
                <td><?= $p['codigo_cupon'] ? '<span class="badge badge-success">'.$p['codigo_cupon'].'</span>' : '<span class="badge">Ninguno</span>'; ?></td>
                <td>S/ <?= number_format($p['descuento_aplicado'], 2); ?></td>
                <td><strong>S/ <?= number_format($p['monto_total'], 2); ?></strong></td>
                <td>⏱️ <?= $p['eta_minutos_total']; ?> min</td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>