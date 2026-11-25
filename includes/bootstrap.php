<?php
// Punto de arranque común de todas las páginas de BEIM Track.
// Orden: configuración -> base de datos -> sesión segura -> utilidades -> servicios.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/pedidos.php';
require_once __DIR__ . '/archivos.php';
require_once __DIR__ . '/alertas.php';
require_once __DIR__ . '/notificaciones/notificador.php';
require_once __DIR__ . '/exportacion.php';
require_once __DIR__ . '/chatbot_menu.php';
require_once __DIR__ . '/chatbot.php';
require_once __DIR__ . '/gerencia.php';
require_once __DIR__ . '/visualizacion.php';
require_once __DIR__ . '/layout.php';

registrarManejadorErrores();
iniciarSesionSegura();
enviarCabecerasSeguridad();
