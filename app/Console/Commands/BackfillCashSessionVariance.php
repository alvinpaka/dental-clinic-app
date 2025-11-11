<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashSession;
use App\Models\CashMovement;

class BackfillCashSessionVariance extends Command
{
    protected $signature = 'cash:backfill-session-variance {--dry-run} {--session-id=}';

    protected $description = 'Backfill expected_cash_at_close and variance for existing cash sessions';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $onlyId = $this->option('session-id') ? (int) $this->option('session-id') : null;

        $query = CashSession::query();
        if ($onlyId) {
            $query->where('id', $onlyId);
        }

        $count = 0;
        $updated = 0;
        $query->orderBy('id')->chunk(200, function ($sessions) use (&$count, &$updated, $dry) {
            foreach ($sessions as $s) {
                $count++;
                // Compute expected from opening + cash inflows - cash outflows for this session
                $in = CashMovement::where('cash_session_id', $s->id)
                    ->where('type', 'inflow')
                    ->where('method', 'cash')
                    ->sum('amount');
                $out = CashMovement::where('cash_session_id', $s->id)
                    ->where('type', 'outflow')
                    ->where('method', 'cash')
                    ->sum('amount');
                $expected = (float) $s->opening_amount + (float) $in - (float) $out;

                $variance = null;
                if ($s->closing_amount !== null) {
                    $variance = (float) $s->closing_amount - $expected;
                }

                $needsUpdate = ($s->expected_cash_at_close === null || (float) $s->expected_cash_at_close !== (float) $expected)
                    || ($s->closing_amount !== null && ($s->variance === null || (float) $s->variance !== (float) $variance));

                if ($needsUpdate) {
                    $updated++;
                    if (!$dry) {
                        $s->update([
                            'expected_cash_at_close' => $expected,
                            'variance' => $variance,
                        ]);
                    }
                    $this->line(sprintf(
                        'Session #%d: expected=%.2f, closing=%s, variance=%s %s',
                        $s->id,
                        $expected,
                        $s->closing_amount === null ? 'NULL' : number_format((float) $s->closing_amount, 2, '.', ''),
                        $variance === null ? 'NULL' : number_format((float) $variance, 2, '.', ''),
                        $dry ? '(dry-run)' : ''
                    ));
                }
            }
        });

        $this->info("Processed: {$count}, Updated: {$updated}" . ($dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
