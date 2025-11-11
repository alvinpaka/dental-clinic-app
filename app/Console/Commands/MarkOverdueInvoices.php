<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use Carbon\Carbon;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue {--dry-run : Show what would change without updating}';

    protected $description = 'Mark invoices as overdue when past due_date and not paid';

    public function handle(): int
    {
        $today = Carbon::today();
        $query = Invoice::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $today);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("[Dry Run] Would mark {$count} invoice(s) as overdue.");
            return self::SUCCESS;
        }

        $updated = $query->update(['status' => 'overdue']);
        $this->info("Marked {$updated} invoice(s) as overdue.");
        return self::SUCCESS;
    }
}
