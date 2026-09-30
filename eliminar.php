<?php
require __DIR__ . '/config.php';
requerir_login();

if (!csrf_valido($_GET['token'] ?? null)) {
    flash('error', 'Solicitud inválida.');
    redirigir('panel.php');
}

$web = obtener_web_propia($pdo, (int) ($_GET['id'] ?? 0));   // solo webs propias
if (!$web) {
    flash('error', 'Web no encontrada.');
    redirigir('panel.php');
}

borrar_carpeta(ruta_web($web['dominio']));

$del = $pdo->prepare('DELETE FROM webs WHERE idWeb = ? AND idUsuario = ?');
$del->execute([$web['idWeb'], $_SESSION['idUsuario']]);

flash('ok', 'Web «' . $web['dominio'] . '» eliminada.');
redirigir('panel.php');
