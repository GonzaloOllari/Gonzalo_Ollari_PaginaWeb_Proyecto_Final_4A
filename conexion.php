<?php
// ===== Configuración del taller =====
const TALLER_NOMBRE    = 'Taller Ollari';
const TALLER_DIRECCION = 'Santo Tomé 5147, esquina Las Colonias';
const TALLER_MAPA      = 'Santo Tomé 5147 y Las Colonias, Ciudad Autónoma de Buenos Aires, Argentina';
const TALLER_TEL       = '11 5143-1316';
const TALLER_HORARIO   = 'Lunes a viernes de 9 a 12 hs y de 15 a 18 hs. Sábados y domingos cerrado';
const TALLER_PAGO      = 'El servicio se paga en el taller. Aceptamos transferencia bancaria o efectivo.';
const TALLER_CAPACIDAD_DIA   = 2;  // turnos máximos por día

date_default_timezone_set('America/Argentina/Buenos_Aires');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
try {
    $conexion = new PDO('mysql:host=localhost;dbname=taller_mecanico;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_campo() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_ok() { return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
function es_admin() { return ($_SESSION['rol'] ?? '') === 'admin'; }
function requiere_login() {
    global $conexion;
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
    // Se revalida contra la base: si la cuenta fue archivada o ya no existe, se cierra la sesión
    $st = $conexion->prepare('SELECT nombre, rol, archivado_en FROM usuarios WHERE id = :i');
    $st->execute(['i' => (int)$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || $u['archivado_en'] !== null) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?archivada=1');
        exit;
    }
    $_SESSION['nombre'] = $u['nombre'];
    $_SESSION['rol'] = $u['rol'];   // el rol siempre sale de la base, no de lo que quedó en la sesión
}
function requiere_admin() { requiere_login(); if (!es_admin()) { http_response_code(403); exit('Acceso denegado.'); } }

// ===== Mensajes que sobreviven a una redirección =====
function flash($tipo, $txt) { $_SESSION['flash'] = ['tipo' => $tipo, 'txt' => $txt]; }
function flash_mostrar() {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    echo '<div class="msg ' . ($f['tipo'] === 'ok' ? 'ok' : 'error') . '">' . e($f['txt']) . '</div>';
}

// Las acciones que modifican datos solo se aceptan por POST
function solo_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }
}

// ===== Validación compartida de usuarios (registro de admin y edición) =====
// Devuelve '' si todo está bien, o el mensaje de error.
function validar_usuario(array $d, bool $passObligatoria): string {
    $pass = $d['password'] ?? '';
    if ($d['nombre'] === '' || $d['apellido'] === '') return 'Completá nombre y apellido.';
    if (mb_strlen($d['nombre']) > 80 || mb_strlen($d['apellido']) > 80) return 'El nombre y el apellido pueden tener hasta 80 caracteres.';
    if (!preg_match('/^\d{7,9}$/', $d['dni'])) return 'El DNI debe tener entre 7 y 9 números, sin puntos.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 254) return 'Ingresá un email válido.';
    if ($passObligatoria || $pass !== '') {
        if (strlen($pass) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
        if (strlen($pass) > 72) return 'La contraseña no puede superar los 72 caracteres.';
    }
    return '';
}

// Devuelve un mensaje si el DNI o el email ya pertenecen a OTRO usuario ('' si están libres)
function usuario_duplicado(PDO $c, string $dni, string $email, int $excluirId = 0): string {
    $st = $c->prepare('SELECT dni, email FROM usuarios WHERE (dni = :d OR email = :e) AND id <> :i');
    $st->execute(['d' => $dni, 'e' => $email, 'i' => $excluirId]);
    $dupDni = $dupMail = false;
    foreach ($st->fetchAll() as $f) {
        if ($f['dni'] === $dni) $dupDni = true;
        if (strcasecmp($f['email'], $email) === 0) $dupMail = true;
    }
    if ($dupDni && $dupMail) return 'Ya existe un usuario con ese DNI y con ese email.';
    if ($dupDni) return 'Ya existe un usuario con ese DNI.';
    if ($dupMail) return 'Ya existe un usuario con ese email.';
    return '';
}

function cabecera($titulo) { ?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> | <?= e(TALLER_NOMBRE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="estilos.css?v=<?= filemtime(__DIR__ . '/estilos.css') ?>">
</head><body>
<header class="top"><div class="wrap bar">
  <a class="marca" href="index.php"><?= e(TALLER_NOMBRE) ?></a>
  <nav>
    <a href="index.php">Inicio</a>
    <a href="turnos.php">Turnos</a>
    <a href="contacto.php">Contacto</a>
    <?php if (!empty($_SESSION['uid'])): ?>
      <a href="panel.php">Mi panel</a>
      <?php if (es_admin()): ?><a href="admin.php">Administración</a><a href="admin_usuarios.php">Usuarios</a><?php endif; ?>
      <a href="cerrar_sesion.php">Salir (<?= e($_SESSION['nombre']) ?>)</a>
    <?php else: ?>
      <a href="login.php">Ingresar</a><a class="btn chico" href="registro.php">Crear cuenta</a>
    <?php endif; ?>
  </nav>
</div></header>
<main class="wrap">
<?php }

function pie() { ?>
</main>
<footer class="pie">
  <div class="wrap">Página realizada por Gonzalo Ollari | 4A | Trabajo Práctico Final | 2026</div>
</footer>
</body></html>
<?php }
