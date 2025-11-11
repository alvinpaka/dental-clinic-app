<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class EodReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Carbon $date,
        public string $csvPath,
        public array $byMethod,
        public float $inflow,
        public float $outflow,
    ) {}

    public function build()
    {
        return $this->subject('End of Day Payments Report - '.$this->date->toDateString())
            ->view('emails.eod_report')
            ->with([
                'date' => $this->date,
                'byMethod' => $this->byMethod,
                'inflow' => $this->inflow,
                'outflow' => $this->outflow,
            ])
            ->attach($this->csvPath, [
                'as' => 'payments-eod-'.$this->date->format('Ymd').'.csv',
                'mime' => 'text/csv',
            ]);
    }
}
