<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use Carbon\Carbon;

class BackfillInvoicePayments extends Command
{
    protected $signature = 'backfill:invoice-payments {--dry-run : Show what would be changed without writing}';

    protected $description = 'Create reconciliation payments for invoices marked as paid but missing full payments.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $count = 0;
        $totalAmount = 0.0;

        Invoice::with('payments')
            ->where('status', 'paid')
            ->chunkById(500, function ($invoices) use (&$count, &$totalAmount, $dry) {
                foreach ($invoices as $invoice) {
                    $paid = (float) $invoice->payments->sum('amount');
                    $amount = (float) $invoice->amount;
                    $remaining = round(max(0, $amount - $paid), 2);

                    if ($remaining > 0) {
                        $count++;
                        $totalAmount += $remaining;

                        $this->info("Invoice #{$invoice->id}: backfilling UGX {$remaining}");

                        if (!$dry) {
                            $invoice->payments()->create([
                                'amount' => $remaining,
                                'method' => 'backfill',
                                'received_at' => Carbon::now(),
                                'reference' => 'Backfill reconciliation',
                                'notes' => 'Auto-created to reconcile paid invoice without full payments.',
                                'received_by' => null,
                            ]);
                        }
                    }
                }
            });

        $this->info("Backfill complete. Invoices updated: {$count}. Total amount: UGX " . number_format($totalAmount));
        if ($dry) {
            $this->warn('Dry run only. No changes were written.');
        }

        return Command::SUCCESS;
    }
}
