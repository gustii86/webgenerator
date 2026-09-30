<?php
require __DIR__ . '/config.php';
requerir_login();

$idUsuario = (int) $_SESSION['idUsuario'];
$error = '';

// ---------- Crear nueva web ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'Solicitud inválida. Recarga la página.';
    } else {
        // Solo letras minúsculas y números, máximo 30 caracteres
        $nombre = strtolower(trim($_POST['nombre'] ?? ''));
        $nombre = substr(preg_replace('/[^a-z0-9]/', '', $nombre), 0, 30);

        if ($nombre === '') {
            $error = 'Escribe un nombre válido (letras y números).';
        } else {
            $dominio = $nombre . $idUsuario;   // nombre + idUsuario

            $st = $pdo->prepare('SELECT 1 FROM webs WHERE dominio = ?');
            $st->execute([$dominio]);

            if ($st->fetch()) {
                $error = "El dominio «{$dominio}» ya existe. Prueba con otro nombre.";
            } else {
                $ins = $pdo->prepare('INSERT INTO webs (idUsuario, dominio) VALUES (?, ?)');
                $ins->execute([$idUsuario, $dominio]);
                $idWeb = (int) $pdo->lastInsertId();

                // Ejecutar el script (desde la carpeta de la app y con el argumento escapado)
                chdir(__DIR__);
                shell_exec('./wix.sh ' . escapeshellarg($dominio) . ' 2>&1');

                // Comprobar que el script realmente creó la carpeta
                if (!is_dir(ruta_web($dominio))) {
                    $pdo->prepare('DELETE FROM webs WHERE idWeb = ?')->execute([$idWeb]);
                    $error = 'wix.sh no pudo crear la web. Revisa permisos y que la carpeta sitios/ exista.';
                } else {
                    flash('ok', "Web «{$dominio}» creada correctamente.");
                    redirigir('panel.php');   // evita reenvío del formulario al recargar
                }
            }
        }
    }
}

// ---------- Listado de webs del usuario ----------
$st = $pdo->prepare('SELECT idWeb, dominio, fechaCreacion FROM webs WHERE idUsuario = ? ORDER BY idWeb DESC');
$st->execute([$idUsuario]);
$webs = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>webgenerator TU_NOMBRE - Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>webgenerator TU_NOMBRE</h1>
    <p>Sesión: <?= e($_SESSION['email'] ?? '') ?> · <a href="logout.php">Cerrar sesión</a></p>

    <?= flash_mostrar() ?>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

    <h2>Crear nueva web</h2>
    <form method="post" action="panel.php">
        <?= csrf_campo() ?>
        <input type="text" name="nombre" placeholder="Nombre de la web (ej: minegocio)" required maxlength="30" pattern="[A-Za-z0-9]+" title="Solo letras y números">
        <button type="submit">Crear web</button>
    </form>

    <h2>Mis webs</h2>
    <?php if (!$webs): ?>
        <p>Aún no has creado ninguna web.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($webs as $w): ?>
            <li>
                <span><strong><?= e($w['dominio']) ?></strong><br><small><?= e($w['fechaCreacion']) ?></small></span>
                <span class="acciones">
                    <a href="descargar.php?id=<?= (int) $w['idWeb'] ?>">descargar web</a>
                    <a href="eliminar.php?id=<?= (int) $w['idWeb'] ?>&amp;token=<?= e(csrf_token()) ?>"
                       onclick="return confirm('¿Eliminar <?= e($w['dominio']) ?>? Esta acción no se puede deshacer.')">Eliminar</a>
                </span>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
