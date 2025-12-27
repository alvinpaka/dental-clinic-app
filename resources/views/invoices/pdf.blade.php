<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->id }} - Vintech Solutions</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #6b7280;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --light: #f9fafb;
            --dark: #111827;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--dark);
            line-height: 1.4;
            font-size: 11px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            padding: 15px;
            background: #ffffff;
            box-shadow: none;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .logo {
            flex: 1;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo img {
            height: 60px;
            width: auto;
            max-width: 150px;
            object-fit: contain;
        }
        
        .logo-text h1 {
            color: var(--primary);
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 3px 0;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        
        .logo p {
            color: var(--secondary);
            margin: 0;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .invoice-info {
            text-align: right;
        }
        
        .invoice-info h2 {
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 5px 0;
            line-height: 1.2;
        }
        
        .invoice-meta {
            font-size: 12px;
            color: var(--secondary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        .status-pending { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .status-paid { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-overdue { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        
        /* Sections */
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--primary-light);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .grid-col {
            background: #f8fafc;
            border-radius: 6px;
            padding: 10px;
            border: 1px solid var(--gray-200);
        }
        
        .grid-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-item {
            margin-bottom: 4px;
            display: flex;
            font-size: 10px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--gray-700);
            min-width: 100px;
            font-size: 10px;
        }
        
        .detail-value {
            font-weight: 400;
            color: var(--dark);
            flex: 1;
            font-size: 10px;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            margin: 15px 0;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 9px;
        }
        
        thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 6px 8px;
            border: none;
            font-size: 9px;
        }
        
        thead th:first-child {
            border-top-left-radius: 7px;
        }
        
        thead th:last-child {
            border-top-right-radius: 7px;
        }
        
        tbody tr:nth-child(even) {
            background-color: var(--gray-100);
        }
        
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
            font-size: 9px;
            line-height: 1.2;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Totals */
        .totals {
            margin-top: 10px;
            margin-left: auto;
            width: 250px;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            overflow: hidden;
            font-size: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 10px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .total-row:last-child {
            border-bottom: none;
        }
        
        .total-label {
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .total-value {
            font-weight: 600;
        }
        
        .total-amount {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .amount-due { color: var(--danger); }
        .amount-paid { color: var(--success); }
        
        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid var(--gray-200);
            font-size: 9px;
            color: var(--secondary);
            text-align: center;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        .stamp {
            margin: 20px auto 0;
            padding: 10px 20px;
            border: 2px dashed var(--gray-300);
            display: inline-block;
            border-radius: 4px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        /* Print styles */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            
            body {
                font-size: 10px;
                padding: 0;
                margin: 0;
                background: white;
                width: 100%;
                height: 100%;
            }
            
            .container {
                padding: 10px;
                box-shadow: none;
                width: 100%;
                max-width: 100%;
                margin: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo">
                <img src="{{ public_path('images/viclogo.png') }}" alt="Hospital Logo">
                <div class="logo-text">
                    
                <p>
                    Vintech Solutions<br>
                Phone: +256 392911652 <br> Email: alvinpaka@gmail.com
                Plot 8, Hill Road, <br>
                Entebbe, Uganda.
            
                </p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE #{{ $invoice->id }}</h2>
                <div class="invoice-meta">
                    <div>Date: {{ $invoice->created_at->format('M d, Y') }}</div>
                    <div>Due: {{ $invoice->due_date->format('M d, Y') }}</div>
                    <!-- <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst($invoice->status) }}
                    </span> -->
                </div>
            </div>
        </div>

        <!-- Client & Invoice Info -->
        <div class="grid">
            <div class="grid-col">
                <div class="grid-title">Bill To</div>
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $invoice->patient->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $invoice->patient->email }}</span>
                </div>
                @if($invoice->patient->phone)
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $invoice->patient->phone }}</span>
                </div>
                @endif
            </div>
            
            <div class="grid-col">
                <div class="grid-title">Invoice Summary</div>
                <div class="detail-item">
                    <span class="detail-label">Invoice #:</span>
                    <span class="detail-value">{{ $invoice->id }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Invoice Date:</span>
                    <span class="detail-value">{{ $invoice->created_at->format('M d, Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Due Date:</span>
                    <span class="detail-value">{{ $invoice->due_date->format('M d, Y') }}</span>
            </div>

            <!-- Client & Invoice Info -->
            <div class="grid">
                <div class="grid-col">
                    <div class="grid-title">Bill To</div>
                    <div class="detail-item">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">{{ $invoice->patient->name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">{{ $invoice->patient->email }}</span>
                    </div>
                    @if($invoice->patient->phone)
                    <div class="detail-item">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">{{ $invoice->patient->phone }}</span>
                    </div>
                    @endif
        @if($invoice->treatment)
        <div class="section">
            <div class="section-title">Treatment Details</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%">Procedure</th>
                            <th style="width: 15%">Notes</th>
                            <th style="width: 15%; text-align: right;">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $invoice->treatment->procedure }}</td>
                            <td>{{ $invoice->treatment->notes ?? 'No additional notes' }}</td>
                            <td style="text-align: right;">UGX {{ number_format($invoice->treatment->cost) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Prescriptions -->
        @if($invoice->prescription || ($invoice->treatment && $invoice->treatment->prescriptions && count($invoice->treatment->prescriptions) > 0))
        <div class="section">
            <div class="section-title">Prescriptions</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%">Medication</th>
                            <th style="width: 15%">Dosage</th>
                            <th style="width: 15%; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($invoice->prescription)
                        <tr>
                            <td>{{ $invoice->prescription->medication ?? 'N/A' }}</td>
                            <td>{{ $invoice->prescription->dosage ?? 'N/A' }}</td>
                            <td style="text-align: right;">UGX {{ $invoice->prescription->prescription_amount ? number_format($invoice->prescription->prescription_amount) : '0' }}</td>
                        </tr>
                        @endif
                        
                        @if($invoice->treatment && $invoice->treatment->prescriptions)
                            @foreach($invoice->treatment->prescriptions as $prescription)
                            <tr>
                                <td>{{ $prescription->medicine ? $prescription->medicine->medicine_name : ($prescription->medication ?? 'N/A') }}</td>
                                <td>{{ $prescription->dosage ?? 'N/A' }}</td>
                                <td style="text-align: right;">UGX {{ $prescription->prescription_amount ? number_format($prescription->prescription_amount) : '0' }}</td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Payment Summary -->
        <div class="section">
            <div class="section-title">Payment Summary</div>
            <div class="totals">
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">UGX {{ number_format($invoice->amount) }}</span>
                </div>
                @php
                    $totalRefunded = $totalRefunded ?? 0;
                    $netAmount = $invoice->amount - $totalRefunded;
                @endphp
                @if($totalRefunded > 0)
                <div class="total-row">
                    <span class="total-label">Total Refunded:</span>
                    <span class="total-value" style="color: #991B1B;">-UGX {{ number_format($totalRefunded) }}</span>
                </div>
                <div class="total-row" style="border-top: 1px solid #e5e7eb;">
                    <span class="total-label" style="font-weight: 600;">Net Amount Due:</span>
                    <span class="total-value" style="font-weight: 600;">UGX {{ number_format($netAmount) }}</span>
                </div>
                @endif
                @if(isset($invoice->paid_total) && $invoice->paid_total > 0)
                <div class="total-row">
                    <span class="total-label">Total Paid:</span>
                    <span class="total-value">UGX {{ number_format($invoice->paid_total) }}</span>
                </div>
                @endif
                @if(isset($invoice->balance))
                <div class="total-row" style="background-color: {{ $invoice->balance > 0 ? '#fef2f2' : '#f0fdf4' }}; padding: 12px 15px;">
                    <span class="total-label" style="font-weight: 600; color: {{ $invoice->balance > 0 ? '#b91c1c' : '#065f46' }};">
                        {{ $invoice->balance > 0 ? 'Amount Due:' : 'Refund Amount:' }}
                    </span>
                    <span class="total-value" style="font-weight: 700; font-size: 16px; color: {{ $invoice->balance > 0 ? '#b91c1c' : '#065f46' }};">
                        UGX {{ number_format(abs($invoice->balance)) }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment History -->
        @if(isset($invoice->payments) && count($invoice->payments) > 0)
        <div class="section">
            <div class="section-title">Payment History</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%">Date</th>
                            <th style="width: 15%; text-align: right;">Amount</th>
                            <!-- <th style="width: 10%; text-align: right;">Refunded</th> -->
                            <th style="width: 10%; text-align: right;">Net</th>
                            <th style="width: 15%">Method</th>
                            <th style="width: 15%">Reference</th>
                            <th style="width: 20%">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->received_at ? \Carbon\Carbon::parse($payment->received_at)->format('M d, Y') : 'N/A' }}</td>
                            @php
                                $refundedAmount = $payment->refunds->sum('amount');
                                $netAmount = $payment->amount - $refundedAmount;
                            @endphp
                            <td style="text-align: right;">UGX {{ number_format($payment->amount) }}</td>
                            <!-- <td style="text-align: right; color: #991B1B;">@if($refundedAmount > 0)-UGX {{ number_format($refundedAmount) }}@else-@endif</td> -->
                            <td style="text-align: right; font-weight: {{ $netAmount > 0 ? 'bold' : 'normal' }}; color: {{ $netAmount < 0 ? '#991B1B' : 'inherit' }}">
                                UGX {{ number_format($netAmount) }}
                            </td>
                            <td>{{ $payment->method ?? 'N/A' }}</td>
                            <td>{{ $payment->reference ?? 'N/A' }}</td>
                            <td>{{ $payment->notes ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Refunds -->
        @if(isset($refunds) && count($refunds) > 0)
        <div class="section">
            <div class="section-title">Refunds</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%">Date</th>
                            <th style="width: 15%; text-align: right;">Amount</th>
                            <th style="width: 20%">Reason</th>
                            <th style="width: 30%">Notes</th>
                            <th style="width: 20%">Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($refunds as $refund)
                        <tr>
                            <td>{{ $refund->refunded_at ? \Carbon\Carbon::parse($refund->refunded_at)->format('M d, Y') : 'N/A' }}</td>
                            <td style="text-align: right;">UGX {{ number_format($refund->amount) }}</td>
                            <td>{{ $refund->reason ?? 'N/A' }}</td>
                            <td>{{ $refund->notes ?? 'N/A' }}</td>
                            <td>{{ $refund->refunded_by_user->name ?? 'System' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->notes)
        <div class="section">
            <div class="section-title">Additional Notes</div>
            <div style="padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary);">
                {!! nl2br(e($invoice->notes)) !!}
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="stamp">
                <div>Authorized Signature</div>
                <div style="margin-top: 10px; border-top: 1px solid #d1d5db; padding-top: 5px;">
                    Vintech Solutions
                </div>
            </div>
            <p>Generated on {{ now()->format('M d, Y \a\t h:i A') }}</p>
            <p>Thank you for choosing Vintech Solutions. For any inquiries, please contact our office.</p>
            <p style="margin-top: 10px;">
                <strong>Phone:</strong> +256 700 123456 | 
                <strong>Email:</strong> info@victoriadental.com | 
                <strong>Website:</strong> www.victoriadental.com
            </p>
        </div>
    </div>
</body>
</body>
</html>