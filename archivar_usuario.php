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
    } elseif ($id === (int)$_SESSION['uid']) {
        flash('error', 'No podés archivar tu propia cuenta.');
    } else {
        $st = $conexion->prepare('UPDATE usuarios SET archivado_en = NOW() WHERE id = :i AND archivado_en IS NULL');
        $st->execute(['i' => $id]);
        if ($st->rowCount() === 1) flash('ok', 'Usuario archivado. Sus datos se conservan y puede reactivarse desde "Usuarios archivados".');
        else flash('error', 'No se encontró un usuario activo con ese identificador.');
    }
}
header('Location: admin_usuarios.php');
exit;
