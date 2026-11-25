<?php
// @spec RF-AUT-04
// @spec RF-MSE-04
require __DIR__ . '/includes/bootstrap.php';
requierePostConCsrf(usuarioActual() ? rutaPanel(usuarioActual()['rol']) : '/index.php');

if (!empty($_POST['todas'])) {
    cerrarSesion();
    iniciarSesionSegura();
    flash('exito', 'Cerraste todas las cuentas de este navegador.');
    redirigir('/index.php', 0);
}

cerrarCuenta(ranuraActual());
flash('exito', 'Cerraste sesión correctamente.');
redirigir('/index.php', 0);
