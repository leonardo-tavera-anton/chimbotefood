<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ChimboteFood SOA - Endpoints y Panel</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; }
        .endpoint-box { background: #e8f4fd; border-left: 4px solid #007bff; padding: 12px 15px; margin-bottom: 15px; border-radius: 4px; }
        .endpoint-box code { background: #fff; padding: 3px 6px; border-radius: 3px; border: 1px solid #ccc; font-family: monospace; display: inline-block; margin-top: 5px; width: 100%; box-sizing: border-box; }
        .method { font-weight: bold; padding: 2px 6px; border-radius: 3px; font-size: 12px; color: #fff; }
        .get { background: #28a745; }
        .post { background: #007bff; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        
        /* Modificado para soportar 4 tarjetas que se adapten bien */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; }
        
        .form-group { margin-bottom: 10px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        .btn-blue { background: #007bff; }
        .btn-blue:hover { background: #0069d9; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #e9ecef; }
        .soa-badge { background: #17a2b8; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; float: right; }
    </style>
</head>
<body>
<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "restaurante_db";

$conexion = new mysqli($host, $user, $password, $dbname);

if ($conexion->connect_error) {
    die("<div class='container'><div class='alert-error'>Error de conexión: " . $conexion->connect_error . "</div></div>");
}
$conexion->set_charset("utf8mb4");

// =================================================================
// 1. CAPA SOA: API PARA CONSUMIDORES EXTERNOS (POSTMAN/MARKETPLACE)
// =================================================================
if (isset($_GET['servicio'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';
    
    if ($token !== 'Bearer chimbote_seguro_2026') {
        http_response_code(401);
        echo json_encode([
            "status" => "error", 
            "message" => "Acceso Denegado: Autenticación requerida. Identidad de consumidor no verificada."
        ]);
        exit();
    }

    $servicio = $_GET['servicio'];
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    switch ($servicio) {
        case 'ListarClientes':
            $res = $conexion->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
            exit();

        case 'ListarRestaurantes':
            $res = $conexion->query("SELECT * FROM restaurantes ORDER BY id_restaurante DESC");
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
            exit();

        case 'ListarCupones':
            $res = $conexion->query("SELECT * FROM cupones ORDER BY id_cupon DESC");
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
            exit();

        case 'ValidarCupon':
            $codigo = strtoupper(trim($input['codigo_cupon'] ?? $_GET['codigo_cupon'] ?? ''));
            $subtotal = (float)($input['subtotal'] ?? $_GET['subtotal'] ?? 0);

            if (empty($codigo)) {
                echo json_encode(["status" => "error", "message" => "Debe proporcionar un código de cupón."]);
                exit();
            }

            $stmt = $conexion->prepare("SELECT * FROM cupones WHERE codigo = ? AND activo = 1");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 0) {
                echo json_encode(["status" => "error", "message" => "El cupón no existe o está inactivo."]);
                exit();
            }

            $cup = $resultado->fetch_assoc();
            if ($subtotal < (float)$cup['monto_minimo']) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "El subtotal no cumple con el monto mínimo requerido de $" . $cup['monto_minimo']
                ]);
                exit();
            }

            $descuento = ($cup['tipo_descuento'] === 'porcentaje') 
                ? $subtotal * ((float)$cup['valor_descuento'] / 100) 
                : (float)$cup['valor_descuento'];

            echo json_encode([
                "status" => "success",
                "message" => "Cupón aplicado correctamente",
                "codigo" => $cup['codigo'],
                "tipo" => $cup['tipo_descuento'],
                "descuento_calculado" => $descuento,
                "nuevo_total_estimado" => max(0, $subtotal - $descuento)
            ]);
            exit();

        case 'CrearPedido':
            $id_cliente = (int)($input['id_cliente'] ?? 0);
            $id_restaurante = (int)($input['id_restaurante'] ?? 0);
            $producto = trim($input['producto'] ?? '');
            $subtotal = (float)($input['subtotal'] ?? 0);
            $codigo_cupon = strtoupper(trim($input['codigo_cupon'] ?? ''));

            if ($id_cliente > 0 && $id_restaurante > 0 && $subtotal > 0) {
                $cli = $conexion->query("SELECT distancia_km FROM clientes WHERE id_cliente = $id_cliente")->fetch_assoc();
                $rest = $conexion->query("SELECT tiempo_preparacion_base FROM restaurantes WHERE id_restaurante = $id_restaurante")->fetch_assoc();

                $distancia = (float)($cli['distancia_km'] ?? 3.5);
                $tiempo_prep = (int)($rest['tiempo_preparacion_base'] ?? 15);
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

                if ($stmt->execute()) {
                    echo json_encode([
                        "status" => "success", 
                        "message" => "Pedido y cupón procesados con éxito", 
                        "id_pedido" => $stmt->insert_id, 
                        "descuento_aplicado" => $descuento,
                        "monto_final" => $monto_total,
                        "eta_minutos" => $eta_total
                    ]);
                } else {
                    echo json_encode(["status" => "error", "message" => $conexion->error]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            }
            exit();

        default:
            echo json_encode(["status" => "error", "message" => "Servicio no reconocido en el catálogo."]);
            exit();
    }
}

// =================================================================
// 2. CAPA VISUAL: DASHBOARD DE ADMINISTRACIÓN Y LISTA DE ENDPOINTS
// =================================================================
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_cliente') {
        $nombre = trim($_POST['nombre_cliente'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $distancia = (float)($_POST['distancia_km'] ?? 3.5);

        if (!empty($nombre) && !empty($direccion)) {
            $stmt = $conexion->prepare("INSERT INTO clientes (nombre_cliente, direccion, distancia_km) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $nombre, $direccion, $distancia);
            if ($stmt->execute()) { header("Location: index.php?status=cliente_ok"); exit(); } else { $error = "Error: " . $conexion->error; }
        }
    } elseif ($action === 'crear_local') {
        $nombre = trim($_POST['nombre_restaurante'] ?? '');
        $tiempo = (int)($_POST['tiempo_preparacion_base'] ?? 15);

        if (!empty($nombre)) {
            $stmt = $conexion->prepare("INSERT INTO restaurantes (nombre_restaurante, tiempo_preparacion_base) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $tiempo);
            if ($stmt->execute()) { header("Location: index.php?status=local_ok"); exit(); } else { $error = "Error: " . $conexion->error; }
        }
    } elseif ($action === 'crear_cupon') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $tipo = $_POST['tipo_descuento'] ?? 'porcentaje';
        $valor = (float)($_POST['valor_descuento'] ?? 0);
        $minimo = (float)($_POST['monto_minimo'] ?? 0);

        if (!empty($codigo) && $valor > 0) {
            $stmt = $conexion->prepare("INSERT INTO cupones (codigo, tipo_descuento, valor_descuento, monto_minimo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $codigo, $tipo, $valor, $minimo);
            if ($stmt->execute()) { header("Location: index.php?status=cupon_ok"); exit(); } else { $error = "Error: " . $conexion->error; }
        }
    } elseif ($action === 'crear_pedido') {
        // Lógica para registrar pedidos desde la UI simulando la API
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_restaurante = (int)($_POST['id_restaurante'] ?? 0);
        $producto = trim($_POST['producto'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $codigo_cupon = strtoupper(trim($_POST['codigo_cupon'] ?? ''));

        if ($id_cliente > 0 && $id_restaurante > 0 && $subtotal > 0 && !empty($producto)) {
            $cli = $conexion->query("SELECT distancia_km FROM clientes WHERE id_cliente = $id_cliente")->fetch_assoc();
            $rest = $conexion->query("SELECT tiempo_preparacion_base FROM restaurantes WHERE id_restaurante = $id_restaurante")->fetch_assoc();

            $distancia = (float)($cli['distancia_km'] ?? 3.5);
            $tiempo_prep = (int)($rest['tiempo_preparacion_base'] ?? 15);
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

            if ($stmt->execute()) { header("Location: index.php?status=pedido_ok"); exit(); } else { $error = "Error: " . $conexion->error; }
        } else {
            $error = "Por favor, complete todos los campos obligatorios del pedido.";
        }
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'cliente_ok') $mensaje = "Cliente registrado correctamente.";
    if ($_GET['status'] === 'local_ok') $mensaje = "Local registrado correctamente.";
    if ($_GET['status'] === 'cupon_ok') $mensaje = "Cupón registrado correctamente.";
    if ($_GET['status'] === 'pedido_ok') $mensaje = "Pedido de prueba creado correctamente.";
}

$clientes_list = $conexion->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
$restaurantes_list = $conexion->query("SELECT * FROM restaurantes ORDER BY id_restaurante DESC");
$cupones_list = $conexion->query("SELECT * FROM cupones ORDER BY id_cupon DESC");
$pedidos_list = $conexion->query("SELECT p.*, c.nombre_cliente, r.nombre_restaurante FROM pedidos p JOIN clientes c ON p.id_cliente = c.id_cliente JOIN restaurantes r ON p.id_restaurante = r.id_restaurante ORDER BY p.id_pedido DESC");
?>

<div class="container">
    <h1>Panel Proveedor ChimboteFood <span class="soa-badge">SOA Internal System</span></h1>
    
    <!-- SECCIÓN VISIBLE DE ENDPOINTS PARA POSTMAN -->
    <div style="background: #f8f9fa; border: 1px solid #cbd3da; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #0056b3;">Catálogo de Endpoints SOA (Para Postman)</h2>
        <p style="font-size: 13px; color: #555;">Recuerda enviar el Header obligatorio: <code>Authorization: Bearer chimbote_seguro_2026</code></p>
        
        <div class="endpoint-box">
            <span class="method get">GET</span> <strong>1. Listar Clientes</strong>
            <code>http://localhost/chimbotefood/index.php?servicio=ListarClientes</code>
        </div>
        <div class="endpoint-box">
            <span class="method get">GET</span> <strong>2. Listar Restaurantes</strong>
            <code>http://localhost/chimbotefood/index.php?servicio=ListarRestaurantes</code>
        </div>
        <div class="endpoint-box">
            <span class="method post">POST</span> <strong>3. Validar Cupón (Body JSON)</strong>
            <code>http://localhost/chimbotefood/index.php?servicio=ValidarCupon</code>
        </div>
        <div class="endpoint-box">
            <span class="method post">POST</span> <strong>4. Crear Pedido (Body JSON)</strong>
            <code>http://localhost/chimbotefood/index.php?servicio=CrearPedido</code>
        </div>
    </div>
    <!-- FIN DE LA SECCIÓN VISIBLE DE ENDPOINTS -->

    <?php if ($mensaje): ?><div class="alert-success"><?= $mensaje; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?= $error; ?></div><?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Registrar Cliente</h2>
            <form method="POST">
                <input type="hidden" name="action" value="crear_cliente">
                <div class="form-group"><label>Nombre:</label><input type="text" name="nombre_cliente" required></div>
                <div class="form-group"><label>Dirección:</label><input type="text" name="direccion" required></div>
                <div class="form-group"><label>Distancia (KM):</label><input type="number" step="0.1" name="distancia_km" value="3.5" required></div>
                <button type="submit">Guardar Cliente</button>
            </form>
        </div>
        <div class="card">
            <h2>Registrar Local</h2>
            <form method="POST">
                <input type="hidden" name="action" value="crear_local">
                <div class="form-group"><label>Nombre del Local:</label><input type="text" name="nombre_restaurante" required></div>
                <div class="form-group"><label>Tiempo Prep. Base (min):</label><input type="number" name="tiempo_preparacion_base" value="15" required></div>
                <button type="submit">Guardar Local</button>
            </form>
        </div>
        <div class="card">
            <h2>Crear Cupón</h2>
            <form method="POST">
                <input type="hidden" name="action" value="crear_cupon">
                <div class="form-group"><label>Código Cupón:</label><input type="text" name="codigo" required></div>
                <div class="form-group"><label>Tipo Descuento:</label><select name="tipo_descuento"><option value="porcentaje">Porcentaje (%)</option><option value="monto_fijo">Monto Fijo ($)</option></select></div>
                <div class="form-group"><label>Valor Descuento:</label><input type="number" step="0.01" name="valor_descuento" required></div>
                <div class="form-group"><label>Monto Mínimo ($):</label><input type="number" step="0.01" name="monto_minimo" value="0.00"></div>
                <button type="submit" class="btn-blue">Crear Cupón</button>
            </form>
        </div>
        
        <!-- NUEVO MÓDULO: CREAR PEDIDO DE PRUEBA -->
        <div class="card">
            <h2>Simular Pedido (UI)</h2>
            <form method="POST">
                <input type="hidden" name="action" value="crear_pedido">
                
                <div class="form-group">
                    <label>Cliente:</label>
                    <select name="id_cliente" required>
                        <option value="">Seleccione...</option>
                        <?php 
                        // Reposicionamos puntero por si se usó arriba
                        $clientes_list->data_seek(0);
                        while($c = $clientes_list->fetch_assoc()): 
                        ?>
                            <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre_cliente'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Restaurante:</label>
                    <select name="id_restaurante" required>
                        <option value="">Seleccione...</option>
                        <?php 
                        $restaurantes_list->data_seek(0);
                        while($r = $restaurantes_list->fetch_assoc()): 
                        ?>
                            <option value="<?= $r['id_restaurante'] ?>"><?= $r['nombre_restaurante'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- CAMPO SELECCIONABLE Y ESCRIBIBLE (Combobox) -->
                <div class="form-group">
                    <label>Producto:</label>
                    <input type="text" name="producto" list="lista_productos" required autocomplete="off" placeholder="Seleccione o escriba uno nuevo...">
                    <datalist id="lista_productos">
                        <option value="Pollo a la Brasa">
                        <option value="Ceviche Clásico">
                        <option value="Lomo Saltado">
                        <option value="Chaufa de Mariscos">
                        <option value="Pizza Familiar">
                        <option value="Hamburguesa Royal">
                        <option value="Caldo de Gallina">
                    </datalist>
                </div>

                <div class="form-group">
                    <label>Subtotal ($):</label>
                    <input type="number" step="0.01" name="subtotal" required>
                </div>

                <div class="form-group">
                    <label>Código Cupón (Opcional):</label>
                    <input type="text" name="codigo_cupon" placeholder="Ej. OFERTA20">
                </div>

                <button type="submit" class="btn-warning">Crear Pedido</button>
            </form>
        </div>
    </div>

    <hr style="border-top: 2px solid #007bff; margin: 30px 0;">
    <h2>Auditoría de Datos Generados (Vista Proveedor)</h2>

    <h3>Historial de Pedidos Procesados</h3>
    <table>
        <tr><th>ID</th><th>Cliente</th><th>Local</th><th>Producto</th><th>Cupón</th><th>Descuento</th><th>Total Final</th><th>ETA</th></tr>
        <?php while($p = $pedidos_list->fetch_assoc()): ?>
            <tr>
                <td>#<?= $p['id_pedido']; ?></td><td><?= $p['nombre_cliente']; ?></td><td><?= $p['nombre_restaurante']; ?></td>
                <td><?= $p['producto']; ?></td><td><?= $p['codigo_cupon'] ?: 'N/A'; ?></td>
                <td>-$<?= $p['descuento_aplicado']; ?></td><td><strong>$<?= $p['monto_total']; ?></strong></td><td><?= $p['eta_minutos_total']; ?> min</td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>