<?php
// ---------- Configuración ----------
const DB_HOST = 'localhost';
const DB_NAME = 'webgenerator';
const DB_USER = 'adm_webgenerator';
const DB_PASS = 'webgenerator2024';

// Carpeta donde wix.sh crea cada web: sitios/<dominio>
const WEBS_DIR = __DIR__ . '/sitios';

// ---------- Sesión segura ----------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ---------- Conexión PDO ----------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Error de conexión a la base de datos.');
}

// ---------- Helpers ----------
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function usuario_logueado(): bool
{
    return isset($_SESSION['idUsuario']);
}

function requerir_login(): void
{
    if (!usuario_logueado()) {
        redirigir('login.php');
    }
}

// CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

// Mensajes flash (se muestran una sola vez)
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $mensaje];
}

function flash_mostrar(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<p class="' . e($f['tipo']) . '">' . e($f['msg']) . '</p>';
}

// Ruta de una web (valida el nombre para evitar path traversal)
function ruta_web(string $dominio): string
{
    if (!preg_match('/^[a-z0-9]{1,100}$/', $dominio)) {
        throw new InvalidArgumentException('Dominio inválido');
    }
    return WEBS_DIR . '/' . $dominio;
}

// Borrado recursivo de una carpeta (sin seguir enlaces simbólicos)
function borrar_carpeta(string $ruta): void
{
    if (is_link($ruta)) {
        unlink($ruta);
        return;
    }
    if (!is_dir($ruta)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($ruta);
}

// Busca una web asegurando que pertenece al usuario logueado
function obtener_web_propia(PDO $pdo, int $idWeb): ?array
{
    $st = $pdo->prepare('SELECT * FROM webs WHERE idWeb = ? AND idUsuario = ?');
    $st->execute([$idWeb, $_SESSION['idUsuario']]);
    return $st->fetch() ?: null;
}
