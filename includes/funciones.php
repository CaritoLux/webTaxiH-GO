<?php
// =====================================================================
//  Funciones que usan todas las páginas de TaxiH-GO!
//  Cada página hace:  session_start();  require_once 'config/conexion.php';
//                     require_once 'includes/funciones.php';
// =====================================================================

// Ruta a la carpeta principal desde la página actual:
// '' si la página está en la raíz, '../' si está en admin/, base/, conductor/ o pasajero/
$carpetaPagina = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
$carpetaRaiz   = realpath(__DIR__ . '/..');
define('RAIZ', $carpetaPagina === $carpetaRaiz ? '' : '../');


// ---------------------------------------------------------------- texto

// Escapa un texto antes de imprimirlo en el HTML (evita XSS).
// Es lo mismo que htmlspecialchars(..., ENT_QUOTES), pero más corto.
function e($texto)
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

// Ícono de Material Symbols (solo decorativo: el texto va a un lado)
function icono($nombre, $clase = '')
{
    return '<span class="material-symbols-outlined ' . $clase . '" aria-hidden="true">' . $nombre . '</span>';
}

// $45 cuando son pesos cerrados; $43.80 cuando hay centavos
function dinero($cantidad)
{
    $cantidad = (float) $cantidad;
    $decimales = (floor($cantidad) == $cantidad) ? 0 : 2;
    return '$' . number_format($cantidad, $decimales);
}

// 12 nov 2026
function fecha_corta($fecha)
{
    if (empty($fecha)) {
        return '';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[date('n', $t) - 1] . ' ' . date('Y', $t);
}

// 12 nov 2026 · 14:05
function fecha_hora($fecha)
{
    if (empty($fecha)) {
        return '';
    }
    return fecha_corta($fecha) . ' · ' . date('H:i', strtotime($fecha));
}

// 7891000002  ->  789 100 0002
function telefono_bonito($telefono)
{
    $t = preg_replace('/\D/', '', (string) $telefono);
    if (strlen($t) === 10) {
        return substr($t, 0, 3) . ' ' . substr($t, 3, 3) . ' ' . substr($t, 6);
    }
    return $telefono;
}

function nombre_completo($fila)
{
    return trim($fila['nombre'] . ' ' . $fila['apellido_paterno'] . ' ' . ($fila['apellido_materno'] ?? ''));
}

// Distancia en km con un decimal: 4.2 km
function kilometros($metros)
{
    return number_format($metros / 1000, 1) . ' km';
}

// Etiqueta de estado (texto siempre visible; el color solo refuerza)
function etiqueta($estado)
{
    $estados = [
        // viajes
        'BUSCANDO'       => ['Buscando taxi', 'activo'],
        'ASIGNADO'       => ['Taxi en camino', 'activo'],
        'EN_ORIGEN'      => ['Taxi en el punto', 'activo'],
        'EN_CURSO'       => ['En curso', 'activo'],
        'FINALIZADO'     => ['Finalizado', 'exito'],
        'CANCELADO'      => ['Cancelado', 'neutro'],
        'SIN_CONDUCTOR'  => ['Sin taxi disponible', 'neutro'],
        // documentos y conductores
        'PENDIENTE'      => ['Pendiente', 'neutro'],
        'ACEPTADO'       => ['Aceptado', 'exito'],
        'RECHAZADO'      => ['Rechazado', 'error'],
        'SIN_DOCUMENTOS' => ['Sin documentos', 'neutro'],
        'EN_REVISION'    => ['En revisión', 'neutro'],
        'APROBADO'       => ['Aprobado', 'exito'],
        // incidentes
        'ABIERTO'        => ['Abierto', 'activo'],
        'EN_ATENCION'    => ['En atención', 'activo'],
        'CERRADO'        => ['Cerrado', 'neutro'],
        // cuentas
        'ACTIVO'         => ['Activa', 'exito'],
        'SUSPENDIDO'     => ['Suspendida', 'error'],
    ];
    $datos = $estados[$estado] ?? [$estado, 'neutro'];
    return '<span class="estado estado-' . $datos[1] . '">' . e($datos[0]) . '</span>';
}

// Nombre amigable de cada rol
function nombre_rol($rol)
{
    $roles = ['PASAJERO' => 'Pasajero', 'CONDUCTOR' => 'Conductor', 'BASE' => 'Base', 'ADMINISTRADOR' => 'Administrador'];
    return $roles[$rol] ?? $rol;
}


// ------------------------------------------------------- sesión y roles

// Página de inicio de cada rol después de iniciar sesión
function panel_de_rol($rol)
{
    $paneles = [
        'PASAJERO'      => 'pasajero/panel.php',
        'CONDUCTOR'     => 'conductor/panel.php',
        'BASE'          => 'base/operacion.php',
        'ADMINISTRADOR' => 'admin/dashboard.php',
    ];
    return $paneles[$rol] ?? 'index.php';
}

// Candado de seguridad: sin sesión va al login; con otro rol, a su propio panel
function requerir_rol($rolesPermitidos)
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . RAIZ . 'login.php?error=acceso_denegado');
        exit;
    }
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        avisar('Esa página no corresponde a tu tipo de cuenta.', 'error');
        header('Location: ' . RAIZ . panel_de_rol($_SESSION['rol']));
        exit;
    }
}

// Aviso que se muestra una sola vez en la siguiente página (después de un header Location)
function avisar($texto, $tipo = 'exito')
{
    $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
}

function mostrar_aviso()
{
    if (empty($_SESSION['aviso'])) {
        return;
    }
    $aviso = $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    $iconos = ['exito' => 'check_circle', 'error' => 'error', 'info' => 'schedule'];
    echo '<div class="aviso aviso-' . e($aviso['tipo']) . ' mb-4" role="status">'
        . icono($iconos[$aviso['tipo']] ?? 'check_circle')
        . '<div>' . e($aviso['texto']) . '</div></div>';
}


