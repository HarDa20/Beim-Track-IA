<?php
// Visualización 3D del pedido (spec 016). Modelos en CSS 3D, sin librerías.

/** Modelo 3D de cada categoría. Las categorías nuevas usan el pendón. */
const MODELOS_3D = ['Fachadas' => 'fachada', 'Interiores' => 'pared', 'Gran formato' => 'pendon'];

/** @spec RF-V3D-01 */
function modeloParaCategoria(?string $categoria): string
{
    return MODELOS_3D[(string) $categoria] ?? 'pendon';
}

/**
 * Componente de visualización 3D. Trae los tres modelos para poder cambiar
 * de uno a otro al elegir otra categoría sin recargar la página.
 *
 * @spec RF-V3D-01, RF-V3D-03
 */
function visualizador3D(string $modelo, ?string $arteUrl, string $titulo, bool $enFormulario = false): string
{
    $arte = $arteUrl ? ' style="--arte: url(\'' . e($arteUrl) . '\')"' : '';
    $tituloHtml = '<span class="viz-arte-titulo" data-viz-titulo>' . e($titulo !== '' ? $titulo : 'Tu diseño aquí') . '</span>';
    $arteCara = fn(string $clase) => '<div class="viz-cara viz-arte ' . $clase . '">' . $tituloHtml . '</div>';

    return '<div class="viz" data-viz data-modelo="' . e($modelo) . '"' . ($arteUrl ? '' : ' data-sin-arte') . ($enFormulario ? ' data-viz-formulario' : '') . $arte . '>'
        . '<div class="viz-escena" tabindex="0" role="img" aria-label="Vista 3D del diseño. Arrastra o usa las flechas para girar." data-viz-escena>'
        . '<div class="viz-mundo" data-viz-mundo>'
        // Local con letrero (Fachadas)
        . '<div class="viz-modelo viz-fachada">'
        . '<div class="viz-cara f-suelo"></div>'
        . '<div class="viz-cara f-frente"><span class="f-puerta"></span><span class="f-vitrina"></span></div>'
        . '<div class="viz-cara f-atras"></div><div class="viz-cara f-izq"></div><div class="viz-cara f-der"><span class="f-ventana"></span></div><div class="viz-cara f-techo"></div>'
        . $arteCara('f-letrero') . '<div class="viz-cara f-letrero-canto"></div>'
        . '</div>'
        // Pared con mural (Interiores)
        . '<div class="viz-modelo viz-pared">'
        . '<div class="viz-cara p-piso"></div><div class="viz-cara p-fondo"></div><div class="viz-cara p-lado"></div>'
        . $arteCara('p-mural')
        . '<div class="viz-cara p-sofa-base"></div><div class="viz-cara p-sofa-espaldar"></div><div class="viz-cara p-sofa-top"></div>'
        . '<div class="viz-cara p-planta"></div>'
        . '</div>'
        // Pendón en su soporte (Gran formato)
        . '<div class="viz-modelo viz-pendon">'
        . '<div class="viz-cara b-piso"></div>'
        . $arteCara('b-lona') . '<div class="viz-cara b-reverso"></div>'
        . '<div class="viz-cara b-base"></div><div class="viz-cara b-base-top"></div><div class="viz-cara b-barra"></div>'
        . '</div>'
        . '</div>'
        . '<p class="viz-aviso" data-viz-aviso hidden>La vista previa está disponible solo para imágenes JPG, PNG o WEBP.</p>'
        . '</div>'
        . '<div class="viz-controles">'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="izq" aria-label="Girar a la izquierda">↺</button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="frente">Vista frontal</button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="der" aria-label="Girar a la derecha">↻</button>'
        . '<span class="viz-sep"></span>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="alejar" aria-label="Alejar">−</button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="acercar" aria-label="Acercar">+</button>'
        . '<span class="viz-sep"></span>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="noche" aria-pressed="false">' . icono('moon', 14) . ' Noche</button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-viz-accion="girar" aria-pressed="false">' . icono('swap', 14) . ' Girar solo</button>'
        . '</div>'
        . '<p class="viz-pista">' . icono('sparkle', 13) . ' Arrastra el modelo para girarlo · vista aproximada, no a escala</p>'
        . '</div>';
}
