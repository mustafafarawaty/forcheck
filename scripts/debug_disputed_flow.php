<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tx = App\Models\WalletTransaction::where('type', 'session_cancelled_pending')->latest('id')->first();
if (! $tx) {
    echo "missing tx\n";
    exit(1);
}

$s = App\Models\TeacherSession::with('student', 'teacher')->find($tx->teacher_session_id);
if (! $s) {
    echo "missing session\n";
    exit(1);
}

echo "before student=" . ($s->student?->balance ?? 'null') . " teacher=" . ($s->teacher?->balance ?? 'null') . " tx={$tx->id} amt={$tx->amount} status={$tx->status}\n";

$wallet = app(App\Services\Wallet\WalletService::class);
$wallet->updateTransaction($tx, 70.00, 'completed', 'test approve', null);

$s2 = App\Models\TeacherSession::with('student', 'teacher')->find($s->id);
echo "after student=" . ($s2->student?->balance ?? 'null') . " teacher=" . ($s2->teacher?->balance ?? 'null') . " status=" . $s2->payment_status . "\n";

$tx2 = App\Models\WalletTransaction::find($tx->id);
echo "tx after amount=" . $tx2->amount . " status=" . $tx2->status . "\n";
