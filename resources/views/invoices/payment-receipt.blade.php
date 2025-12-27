<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Receipt #{{ $payment->receipt_number ?? $payment->id }} - Invoice #{{ $invoice->id }}</title>
  <style>
    /* Base */
    body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; color: #111827; }
    .container { max-width: 320px; margin: 0 auto; padding: 8px; }
    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .title { font-size: 14px; font-weight: 700; }
    .muted { color: #6B7280; font-size: 10px; }
    .section { margin-top: 8px; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 10px; }
    .label { color: #6B7280; font-size: 10px; }
    .value { font-size: 12px; font-weight: 600; }
    .amount { color: #065F46; font-weight: 700; }
    .footer { margin-top: 10px; font-size: 10px; color: #6B7280; }
    .actions { margin-top: 6px; }
    .btn { display: inline-block; padding: 4px 8px; border: 1px solid #E5E7EB; border-radius: 4px; text-decoration: none; color: #111827; font-size: 11px; }
    .sep { border-top: 1px dashed #D1D5DB; margin: 6px 0; }
    /* Force compact table cells even if inline styles exist */
    .container table { width: 100%; }
    .container th, .container td { padding: 4px !important; font-size: 11px !important; }
    .container th { color: #6B7280; font-weight: 600; }
    .qr img { filter: grayscale(100%) contrast(200%); image-rendering: pixelated; }
    @media print {
      @page { size: 80mm auto; margin: 0; }
      body { margin: 0; }
      .actions { display: none; }
      .container { width: 80mm; max-width: 80mm; padding: 6px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header" style="margin-bottom:6px;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:30px; height:30px; border-radius:6px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
          <img src="{{ asset('images/tooth.png') }}" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div>
          <!-- <div style="font-size:13px; font-weight:800;">{{ config('app.name') }}</div> -->
          <div class="muted">Email: alvinpaka@gmail.com</div>
          <div class="muted">Plot 8, Hill Road,  <br>
                Entebbe, Uganda.</div>
          <div class="muted">Phone: +256 392911652</div>
        </div>
      </div>
    </div>

    <div class="header">
      <div>
        <div class="title">Payment Receipt</div>
        <div class="muted">Receipt #{{ $payment->receipt_number ?? $payment->id }} · Invoice #{{ $invoice->id }}</div>
      </div>
      <div class="muted">{{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="section grid">
      <div>
        <div class="label">Patient</div>
        <div class="value">{{ $invoice->patient->name ?? 'N/A' }}</div>
      </div>
      <div>
        <div class="label">Receipt No.</div>
        <div class="value">{{ $payment->receipt_number ?? $payment->id }}</div>
      </div>
      <div>
        <div class="label">Invoice Amount</div>
        <div class="value">UGX {{ number_format((float)$invoice->amount) }}</div>
      </div>
      <div>
        <div class="label">Original Amount</div>
        <div class="value">UGX {{ number_format((float)$payment->amount) }}</div>
      </div>
      @if(isset($refundedForPayment) && $refundedForPayment > 0)
      <div>
        <div class="label">Refunded Amount</div>
        <div class="value" style="color:#991B1B; font-weight:700;">-UGX {{ number_format((float)$refundedForPayment) }}</div>
      </div>
      <div class="border-t border-gray-200 my-1"></div>
      <div>
        <div class="label font-semibold">Net Received Amount</div>
        <div class="value amount font-bold">UGX {{ number_format((float)($payment->amount - $refundedForPayment)) }}</div>
      </div>
      @else
      <div>
        <div class="label">Net Received Amount</div>
        <div class="value amount">UGX {{ number_format((float)$payment->amount) }}</div>
      </div>
      @endif
      <div>
        <div class="label">Previous Balance</div>
        <div class="value">UGX {{ number_format((float)($prevBalance ?? 0)) }}</div>
      </div>
      <div>
        <div class="label">New Balance</div>
        <div class="value">UGX {{ number_format((float)($newBalance ?? 0)) }}</div>
      </div>
      <div>
        <div class="label">Method</div>
        <div class="value">{{ $payment->method ?? '—' }}</div>
      </div>
      <div>
        <div class="label">Date</div>
        <div class="value">{{ optional($payment->received_at)->format('Y-m-d') ?? '—' }}</div>
      </div>
      <div>
        <div class="label">Reference</div>
        <div class="value">{{ $payment->reference ?? '—' }}</div>
      </div>
      <div>
        <div class="label">Notes</div>
        <div class="value">{{ $payment->notes ?? '—' }}</div>
      </div>
      <div>
        <div class="label">Cashier</div>
        <div class="value">{{ optional($payment->receivedBy)->name ?? '—' }}</div>
      </div>
    </div>

    <div class="sep"></div>

    <!-- <div class="section qr" style="display:flex; align-items:center; gap:8px;">
      <img src="https://quickchart.io/qr?text={{ urlencode($receiptUrl ?? request()->fullUrl()) }}&size=160&margin=1" width="110" height="110" alt="QR" />
      <div class="muted" style="line-height:1.2; font-size:8px;">Scan to view receipt online<br>{{ $receiptUrl ?? request()->fullUrl() }}</div>
    </div> -->

    @if(optional($invoice->treatment)->procedure)
      <div class="section">
        <div class="title" style="font-size:16px; margin-bottom:8px;">Treatment</div>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Procedure</th>
              <th style="text-align:right; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:8px; font-size:14px;">{{ $invoice->treatment->procedure }}</td>
              <td style="padding:8px; font-size:14px; text-align:right;">UGX {{ number_format((float)($invoice->treatment->cost ?? 0)) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endif

    @php
      $treatmentPrescriptions = optional($invoice->treatment)->prescriptions;
      $hasTreatmentPrescriptions = $treatmentPrescriptions && $treatmentPrescriptions->count() > 0;
      $singlePrescription = $invoice->prescription ?? null;
    @endphp

    @if ($hasTreatmentPrescriptions || $singlePrescription)
      <div class="section">
        <div class="title" style="font-size:16px; margin-bottom:8px;">Prescriptions</div>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Medication</th>
              <th style="text-align:left; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Dosage</th>
              <th style="text-align:left; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Reference</th>
              <th style="text-align:right; padding:8px; border-bottom:1px solid #E5E7EB; font-size:12px; color:#6B7280;">Amount</th>
            </tr>
          </thead>
          <tbody>
            @if ($hasTreatmentPrescriptions)
              @foreach ($treatmentPrescriptions as $p)
                <tr>
                  <td style="padding:8px; font-size:14px;">{{ optional($p->medicine)->medicine_name ?? $p->medication ?? '—' }}</td>
                  <td style="padding:8px; font-size:14px;">{{ $p->dosage ?? '—' }}</td>
                  <td style="padding:8px; font-size:14px;">#{{ $p->id }}</td>
                  <td style="padding:8px; font-size:14px; text-align:right;">UGX {{ number_format((float)($p->prescription_amount ?? $p->amount ?? 0)) }}</td>
                </tr>
              @endforeach
            @endif
            @if ($singlePrescription && !$hasTreatmentPrescriptions)
              <tr>
                <td style="padding:8px; font-size:14px;">{{ optional($singlePrescription->medicine)->medicine_name ?? $singlePrescription->medication ?? '—' }}</td>
                <td style="padding:8px; font-size:14px;">{{ $singlePrescription->dosage ?? '—' }}</td>
                <td style="padding:8px; font-size:14px;">#{{ $singlePrescription->id }}</td>
                <td style="padding:8px; font-size:14px; text-align:right;">UGX {{ number_format((float)($singlePrescription->prescription_amount ?? $singlePrescription->amount ?? 0)) }}</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    @endif

    <div class="footer">
      Generated by Vintech Solutions · This receipt acknowledges the payment received against the referenced invoice.
    </div>

    <div class="actions">
      <a href="#" class="btn" onclick="window.print(); return false;">Print</a>
      <a href="{{ route('invoices.show', $invoice->id) }}" class="btn">Back to Invoice</a>
    </div>
  </div>
</body>
</html>
