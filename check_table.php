<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking treatments table structure...\n";

$columns = Schema::getColumnListing('treatments');
echo "Columns count: " . count($columns) . "\n";
echo "Columns: " . implode(', ', $columns) . "\n";

// Get actual column info
$columnInfo = DB::select("DESCRIBE treatments");
echo "\nDetailed column info:\n";
foreach ($columnInfo as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}
