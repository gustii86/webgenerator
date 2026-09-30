<?php
require __DIR__ . '/config.php';
requerir_login();

$web = obtener_web_propia($pdo, (int) ($_GET['id'] ?? 0));   // solo webs propias
if (!$web) {
    http_response_code(404);
    exit('Web no encontrada.');
}

$dominio = $web['dominio'];
$ruta    = realpath(ruta_web($dominio));

if ($ruta === false || !is_dir($ruta)) {
    flash('error', 'La carpeta de la web no existe en el servidor.');
    redirigir('panel.php');
}
if (!class_exists('ZipArchive')) {
    exit('La extensión zip de PHP no está habilitada.');
}

$tmp = tempnam(sys_get_temp_dir(), 'web_');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    exit('No se pudo crear el ZIP.');
}

$zip->addEmptyDir($dominio);
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $item) {
    if ($item->isLink()) {
        continue;
    }
    $relativa = $dominio . '/' . substr($item->getPathname(), strlen($ruta) + 1);
    if ($item->isDir()) {
        $zip->addEmptyDir($relativa);
    } else {
        $zip->addFile($item->getPathname(), $relativa);
    }
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $dominio . '.zip"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
unlink($tmp);
exit;
