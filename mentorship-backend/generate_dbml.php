<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$database = config('database.connections.mysql.database');

$columns = DB::select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_KEY 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = ?
", [$database]);

$schema = [];
foreach ($columns as $col) {
    $schema[$col->TABLE_NAME][] = [
        'name' => $col->COLUMN_NAME,
        'type' => $col->DATA_TYPE,
        'is_pk' => $col->COLUMN_KEY === 'PRI'
    ];
}

$dbml = "";
foreach ($schema as $tableName => $cols) {
    $dbml .= "Table $tableName {\n";
    foreach ($cols as $col) {
        $pk = $col['is_pk'] ? ' [primary key]' : '';
        $dbml .= "  {$col['name']} {$col['type']}$pk\n";
    }
    $dbml .= "}\n\n";
}

echo $dbml;
