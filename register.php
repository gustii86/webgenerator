<?php
require __DIR__ . '/config.php';

if (usuario_logueado()) {
    redirigir('panel.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'Solicitud inválida. Recarga la página.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'El email no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $st->execute([$email]);

        if ($st->fetch()) {
            $error = 'Ese email ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare('INSERT INTO usuarios (email, password) VALUES (?, ?)');
            $ins->execute([$email, $hash]);

            flash('ok', 'Cuenta creada. Ya puedes iniciar sesión.');
            redirigir('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>webgenerator TU_NOMBRE - Registro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>webgenerator TU_NOMBRE</h1>
    <h2>Crear cuenta</h2>

    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

    <form method="post" action="register.php">
        <?= csrf_campo() ?>
        <input type="email" name="email" placeholder="Email" required value="<?= e($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Contraseña (mín. 8 caracteres)" required minlength="8">
        <input type="password" name="password2" placeholder="Repetir contraseña" required minlength="8">
        <button type="submit">Registrarme</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</body>
</html>
