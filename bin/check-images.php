#!/usr/bin/env php
<?php

$newsDir = __DIR__ . '/../public/uploads/news/';
$predefinedDir = $newsDir . 'predefined/';

echo "Verificando estructura de imágenes...\n\n";

// Verificar directorio de noticias
if (!is_dir($newsDir)) {
    mkdir($newsDir, 0777, true);
    echo "✓ Creado directorio: $newsDir\n";
}

// Verificar directorio predefinido
if (!is_dir($predefinedDir)) {
    mkdir($predefinedDir, 0777, true);
    echo "✓ Creado directorio: $predefinedDir\n";
}

// Contar imágenes
$predefinedImages = glob($predefinedDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
$userImages = glob($newsDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

echo "\nEstadísticas:\n";
echo "- Imágenes predefinidas: " . count($predefinedImages) . "\n";
echo "- Imágenes de usuarios: " . count($userImages) . "\n";

if (count($predefinedImages) === 0) {
    echo "\n⚠️  No hay imágenes predefinidas. Descarga algunas para el sistema automático.\n";
}

echo "\n✓ Verificación completa\n";