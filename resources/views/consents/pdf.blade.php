<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Consent - {{ $patient->name }}</title>
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
    
    .consent-content {
      line-height: 1.6;
      font-size: 11px;
    }
    
    .sig {
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid var(--gray-200);
    }
    
    .sig img { 
      max-height: 50px; 
      border: 1px solid #eee;
      padding: 5px;
      background: white;
      margin-top: 5px;
    }
    
    .footer {
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid var(--gray-200);
      font-size: 9px;
      color: var(--secondary);
      text-align: center;
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
        <h1>Victoria Dental Lounge</h1>
        <p class="muted">Dental Care Excellence</p>
      </div>
      <div class="invoice-info">
        <h2>PATIENT CONSENT</h2>
        <div class="invoice-meta">
          <div>Date: {{ optional($consent->signed_at)->format('M d, Y') ?? 'Not Signed' }}</div>
          <div>Consent #: {{ $consent->id }}</div>
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
            <span class="detail-label">Consent Type:</span>
            <span class="detail-value">{{ $consent->title }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span class="status-{{ $consent->signed_at ? 'active' : 'inactive' }}">
              {{ $consent->signed_at ? 'SIGNED' : 'NOT SIGNED' }}
            </span>
          </div>
        </div>
      </div>
    </div>

  <div class="section">
    <div class="section-title">Consent Details</div>
    <div class="box">
      <div class="consent-content">
        {!! $content_html ?? nl2br(e($consent->content_snapshot)) !!}
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
            <span class="detail-value">{{ $consent->signed_by_name ?? 'Not Signed' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Signed At:</span>
            <span class="detail-value">
              {{ optional($consent->signed_at)->timezone(config('app.timezone'))->format('M d, Y H:i') ?? '—' }}
            </span>
          </div>
        </div>
        @if($signature_path)
        <div style="text-align: right;">
          <div style="margin-bottom: 5px;">
            <strong>Signature:</strong>
          </div>
          <img src="{{ $signature_path }}" alt="Signature" class="sig">
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="footer">
    <p>Generated on {{ now()->format('M d, Y H:i') }} | Victoria Dental Lounge</p>
  </div>
</body>
</html>
