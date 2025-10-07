<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->id }}</title>
</head>
<body>
    <h1>Invoice for {{ $invoice->patient->name }}</h1>
    <p>Amount: UGX{{ $invoice->amount }}</p>
    <p>Due: {{ $invoice->due_date }}</p>
    <!-- More details -->
</body>
</html>