<?php
// Processamento de imagens enviadas pelo admin: valida, redimensiona e gera
// versão grande (1600px) + thumb (800px). Usa GD (disponível no Hostinger).
// Saída em WebP quando suportado, senão JPEG. Remove EXIF (recompressão).

define('IMG_MAX_LARGE', 1600);
define('IMG_MAX_THUMB', 800);
define('IMG_MAX_BYTES', 8 * 1024 * 1024); // 8 MB por foto
define('IMG_MAX_POR_IMOVEL', 30);

function img_usa_webp() {
    return function_exists('imagewebp');
}

/** Carrega o arquivo num recurso GD; preenche $tipo. Retorna null se inválido. */
function _img_load($caminho) {
    $info = @getimagesize($caminho);
    if (!$info) return null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: return @imagecreatefromjpeg($caminho);
        case IMAGETYPE_PNG:  return @imagecreatefrompng($caminho);
        case IMAGETYPE_GIF:  return @imagecreatefromgif($caminho);
        case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($caminho) : null;
    }
    return null;
}

/** Cópia redimensionada (nunca amplia). Sempre retorna um novo recurso. */
function _img_escala($src, $max, $webp) {
    $w = imagesx($src);
    $h = imagesy($src);
    $escala = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $escala));
    $nh = max(1, (int) round($h * $escala));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($webp) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    } else {
        $branco = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $branco);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}

/**
 * Processa UM arquivo de $_FILES (já é um item, não o array todo).
 * Grava em $destDir (com barra final). Retorna o nome do arquivo grande
 * ou lança Exception com mensagem amigável.
 */
function img_processar(array $file, $destDir) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Upload inválido.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Nenhum arquivo enviado.');
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new Exception('Arquivo maior que o limite do servidor.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Falha no upload (código ' . (int) $file['error'] . ').');
    }
    if ($file['size'] > IMG_MAX_BYTES) {
        throw new Exception('Foto acima de 8 MB: ' . e($file['name']));
    }

    $src = _img_load($file['tmp_name']);
    if (!$src) {
        throw new Exception('Formato não suportado (use JPG, PNG ou WebP): ' . e($file['name']));
    }

    if (!is_dir($destDir)) {
        @mkdir($destDir, 0775, true);
    }

    $webp  = img_usa_webp();
    $ext   = $webp ? 'webp' : 'jpg';
    $base  = str_replace('.', '', uniqid('f', true));
    $grande = $base . '.' . $ext;
    $thumb  = $base . '_t.' . $ext;

    $imgG = _img_escala($src, IMG_MAX_LARGE, $webp);
    $imgT = _img_escala($src, IMG_MAX_THUMB, $webp);
    imagedestroy($src);

    if ($webp) {
        imagewebp($imgG, $destDir . $grande, 82);
        imagewebp($imgT, $destDir . $thumb, 80);
    } else {
        imagejpeg($imgG, $destDir . $grande, 85);
        imagejpeg($imgT, $destDir . $thumb, 82);
    }
    imagedestroy($imgG);
    imagedestroy($imgT);

    return $grande;
}

/** Remove o arquivo grande + o thumb correspondente do disco. */
function img_remover_arquivos($destDir, $arquivo) {
    if (preg_match('~^https?://~', $arquivo)) return; // seed (URL externa)
    $g = $destDir . $arquivo;
    if (is_file($g)) @unlink($g);
    $dot = strrpos($arquivo, '.');
    $thumb = $dot !== false ? substr($arquivo, 0, $dot) . '_t' . substr($arquivo, $dot) : $arquivo . '_t';
    if (is_file($destDir . $thumb)) @unlink($destDir . $thumb);
}

/** Apaga a pasta de fotos de um imóvel (usada ao excluir o imóvel). */
function img_remover_pasta($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        @unlink($dir . '/' . $f);
    }
    @rmdir($dir);
}
