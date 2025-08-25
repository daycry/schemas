<?php

// Definir APPPATH si no existe
if (!defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/app/');
}

require_once 'vendor/autoload.php';

use Daycry\Schemas\Config\Schemas;

// Test de la nueva configuración
$config = new Schemas();

echo "Configuración TTL:\n";
echo "Cache TTL: " . $config->cache['ttl'] . " segundos\n";
echo "Cache habilitado: " . ($config->cache['enabled'] ? 'Sí' : 'No') . "\n";
echo "Handler de cache: " . $config->cache['handler'] . "\n";

// Verificar que no existe la propiedad ttl anterior
echo "\nVerificando eliminación de \$ttl deprecated:\n";
if (property_exists($config, 'ttl')) {
    echo "❌ ERROR: La propiedad \$ttl todavía existe\n";
} else {
    echo "✅ OK: La propiedad \$ttl ha sido eliminada correctamente\n";
}

echo "\nConfiguraciones principales:\n";
echo "- defaultGroup: " . $config->defaultGroup . "\n";
echo "- silent: " . ($config->silent ? 'true' : 'false') . "\n";
echo "- enableValidation: " . ($config->enableValidation ? 'true' : 'false') . "\n";
echo "- enablePerformanceAnalysis: " . ($config->enablePerformanceAnalysis ? 'true' : 'false') . "\n";
echo "- enableIntelligentCache: " . ($config->enableIntelligentCache ? 'true' : 'false') . "\n";

// Limpiar archivo de test
unlink(__FILE__);
