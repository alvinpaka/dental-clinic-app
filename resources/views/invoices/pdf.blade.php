<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-details td.label {
            font-weight: bold;
            width: 30%;
        }
        .items {
            margin-top: 20px;
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
        }
        .items th, .items td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .items th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total {
            text-align: right;
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dental Clinic Invoice</h1>
        <h2>Invoice #{{ $invoice->id }}</h2>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <td class="label">Patient Name:</td>
                <td>{{ $invoice->patient->name }}</td>
            </tr>
            <tr>
                <td class="label">Patient Email:</td>
                <td>{{ $invoice->patient->email }}</td>
            </tr>
            <tr>
                <td class="label">Invoice Date:</td>
                <td>{{ $invoice->created_at->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="label">Due Date:</td>
                <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td>{{ ucfirst($invoice->status) }}</td>
            </tr>
            @if($invoice->notes)
            <tr>
                <td class="label">Notes:</td>
                <td>{{ $invoice->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($invoice->treatment || $invoice->prescription)
    <div class="items">
        <h3>Services & Medications</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @if($invoice->treatment)
                <tr>
                    <td>Treatment</td>
                    <td>{{ $invoice->treatment->procedure }}</td>
                    <td>{{ $invoice->treatment->notes ?? 'No additional notes' }}</td>
                </tr>
                @endif
                @if($invoice->prescription)
                <tr>
                    <td>Prescription</td>
                    <td>{{ $invoice->prescription->medication }}</td>
                    <td>{{ $invoice->prescription->dosage }} - {{ $invoice->prescription->frequency }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <div class="total">
        <p>Total Amount: UGX {{ number_format($invoice->amount) }}</p>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>Thank you for choosing our dental clinic!</p>
    </div>
</body>
</html>