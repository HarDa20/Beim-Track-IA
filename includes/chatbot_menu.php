<?php
// =====================================================================
// Menú parametrizado del asistente Beimy (spec 014 · RF-BOT-02)
//
// Este archivo es SOLO configuración: para agregar, quitar o cambiar una
// opción del chatbot se edita este arreglo, sin tocar includes/chatbot.php.
//
// Cada opción define:
//   etiqueta    Texto del botón.
//   roles       Quién la ve: cliente, empleado, administrador.
//   tipo        'texto'    -> responde el texto fijo de "respuesta".
//               'dinamico' -> llama a la función de "funcion" (datos reales
//                             SOLO del usuario que pregunta).
//               'ia'       -> invita a escribir una pregunta libre (Gemini).
//   respuesta   Texto fijo (tipo 'texto' o 'ia'). {nombre} = primer nombre.
//   funcion     Función de includes/chatbot.php (tipo 'dinamico').
//   siguientes  Opciones que se ofrecen después de responder.
// =====================================================================

/** @spec RF-BOT-02 */
function chatbotMenuConfig(): array
{
    $todos = ['cliente', 'empleado', 'administrador'];

    return [
        'saludo'    => '¡Hola, {nombre}! Soy Beimy, el asistente de BEIM Track. ¿En qué te ayudo?',
        'principal' => ['mis_pedidos', 'crear_pedido', 'mi_bandeja', 'como_avanzar', 'resumen', 'precios', 'estados', 'pregunta_libre', 'contacto'],
        'opciones'  => [
            'mis_pedidos' => [
                'etiqueta'   => '📦 Mis pedidos',
                'roles'      => ['cliente'],
                'tipo'       => 'dinamico',
                'funcion'    => 'chatbotMisPedidos',
                'siguientes' => ['estados', 'cancelar', 'crear_pedido'],
            ],
            'crear_pedido' => [
                'etiqueta'   => '➕ Cómo crear un pedido',
                'roles'      => ['cliente'],
                'tipo'       => 'texto',
                'respuesta'  => "Es muy fácil:\n1. Entra a \"Nuevo pedido\" en el menú.\n2. Elige la categoría, escribe el trabajo, la cantidad y la fecha de entrega.\n3. Adjunta tu diseño (PDF, AI, JPG, PNG o WEBP).\n4. Revisa el costo estimado y pulsa \"Enviar pedido\".\nSi aún no lo tienes listo, puedes guardarlo como borrador.",
                'siguientes' => ['precios', 'mis_pedidos'],
            ],
            'cancelar' => [
                'etiqueta'   => '✖️ Cancelar un pedido',
                'roles'      => ['cliente'],
                'tipo'       => 'texto',
                'respuesta'  => "Puedes cancelar tu pedido mientras esté en \"Recibido\": abre el pedido y pulsa \"Cancelar mi pedido\" escribiendo el motivo. Si ya está en diseño o producción, comunícate con Recepción.",
                'siguientes' => ['mis_pedidos', 'contacto'],
            ],
            'mi_bandeja' => [
                'etiqueta'   => '📥 Mi bandeja',
                'roles'      => ['empleado'],
                'tipo'       => 'dinamico',
                'funcion'    => 'chatbotMiBandeja',
                'siguientes' => ['como_avanzar', 'estados'],
            ],
            'como_avanzar' => [
                'etiqueta'   => '➡️ Cómo avanzo un pedido',
                'roles'      => ['empleado'],
                'tipo'       => 'texto',
                'respuesta'  => "1. En \"Mi bandeja\" pulsa \"Tomar pedido\" para quedar como responsable.\n2. Abre el pedido y revisa especificaciones y archivos.\n3. Usa el botón de la siguiente etapa. Si devuelves de Calidad a Producción o cancelas, escribe el motivo.\nCada cambio queda en el historial con tu nombre.",
                'siguientes' => ['mi_bandeja', 'estados'],
            ],
            'resumen' => [
                'etiqueta'   => '📊 Resumen del día',
                'roles'      => ['administrador'],
                'tipo'       => 'dinamico',
                'funcion'    => 'chatbotResumenAdmin',
                'siguientes' => ['estados', 'pregunta_libre'],
            ],
            'precios' => [
                'etiqueta'   => '💲 Precios',
                'roles'      => $todos,
                'tipo'       => 'dinamico',
                'funcion'    => 'chatbotPrecios',
                'siguientes' => ['crear_pedido', 'pregunta_libre'],
            ],
            'estados' => [
                'etiqueta'   => '🔄 Etapas de un pedido',
                'roles'      => $todos,
                'tipo'       => 'texto',
                'respuesta'  => "Cada pedido pasa por estas etapas:\nRecibido (Recepción) → En diseño (Diseño) → En producción (Producción) → Control de calidad (Calidad) → Listo para entrega (Recepción) → Entregado.\nCalidad puede devolverlo a Producción, y antes de empezar se puede cancelar.",
                'siguientes' => ['mis_pedidos', 'mi_bandeja', 'resumen'],
            ],
            'pregunta_libre' => [
                'etiqueta'   => '💬 Hacer una pregunta',
                'roles'      => $todos,
                'tipo'       => 'ia',
                'respuesta'  => 'Claro, {nombre}. Escríbeme tu pregunta abajo con tus palabras y te respondo.',
                'siguientes' => [],
            ],
            'contacto' => [
                'etiqueta'   => '☎️ Contacto',
                'roles'      => $todos,
                'tipo'       => 'texto',
                'respuesta'  => "Recepción te atiende de lunes a viernes de 8:00 a. m. a 6:00 p. m. y los sábados de 8:00 a. m. a 1:00 p. m., por llamada, chat o en el punto de atención.",
                'siguientes' => ['pregunta_libre'],
            ],
        ],
    ];
}
