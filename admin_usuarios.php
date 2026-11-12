<?php
require_once __DIR__ . '/conexion.php';
requiere_admin();

$error = '';
$v = ['nombre' => '', 'apellido' => '', 'dni' => '', 'email' => '', 'rol' => 'usuario'];

// ----- Registrar un usuario desde el panel -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!csrf_ok()) {
        $error = 'La sesión expiró. Recargá la página.';
    } else {
        $error = validar_usuario($v + ['password' => $pass], true);
        if ($error === '' && !in_array($v['rol'], ['usuario', 'admin'], true)) $error = 'El rol elegido no es válido.';
        if ($error === '') $error = usuario_duplicado($conexion, $v['dni'], $v['email']);
        if ($error === '') {
            try {
                $conexion->prepare('INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                                    VALUES (:n, :a, :d, :e, :p, :r)')
                    ->execute(['n' => $v['nombre'], 'a' => $v['apellido'], 'd' => $v['dni'], 'e' => $v['email'],
                               'p' => password_hash($pass, PASSWORD_DEFAULT), 'r' => $v['rol']]);
                flash('ok', 'Usuario registrado correctamente.');
                header('Location: admin_usuarios.php');
                exit;
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $error = $ex->getCode() === '23000' ? 'Ya existe un usuario con ese DNI o email.' : 'No se pudo registrar al usuario.';
            }
        }
    }
}

// ----- Búsqueda de usuarios activos -----
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en FROM usuarios WHERE archivado_en IS NULL';
$par = [];
if ($q !== '') {
    $sql .= " AND (nombre LIKE :q1 OR apellido LIKE :q2 OR dni LIKE :q3 OR email LIKE :q4 OR CONCAT(nombre, ' ', apellido) LIKE :q5)";
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $par = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like];
}
$st = $conexion->prepare($sql . ' ORDER BY apellido, nombre LIMIT 500');
$st->execute($par);
$usuarios = $st->fetchAll();
$nArchivados = (int)$conexion->query('SELECT COUNT(*) FROM usuarios WHERE archivado_en IS NOT NULL')->fetchColumn();

cabecera('Usuarios'); ?>
<?php flash_mostrar(); ?>

<section class="panel">
  <h2>Usuarios activos (<?= count($usuarios) ?>)</h2>
  <form class="f" method="get" style="grid-template-columns:3fr auto auto;align-items:end">
    <div><label for="q">Buscar por nombre, apellido, DNI o email</label><input id="q" name="q" value="<?= e($q) ?>"></div>
    <button type="submit">Buscar</button>
    <?php if ($q !== ''): ?><a class="btn sec" href="admin_usuarios.php">Limpiar</a><?php endif; ?>
  </form>
  <p class="ayuda"><a href="usuarios_archivados.php">Ver usuarios archivados (<?= $nArchivados ?>)</a></p>
  <div class="tabla" style="margin-top:.8rem"><table>
    <tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Alta</th><th>Acciones</th></tr>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= e($u['apellido'] . ', ' . $u['nombre']) ?></td>
      <td><?= e($u['dni']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><span class="etq <?= $u['rol'] === 'admin' ? 'pendiente' : '' ?>"><?= e($u['rol']) ?></span></td>
      <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
      <td><div class="fila">
        <a class="btn chico sec" href="editar_usuario.php?id=<?= (int)$u['id'] ?>">Editar</a>
        <?php if ((int)$u['id'] === (int)$_SESSION['uid']): ?>
          <small>(sos vos)</small>
        <?php else: ?>
          <form method="post" action="archivar_usuario.php">
            <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button type="submit" onclick="return confirm('¿Archivar a este usuario? No podrá ingresar, pero sus datos se conservan.')">Archivar</button>
          </form>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endforeach; if (!$usuarios): ?><tr><td colspan="6">No se encontraron usuarios activos.</td></tr><?php endif; ?>
  </table></div>
</section>

<section class="panel">
  <h2>Registrar nuevo usuario</h2>
  <?php if ($error): ?><div class="msg error"><?= e($error) ?></div><?php endif; ?>
  <form class="f" method="post">
    <?= csrf_campo() ?>
    <div class="dos">
      <div><label for="n-nombre">Nombre</label><input id="n-nombre" name="nombre" required maxlength="80" value="<?= e($v['nombre']) ?>"></div>
      <div><label for="n-apellido">Apellido</label><input id="n-apellido" name="apellido" required maxlength="80" value="<?= e($v['apellido']) ?>"></div>
    </div>
    <div class="dos">
      <div><label for="n-dni">DNI</label><input id="n-dni" name="dni" required inputmode="numeric" value="<?= e($v['dni']) ?>"></div>
      <div><label for="n-email">Email</label><input id="n-email" type="email" name="email" required value="<?= e($v['email']) ?>"></div>
    </div>
    <div class="dos">
      <div><label for="n-pass">Contraseña</label><input id="n-pass" type="password" name="password" required minlength="8" autocomplete="new-password"><p class="ayuda">Mínimo 8 caracteres.</p></div>
      <div><label for="n-rol">Rol</label>
        <select id="n-rol" name="rol">
          <option value="usuario" <?= $v['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario (cliente)</option>
          <option value="admin" <?= $v['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select></div>
    </div>
    <button type="submit">Registrar usuario</button>
  </form>
</section>
<?php pie();
