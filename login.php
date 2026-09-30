<?php
require __DIR__ . '/config.php';

if (usuario_logueado()) {
    redirigir('panel.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $error = 'Solicitud inválida. Recarga la página.';
    } else {
        $st = $pdo->prepare('SELECT idUsuario, password FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $usuario = $st->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);           // evita session fixation
            $_SESSION['idUsuario'] = (int) $usuario['idUsuario'];
            $_SESSION['email']     = $email;
            redirigir('panel.php');
        }
        $error = 'Email o contraseña incorrectos.';  // mensaje genérico a propósito
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>webgenerator TU_NOMBRE - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>webgenerator TU_NOMBRE</h1>

    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <?= flash_mostrar() ?>

    <form method="post" action="login.php">
        <?= csrf_campo() ?>
        <input type="email" name="email" placeholder="Email" required value="<?= e($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
</body>
</html>
