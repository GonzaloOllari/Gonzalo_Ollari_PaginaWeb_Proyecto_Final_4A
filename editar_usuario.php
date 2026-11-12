<?php
require_once __DIR__ . '/conexion.php';
requiere_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $conexion->prepare('SELECT id, nombre, apellido, dni, email, rol, archivado_en FROM usuarios WHERE id = :i');
$st->execute(['i' => $id]);
$u = $st->fetch();
if (!$u) { flash('error', 'El usuario no existe.'); header('Location: admin_usuarios.php'); exit; }
if ($u['archivado_en'] !== null) { flash('error', 'Ese usuario está archivado. Reactivalo antes de editarlo.'); header('Location: usuarios_archivados.php'); exit; }

$esYo = $id === (int)$_SESSION['uid'];
$error = '';
$v = ['nombre' => $u['nombre'], 'apellido' => $u['apellido'], 'dni' => $u['dni'], 'email' => $u['email'], 'rol' => $u['rol']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nombre', 'apellido', 'dni', 'email'] as $k) $v[$k] = trim($_POST[$k] ?? '');
    // Un admin no puede cambiarse el rol a sí mismo (evita quedarse sin administradores)
    $v['rol'] = $esYo ? $u['rol'] : trim($_POST['rol'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!csrf_ok()) {
        $error = 'La sesión expiró. Recargá la página.';
    } else {
        $error = validar_usuario($v + ['password' => $pass], false);
        if ($error === '' && !in_array($v['rol'], ['usuario', 'admin'], true)) $error = 'El rol elegido no es válido.';
        if ($error === '') $error = usuario_duplicado($conexion, $v['dni'], $v['email'], $id);
        if ($error === '') {
            $campos = 'nombre = :n, apellido = :a, dni = :d, email = :e, rol = :r';
            $par = ['n' => $v['nombre'], 'a' => $v['apellido'], 'd' => $v['dni'], 'e' => $v['email'], 'r' => $v['rol'], 'i' => $id];
            if ($pass !== '') { $campos .= ', password_hash = :p'; $par['p'] = password_hash($pass, PASSWORD_DEFAULT); }
            try {
                $conexion->prepare("UPDATE usuarios SET $campos WHERE id = :i AND archivado_en IS NULL")->execute($par);
                if ($esYo) $_SESSION['nombre'] = $v['nombre'];
                flash('ok', 'Datos del usuario actualizados.');
                header('Location: admin_usuarios.php');
                exit;
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $error = $ex->getCode() === '23000' ? 'Ya existe un usuario con ese DNI o email.' : 'No se pudieron guardar los cambios.';
            }
        }
    }
}
cabecera('Editar usuario'); ?>
<section class="panel" style="max-width:640px;margin:0 auto">
  <h2>Editar usuario</h2>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="dos">
      <div><label for="nombre">Nombre</label><input id="nombre" name="nombre" required maxlength="80" value="<?= e($v['nombre']) ?>"></div>
      <div><label for="apellido">Apellido</label><input id="apellido" name="apellido" required maxlength="80" value="<?= e($v['apellido']) ?>"></div>
    </div>
    <div class="dos">
      <div><label for="dni">DNI</label><input id="dni" name="dni" required inputmode="numeric" value="<?= e($v['dni']) ?>"></div>
      <div><label for="email">Email</label><input id="email" type="email" name="email" required value="<?= e($v['email']) ?>"></div>
    </div>
    <div class="dos">
      <div><label for="rol">Rol</label>
        <select id="rol" name="rol" <?= $esYo ? 'disabled' : '' ?>>
          <option value="usuario" <?= $v['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario (cliente)</option>
          <option value="admin" <?= $v['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
        <?php if ($esYo): ?><p class="ayuda">No podés cambiar tu propio rol.</p><?php endif; ?>
      </div>
      <div><label for="password">Nueva contraseña</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password"><p class="ayuda">Dejala vacía para no cambiarla.</p></div>
    </div>
    <div class="fila">
      <button type="submit">Guardar cambios</button>
      <a class="btn sec" href="admin_usuarios.php">Cancelar</a>
    </div>
  </form>
</section>
<?php pie();
