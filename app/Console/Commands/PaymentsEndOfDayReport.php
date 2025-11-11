<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\CashMovement;
use App\Models\User;
use App\Mail\EodReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PaymentsEndOfDayReport extends Command
{
    protected $signature = 'payments:eod-report {--date=} {--output=} {--email=}';

    protected $description = 'Generate end-of-day payments summary by method and cash movements';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $this->info('Generating EOD report for '.$date->toDateString());

        $payments = Payment::whereBetween('received_at', [$start, $end])->get();
        $byMethod = [
            'cash' => 0.0,
            'card' => 0.0,
            'mobile_money' => 0.0,
            'bank_transfer' => 0.0,
            'other' => 0.0,
        ];
        foreach ($payments as $p) {
            $method = $p->method ?: 'other';
            if (!array_key_exists($method, $byMethod)) $method = 'other';
            $byMethod[$method] += (float) $p->amount;
        }

        $movements = CashMovement::whereBetween('created_at', [$start, $end])->get();
        $inflow = $movements->where('type', 'inflow')->sum('amount');
        $outflow = $movements->where('type', 'outflow')->sum('amount');

        $output = $this->option('output') ?: storage_path('app/reports/payments-eod-'.$date->format('Ymd').'.csv');
        @mkdir(dirname($output), 0777, true);
        $fh = fopen($output, 'w');
        fputcsv($fh, ['date', $date->toDateString()]);
        fputcsv($fh, []);
        fputcsv($fh, ['Payments by Method']);
        fputcsv($fh, ['method', 'amount']);
        foreach ($byMethod as $method => $amt) {
            fputcsv($fh, [$method, number_format($amt, 2, '.', '')]);
        }
        fputcsv($fh, []);
        fputcsv($fh, ['Cash Movements']);
        fputcsv($fh, ['type', 'amount']);
        fputcsv($fh, ['inflow', number_format((float) $inflow, 2, '.', '')]);
        fputcsv($fh, ['outflow', number_format((float) $outflow, 2, '.', '')]);
        fclose($fh);

        $this->info('EOD report written to '.$output);

        // Optional email
        $emailOpt = $this->option('email');
        if ($emailOpt !== null) {
            $recipients = [];
            if ($emailOpt === 'admins') {
                $recipients = User::role('admin')->whereNotNull('email')->pluck('email')->all();
            } elseif (trim($emailOpt) !== '') {
                $recipients = array_filter(array_map('trim', explode(',', $emailOpt)));
            }
            if (!empty($recipients)) {
                try {
                    Mail::to($recipients)->send(new EodReportMail($date, $output, $byMethod, (float) $inflow, (float) $outflow));
                    $this->info('EOD report emailed to: '.implode(', ', $recipients));
                } catch (\Throwable $e) {
                    $this->error('Failed to email EOD report: '.$e->getMessage());
                }
            } else {
                $this->warn('No recipients resolved for --email option.');
            }
        }
        return self::SUCCESS;
    }
}
