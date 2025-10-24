#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Cargar variables de entorno
(new Dotenv())->bootEnv(__DIR__ . '/../.env');

// Conectar a la base de datos
$dbUrl = $_ENV['DATABASE_URL'];
preg_match('/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/(.+)/', $dbUrl, $matches);

$host = $matches[3];
$port = $matches[4];
$dbname = $matches[5];
$user = $matches[1];
$pass = $matches[2];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conectado a la base de datos\n\n";
    
    // Directorios
    $predefinedDir = __DIR__ . '/../public/uploads/news/predefined/';
    $newsDir = __DIR__ . '/../public/uploads/news/';
    
    // Obtener todas las noticias con imágenes
    $stmt = $pdo->query("SELECT id, featured_image FROM news WHERE featured_image IS NOT NULL AND featured_image != ''");
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontradas " . count($news) . " noticias con imágenes\n\n";
    
    $copied = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($news as $item) {
        $imageName = $item['featured_image'];
        
        // Si la imagen ya está en la raíz de news/, no hacer nada
        if (file_exists($newsDir . $imageName)) {
            echo "✓ '{$imageName}' ya existe en news/\n";
            $skipped++;
            continue;
        }
        
        // Si la imagen está en predefined/
        if (strpos($imageName, 'predefined/') === 0) {
            $actualImage = str_replace('predefined/', '', $imageName);
            $sourcePath = $predefinedDir . $actualImage;
            
            if (file_exists($sourcePath)) {
                // Copiar la imagen a la raíz de news/
                if (copy($sourcePath, $newsDir . $actualImage)) {
                    // Actualizar la base de datos
                    $updateStmt = $pdo->prepare("UPDATE news SET featured_image = ? WHERE id = ?");
                    $updateStmt->execute([$actualImage, $item['id']]);
                    
                    echo "✓ Copiada y actualizada: '{$actualImage}'\n";
                    $copied++;
                } else {
                    echo "✗ Error al copiar: '{$actualImage}'\n";
                    $errors++;
                }
            } else {
                echo "⚠ No se encontró: '{$sourcePath}'\n";
                $errors++;
            }
        } else {
            // Buscar la imagen en predefined/
            if (file_exists($predefinedDir . $imageName)) {
                if (copy($predefinedDir . $imageName, $newsDir . $imageName)) {
                    echo "✓ Copiada desde predefined/: '{$imageName}'\n";
                    $copied++;
                } else {
                    echo "✗ Error al copiar desde predefined/: '{$imageName}'\n";
                    $errors++;
                }
            } else {
                echo "⚠ Imagen no encontrada en ningún lugar: '{$imageName}'\n";
                $errors++;
            }
        }
    }
    
    echo "\n=== RESUMEN ===\n";
    echo "Copiadas: $copied\n";
    echo "Ya existían: $skipped\n";
    echo "Errores: $errors\n";
    echo "\n✓ Migración completada\n";
    
} catch (PDOException $e) {
    echo "✗ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
}