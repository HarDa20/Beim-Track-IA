<?php
// @spec RF-REC-01, RF-REC-02
// Registro de pedidos recibidos por llamada, chat o en persona.
// Disponible para empleados de Recepción y para el administrador.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereLogin();
if (!puedeRegistrarPedidos($u)) {
    flash('error', 'Solo Recepción y Administración pueden registrar pedidos a nombre de un cliente.');
    redirigirSegunRol($u['rol']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requierePostConCsrf('/empleado/registrar_pedido.php');

    [$errores, $datos] = validarDatosPedido($pdo, $_POST);
    [$erroresArchivos, $archivos] = validarArchivosSubidos($_FILES['archivos'] ?? null);
    $errores = array_merge($errores, $erroresArchivos);

    $tipoCliente = ($_POST['tipo_cliente'] ?? 'existente') === 'nuevo' ? 'nuevo' : 'existente';
    $clienteId = 0;
    $nuevoCliente = null;

    if ($tipoCliente === 'existente') {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'cliente' AND activo = 1");
        $stmt->execute([(int) ($_POST['cliente_id'] ?? 0)]);
        $clienteId = (int) $stmt->fetchColumn();
        if (!$clienteId) {
            $errores[] = 'Selecciona un cliente registrado y activo.';
        }
    } else {
        $nombre = trim((string) ($_POST['cliente_nombre'] ?? ''));
        $correo = mb_strtolower(trim((string) ($_POST['cliente_correo'] ?? '')));
        $telefono = trim((string) ($_POST['cliente_telefono'] ?? ''));
        $errores = array_merge($errores, validarDatosUsuario($nombre, $correo, $telefono));
        if (($_POST['cliente_acepta'] ?? '') !== '1') {
            $errores[] = 'Confirma que el cliente autorizó el tratamiento de sus datos.';
        }
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) && correoRegistrado($pdo, $correo)) {
            $errores[] = 'Ya existe un usuario con ese correo: selecciónalo como cliente registrado.';
        }
        $nuevoCliente = compact('nombre', 'correo', 'telefono');
    }

    if ($errores) {
        guardarOld($_POST);
        flash('error', implode(' ', $errores));
        redirigir('/empleado/registrar_pedido.php');
    }

    if ($nuevoCliente) {
        // Cuenta sin acceso: la contraseña aleatoria nadie la conoce.
        $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, activo, acepta_datos_en, creado_en) VALUES (?, ?, ?, ?, 'cliente', 1, ?, ?)")
            ->execute([$nuevoCliente['nombre'], $nuevoCliente['correo'], $nuevoCliente['telefono'] ?: null,
                password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), ahora(), ahora()]);
        $clienteId = (int) $pdo->lastInsertId();
    }

    $id = guardarPedido($pdo, $u, $clienteId, $datos, (string) ($_POST['canal_recepcion'] ?? 'Llamada'), false, $archivos);
    limpiarOld();
    flash('exito', 'Pedido ' . codigoPedido($id) . ' registrado. Quedó en la bandeja de Recepción.');
    redirigir('/pedido.php?id=' . $id);
}

$categorias = categoriasActivas($pdo);
$clientes = $pdo->query("SELECT id, nombre, correo FROM usuarios WHERE rol = 'cliente' AND activo = 1 ORDER BY nombre")->fetchAll();
$valores = $_SESSION['old'] ?? [];
$accionForm = '/empleado/registrar_pedido.php';
$modoStaff = true;
$borradorId = null;

layoutInicio('Registrar pedido', 'registrar', 'Pedidos recibidos por llamada, chat o atención presencial');
include __DIR__ . '/../includes/form_pedido.php';
layoutFin();
