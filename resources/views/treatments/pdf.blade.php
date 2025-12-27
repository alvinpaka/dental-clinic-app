<!DOCTYPE html>
<html>
<head>
    <title>Treatment Report - {{ $treatment->procedure }} - Vintech Solutions</title>
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
        }
        
        .logo h1 {
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
        
        .treatment-info {
            text-align: right;
        }
        
        .treatment-info h2 {
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 5px 0;
            line-height: 1.2;
        }
        
        .treatment-meta {
            font-size: 12px;
            color: var(--secondary);
        }
        
        .treatment-id {
            background-color: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
            display: inline-block;
        }
        
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
        
        .amount-highlight {
            color: var(--success);
            font-weight: 600;
        }
        
        /* Notes */
        .notes-box {
            background: #f8fafc;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .attachment-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--warning);
        }
        
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
                <h1>Vintech Solutions</h1>
                <p>Phone: +256 392911652 <br> Email: alvinpaka@gmail.com
                Plot 8, Hill Road, <br>
                Entebbe, Uganda.</p>
            </div>
            <div class="treatment-info">
                <h2>TREATMENT REPORT</h2>
                <div class="treatment-meta">
                    <div>{{ $treatment->procedure }}</div>
                    <div>Date: {{ \Carbon\Carbon::parse($treatment->created_at)->format('M d, Y') }}</div>
                    <div class="treatment-id">Treatment #{{ $treatment->id }}</div>
                </div>
            </div>
        </div>

        <!-- Patient & Treatment Info -->
        <div class="grid">
            <div class="grid-col">
                <div class="grid-title">Patient Information</div>
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $treatment->patient->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $treatment->patient->email }}</span>
                </div>
                @if($treatment->patient->phone)
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $treatment->patient->phone }}</span>
                </div>
                @endif
            </div>
            
            <div class="grid-col">
                <div class="grid-title">Treatment Summary</div>
                <div class="detail-item">
                    <span class="detail-label">Treatment ID:</span>
                    <span class="detail-value">#{{ $treatment->id }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Procedure:</span>
                    <span class="detail-value">{{ $treatment->procedure }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Treatment Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($treatment->created_at)->format('M d, Y') }}</span>
                </div>
                @if($treatment->appointment)
                <div class="detail-item">
                    <span class="detail-label">Appointment ID:</span>
                    <span class="detail-value">#{{ $treatment->appointment->id }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Procedures -->
        <div class="section">
            <div class="section-title">Procedures</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60%">Procedure</th>
                            <th style="width: 40%; text-align: right;">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($treatment->procedures && $treatment->procedures->count() > 0)
                            @foreach($treatment->procedures as $procedure)
                                <tr>
                                    <td>{{ $procedure->name }}</td>
                                    <td style="text-align: right;" class="amount-highlight">UGX {{ number_format($procedure->cost, 0) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>{{ $treatment->procedure }}</td>
                                <td style="text-align: right;" class="amount-highlight">UGX {{ number_format($treatment->cost, 0) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prescriptions -->
        @if($treatment->prescriptions && $treatment->prescriptions->count() > 0)
        <div class="section">
            <div class="section-title">Prescriptions</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50%">Medication</th>
                            <th style="width: 25%">Dosage</th>
                            <th style="width: 25%; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($treatment->prescriptions as $prescription)
                            <tr>
                                <td>{{ $prescription->medicine->medicine_name ?? $prescription->medication ?? 'N/A' }}</td>
                                <td>{{ $prescription->dosage ?? 'N/A' }}</td>
                                <td style="text-align: right;" class="amount-highlight">
                                    @if($prescription->prescription_amount > 0)
                                        UGX {{ number_format($prescription->prescription_amount, 0) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($treatment->prescriptions->where('prescription_instructions')->count() > 0)
                <div class="section-title" style="margin-top: 15px;">Instructions</div>
                @foreach($treatment->prescriptions->where('prescription_instructions') as $prescription)
                    @if($prescription->prescription_instructions)
                        <div class="notes-box" style="margin-bottom: 8px;">
                            <strong>{{ $prescription->medicine->medicine_name ?? $prescription->medication ?? 'N/A' }}:</strong><br>
                            {{ $prescription->prescription_instructions }}
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
        @endif

        <!-- Notes -->
        @if($treatment->notes)
        <div class="section">
            <div class="section-title">Treatment Notes</div>
            <div class="notes-box">
                {!! nl2br(e($treatment->notes)) !!}
            </div>
        </div>
        @endif

        <!-- Attachment Info -->
        @if($hasFile)
        <div class="section">
            <div class="section-title">Attachment Information</div>
            <div class="attachment-box">
                <strong>File:</strong> {{ basename($treatment->file_path) }}<br>
                <em>Note: The original attachment file is stored separately. This PDF contains all treatment information.</em>
            </div>
        </div>
        @endif

        <!-- Cost Summary -->
        <div class="section">
            <div class="section-title">Cost Summary</div>
            <div class="totals">
                <div class="total-row">
                    <span class="total-label">Procedure Cost:</span>
                    <span class="total-value amount-highlight">UGX {{ number_format($treatment->cost, 0) }}</span>
                </div>
                @if($treatment->prescriptions && $treatment->prescriptions->sum('prescription_amount') > 0)
                <div class="total-row">
                    <span class="total-label">Prescription Cost:</span>
                    <span class="total-value amount-highlight">UGX {{ number_format($treatment->prescriptions->sum('prescription_amount'), 0) }}</span>
                </div>
                @endif
                <div class="total-row" style="background-color: #f0fdf4; padding: 12px 15px;">
                    <span class="total-label" style="font-weight: 600; color: #065f46;">Total Treatment Cost:</span>
                    <span class="total-value total-amount">UGX {{ number_format($treatment->cost + ($treatment->prescriptions->sum('prescription_amount') ?? 0), 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="stamp">
                <div>Authorized Signature</div>
                <div style="margin-top: 10px; border-top: 1px solid #d1d5db; padding-top: 5px;">
                    Vintech Solutions
                </div>
            </div>
            <p>Generated on {{ now()->format('M d, Y \a\t h:i A') }}</p>
            <p>This document was generated automatically by the Vintech Solutions System.</p>
            <p>For any questions, please contact our office.</p>
            <p style="margin-top: 10px;">
                <strong>Phone:</strong> +256 392911652 | 
                <strong>Email:</strong> alvinpaka@gmail.com | 
                <!-- <strong>Website:</strong> www.victoriadental.com -->
            </p>
        </div>
    </div>
</body>
</html>
