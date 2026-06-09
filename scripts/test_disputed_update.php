<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = App\Models\WalletTransaction::find(66);
if (! $tx) {
    echo "missing transaction\n";
    exit(1);
}

$wallets = app(App\Services\Wallet\WalletService::class);
try {
    $wallets->updateTransaction($tx, 50.00, 'completed', 'test approval', null);
    echo "ok\n";
} catch (Throwable $e) {
    echo "error: {$e->getMessage()}\n";
    echo $e->getTraceAsString();
    exit(1);
}
