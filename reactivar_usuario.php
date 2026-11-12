<?php
require_once __DIR__ . '/conexion.php';
requiere_admin();
solo_post();

if (!csrf_ok()) {
    flash('error', 'La sesión expiró. Intentá de nuevo.');
} else {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        flash('error', 'Usuario inválido.');
    } else {
        $st = $conexion->prepare('UPDATE usuarios SET archivado_en = NULL WHERE id = :i AND archivado_en IS NOT NULL');
        $st->execute(['i' => $id]);
        if ($st->rowCount() === 1) flash('ok', 'Cuenta reactivada. El usuario ya puede volver a ingresar.');
        else flash('error', 'No se encontró un usuario archivado con ese identificador.');
    }
}
header('Location: usuarios_archivados.php');
exit;
