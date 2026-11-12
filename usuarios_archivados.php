<?php
require_once __DIR__ . '/conexion.php';
requiere_admin();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT id, nombre, apellido, dni, email, rol, archivado_en FROM usuarios WHERE archivado_en IS NOT NULL';
$par = [];
if ($q !== '') {
    $sql .= " AND (nombre LIKE :q1 OR apellido LIKE :q2 OR dni LIKE :q3 OR email LIKE :q4 OR CONCAT(nombre, ' ', apellido) LIKE :q5)";
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $par = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like];
}
$st = $conexion->prepare($sql . ' ORDER BY archivado_en DESC LIMIT 500');
$st->execute($par);
$usuarios = $st->fetchAll();

cabecera('Usuarios archivados'); ?>
<?php flash_mostrar(); ?>
<section class="panel">
  <h2>Usuarios archivados (<?= count($usuarios) ?>)</h2>
  <form class="f" method="get" style="grid-template-columns:3fr auto auto;align-items:end">
    <div><label for="q">Buscar por nombre, apellido, DNI o email</label><input id="q" name="q" value="<?= e($q) ?>"></div>
    <button type="submit">Buscar</button>
    <?php if ($q !== ''): ?><a class="btn sec" href="usuarios_archivados.php">Limpiar</a><?php endif; ?>
  </form>
  <p class="ayuda"><a href="admin_usuarios.php">← Volver a usuarios activos</a></p>
  <div class="tabla" style="margin-top:.8rem"><table>
    <tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Archivado el</th><th>Acción</th></tr>
    <?php foreach ($usuarios as $u): ?>
    <tr>
      <td><?= e($u['apellido'] . ', ' . $u['nombre']) ?></td>
      <td><?= e($u['dni']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($u['rol']) ?></td>
      <td><?= e(date('d/m/Y H:i', strtotime($u['archivado_en']))) ?></td>
      <td><form method="post" action="reactivar_usuario.php">
        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <button type="submit">Reactivar</button>
      </form></td>
    </tr>
    <?php endforeach; if (!$usuarios): ?><tr><td colspan="6">No hay usuarios archivados.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php pie();
