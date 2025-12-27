<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Clinical Note - {{ $patient->name }}</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    :root {
      --primary: #1e40af;
      --primary-light: #3b82f6;
      --secondary: #6b7280;
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
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--gray-200);
    }
    
    .logo h1 {
      color: var(--primary);
      font-size: 18px;
      font-weight: 700;
      margin: 0 0 3px 0;
      letter-spacing: -0.5px;
      line-height: 1.2;
    }
    
    .muted { 
      color: var(--secondary);
      font-size: 11px;
    }
    
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
    
    .box { 
      background: #f8fafc;
      border: 1px solid var(--gray-200);
      border-radius: 6px;
      padding: 12px;
      margin-bottom: 10px;
    }
    
    .grid { 
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }
    
    .label { 
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 5px;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    
    .pre { 
      white-space: pre-wrap;
      font-size: 11px;
      line-height: 1.5;
    }
    
    .detail-item {
      margin-bottom: 5px;
      display: flex;
      font-size: 11px;
    }
    
    .detail-label {
      font-weight: 500;
      color: var(--gray-700);
      min-width: 100px;
    }
    
    .detail-value {
      font-weight: 400;
      color: var(--dark);
      flex: 1;
    }
    
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
        width: 100%;
        max-width: 100%;
        margin: 0;
      }
    }
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="logo">
        <h1>Vintech Solutions</h1>
        <p class="muted">You Smile, We Smile</p>
      </div>
      <div class="invoice-info">
        <h2>CLINICAL NOTE</h2>
        <div class="invoice-meta">
          <div>Date: {{ optional($note->created_at)->format('M d, Y') }}</div>
          <div>Note #: {{ $note->id }}</div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Patient Information</div>
      <div class="grid">
        <div class="box">
          <div class="detail-item">
            <span class="detail-label">Patient Name:</span>
            <span class="detail-value">{{ $patient->name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Age:</span>
            <span class="detail-value">
              @php
                $age = 'N/A';
                if ($patient->dob) {
                    $age = abs(intval(now()->diffInYears($patient->dob)));
                }
              @endphp
              {{ $age }} years
            </span>
          </div>
        </div>
        <div class="box">
          <div class="detail-item">
            <span class="detail-label">Note Date:</span>
            <span class="detail-value">{{ optional($note->created_at)->format('M d, Y H:i') }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="status-{{ strtolower($note->status) }}">
              {{ strtoupper($note->status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Clinical Note Details</div>
      <div class="grid">
        <div class="box">
          <div class="label">Subjective</div>
          <div class="pre">{{ $note->subjective ?: 'N/A' }}</div>
        </div>
        <div class="box">
          <div class="label">Objective</div>
          <div class="pre">{{ $note->objective ?: 'N/A' }}</div>
        </div>
        <div class="box">
          <div class="label">Assessment</div>
          <div class="pre">{{ $note->assessment ?: 'N/A' }}</div>
        </div>
        <div class="box">
          <div class="label">Plan</div>
          <div class="pre">{{ $note->plan ?: 'N/A' }}</div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Signature</div>
      <div class="box">
        <div class="grid">
          <div>
            <div class="detail-item">
              <span class="detail-label">Signed By:</span>
              <span class="detail-value">{{ optional($note->signer)->name ?? 'Not Signed' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Signed At:</span>
              <span class="detail-value">{{ optional($note->signed_at)->format('M d, Y H:i') ?? '—' }}</span>
            </div>
          </div>
          @if($note->signature_path)
          <div style="text-align: right;">
            <div style="margin-bottom: 5px;">
              <strong>Signature:</strong>
            </div>
            <img src="{{ $note->signature_path }}" alt="Signature" style="max-height: 50px; border: 1px solid #eee; padding: 5px; background: white;">
          </div>
          @endif
        </div>
      </div>
    </div>

    <div class="footer">
      <p>Generated on {{ now()->format('M d, Y H:i') }} | Vintech Solutions</p>
    </div>
  </div>
</body>
</html>
