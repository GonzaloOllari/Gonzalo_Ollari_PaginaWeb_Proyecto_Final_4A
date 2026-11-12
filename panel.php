<?php
require_once __DIR__ . '/conexion.php';
requiere_login();

$st = $conexion->prepare('SELECT nombre, apellido, dni, email, rol, creado_en FROM usuarios WHERE id = :i');
$st->execute(['i' => (int)$_SESSION['uid']]);
$u = $st->fetch();

cabecera('Mi panel'); ?>
<section class="panel" style="max-width:640px;margin:0 auto">
  <h2>Mi panel</h2>
  <p class="ayuda">Estos son los datos de tu cuenta.</p>
  <dl class="datos">
    <dt>Nombre</dt><dd><?= e($u['nombre']) ?></dd>
    <dt>Apellido</dt><dd><?= e($u['apellido']) ?></dd>
    <dt>DNI</dt><dd><?= e($u['dni']) ?></dd>
    <dt>Email</dt><dd><?= e($u['email']) ?></dd>
    <dt>Tipo de cuenta</dt><dd><?= $u['rol'] === 'admin' ? 'Administrador' : 'Cliente' ?></dd>
    <dt>Cuenta creada</dt><dd><?= e(date('d/m/Y H:i', strtotime($u['creado_en']))) ?></dd>
  </dl>
  <div class="fila" style="margin-top:1.2rem">
    <a class="btn" href="turnos.php">Mis turnos</a>
    <a class="btn sec" href="contacto.php">Contacto</a>
    <?php if (es_admin()): ?><a class="btn sec" href="admin_usuarios.php">Administrar usuarios</a><?php endif; ?>
    <a class="btn sec" href="cerrar_sesion.php">Cerrar sesión</a>
  </div>
</section>
<?php pie();
