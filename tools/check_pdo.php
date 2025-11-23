<?php
require __DIR__ . '/../bootstrap/legacy.php';
$app = legacy_app();
$pdo = $app->make(PDO::class);
$count = $pdo->query('SELECT COUNT(*) FROM ml_prediction_log')->fetchColumn();
$pdo->exec('CREATE TEMPORARY TABLE IF NOT EXISTS tmp_schema_check (id INT)');
$pdo->exec('DROP TABLE IF EXISTS tmp_schema_check');
echo "ml_prediction_log rows={$count}\n";
echo "temporary_table_ok=1\n";
