<?php
// @spec RNF-SEG-07
// Carga de variables de entorno desde el archivo .env de la raíz del proyecto.
// El archivo .env NO se sube al repositorio (ver .gitignore); usa .env.example
// como plantilla. Si no existe .env, se usan los valores por defecto de XAMPP.

if (!function_exists('cargarEnv')) {
    function cargarEnv(string $ruta): void
    {
        if (!is_file($ruta) || !is_readable($ruta)) {
            return;
        }
        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            $valor = trim($valor, "\"'");
            // Las variables reales del sistema tienen prioridad sobre el archivo.
            if (getenv($clave) === false) {
                putenv("$clave=$valor");
                $_ENV[$clave] = $valor;
            }
        }
    }

    function env(string $clave, $defecto = null)
    {
        $valor = $_ENV[$clave] ?? getenv($clave);
        return ($valor === false || $valor === null || $valor === '') ? $defecto : $valor;
    }

    function envBool(string $clave, bool $defecto = false): bool
    {
        $valor = env($clave);
        if ($valor === null) {
            return $defecto;
        }
        return in_array(strtolower((string) $valor), ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
    }

    cargarEnv(dirname(__DIR__) . '/.env');
}