// ---------------------------------------------------------- consultas

// Ejecuta un SELECT preparado y regresa TODAS las filas en un arreglo.
// $tipos y $valores son los mismos de mysqli_stmt_bind_param.
function consultar($conexion, $sql, $tipos = '', $valores = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $filas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $filas;
}

// Igual que consultar(), pero regresa solo la primera fila (o null)
function consultar_uno($conexion, $sql, $tipos = '', $valores = [])
{
    $filas = consultar($conexion, $sql, $tipos, $valores);
    return $filas[0] ?? null;
}

// INSERT, UPDATE o DELETE preparado. Regresa true si se ejecutó.
function ejecutar($conexion, $sql, $tipos = '', $valores = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

// Identidad del sitio (nombre, logo, ícono, mensaje de temporada...)
function obtener_sitio($conexion)
{
    $sitio = consultar_uno($conexion, "SELECT * FROM sitio WHERE id_sitio = 1");
    $sitio['logo_url'] = $sitio['logo'] ? RAIZ . 'uploads/sitio/' . $sitio['logo'] : RAIZ . 'assets/img/logo.png';
    $sitio['favicon_url'] = $sitio['favicon'] ? RAIZ . 'uploads/sitio/' . $sitio['favicon'] : RAIZ . 'assets/img/favicon.png';
    return $sitio;
}

function obtener_empresa($conexion)
{
    return consultar_uno($conexion, "SELECT * FROM empresa WHERE id_empresa = 1");
}

// Una página pública por su clave ('inicio', 'quienes-somos', ...)
function obtener_pagina($conexion, $clave)
{
    return consultar_uno($conexion, "SELECT * FROM paginas WHERE clave = ?", "s", [$clave]);
}

// Secciones visibles de una página, en orden
function obtener_secciones($conexion, $idPagina)
{
    return consultar($conexion, "SELECT * FROM secciones WHERE id_pagina = ? AND activo = 1 ORDER BY orden, id_seccion", "i", [$idPagina]);
}

function obtener_pasos($conexion, $idSeccion)
{
    return consultar($conexion, "SELECT texto FROM pasos WHERE id_seccion = ? ORDER BY orden, id_paso", "i", [$idSeccion]);
}


// --------------------------------------------------------------- tarifa

// La tarifa vigente: la más reciente cuya fecha de inicio ya llegó
function tarifa_vigente($conexion)
{
    return consultar_uno($conexion, "SELECT * FROM tarifas WHERE vigente_desde <= CURDATE()
                                      ORDER BY vigente_desde DESC, id_tarifa DESC LIMIT 1");
}


// -------------------------------------------------------------- folios

// IN-261125-9KD2V (incidentes). Los viajes (VJ-...) los crea la API de la app.
function nuevo_folio($conexion, $tabla)
{
    $prefijo = ($tabla === 'viajes') ? 'VJ' : 'IN';
    $sql = ($tabla === 'viajes') ? "SELECT 1 FROM viajes WHERE folio = ?" : "SELECT 1 FROM incidentes WHERE folio = ?";
    $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $codigo = '';
        for ($i = 0; $i < 5; $i++) {
            $codigo .= $letras[random_int(0, strlen($letras) - 1)];
        }
        $folio = $prefijo . '-' . date('ymd') . '-' . $codigo;
    } while (consultar_uno($conexion, $sql, "s", [$folio]));
    return $folio;
}


// -------------------------------------------------------------- archivos

// Sube un archivo con las mismas validaciones vistas en clase (tamaño,
// extensión y tipo MIME real). Regresa ['nombre' => ..., 'error' => ...]
//   $carpeta: 'sitio', 'documentos' o 'evidencias' (dentro de uploads/)
function subir_archivo($archivo, $carpeta, $prefijo, $aceptaPdf = false, $maxMb = 5)
{
    if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['nombre' => null, 'error' => 'Selecciona un archivo.'];
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['nombre' => null, 'error' => 'Ocurrió un error al subir el archivo. Inténtalo de nuevo.'];
    }
    if ($archivo['size'] > $maxMb * 1024 * 1024) {
        return ['nombre' => null, 'error' => "El archivo pesa más de $maxMb MB."];
    }

    $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
    $mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if ($aceptaPdf) {
        $extensiones[] = 'pdf';
        $mimes[] = 'application/pdf';
    }

    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($ext, $extensiones) || !in_array($mimeReal, $mimes)) {
        $formatos = $aceptaPdf ? 'JPG, PNG, WEBP o PDF' : 'JPG, PNG o WEBP';
        return ['nombre' => null, 'error' => "Formato no válido. Sube un archivo $formatos."];
    }

    $nombreUnico = uniqid($prefijo . '_', true) . '.' . $ext;
    $destino = __DIR__ . '/../uploads/' . $carpeta . '/';
    if (!file_exists($destino)) {
        mkdir($destino, 0755, true);
    }
    if (!move_uploaded_file($archivo['tmp_name'], $destino . $nombreUnico)) {
        return ['nombre' => null, 'error' => 'No se pudo guardar el archivo en el servidor.'];
    }
    return ['nombre' => $nombreUnico, 'error' => ''];
}

// Borra un archivo de uploads/ (cuando se reemplaza o se elimina el registro)
function borrar_archivo($carpeta, $nombre)
{
    if (empty($nombre)) {
        return;
    }
    $ruta = __DIR__ . '/../uploads/' . $carpeta . '/' . basename($nombre);
    if (file_exists($ruta)) {
        unlink($ruta);
    }
}
?>
