<?php
// setup.php - Creates the schema. Visit once after deploying.
$secret = getenv('IMPORT_SECRET') ?: 'changeme';
if (php_sapi_name() !== 'cli') {
    $provided = $_GET['secret'] ?? '';
    if ($provided !== $secret) {
        http_response_code(403);
        die('Forbidden');
    }
}
require_once 'db.php';

$sql = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($sql);
echo "Schema created.\n";
echo "Next: visit /import.php?secret=YOUR_SECRET to load data.\n";
