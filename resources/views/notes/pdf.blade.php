<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Clinical Note - {{ $patient->name }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    .header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; }
    .title { font-size: 20px; font-weight: bold; }
    .muted { color: #666; }
    .section { margin-bottom: 16px; }
    .box { border:1px solid #ddd; padding:12px; border-radius:6px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .label { font-weight:600; margin-bottom:4px; }
    .pre { white-space: pre-wrap; }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <div class="title">Clinical Note (SOAP)</div>
      <div class="muted">Patient: {{ $patient->name }} · Created: {{ optional($note->created_at)->format('Y-m-d H:i') }}</div>
    </div>
    <div class="muted">#{{ $note->id }}</div>
  </div>

  <div class="grid">
    <div class="section box">
      <div class="label">Subjective</div>
      <div class="pre">{{ $note->subjective }}</div>
    </div>
    <div class="section box">
      <div class="label">Objective</div>
      <div class="pre">{{ $note->objective }}</div>
    </div>
    <div class="section box">
      <div class="label">Assessment</div>
      <div class="pre">{{ $note->assessment }}</div>
    </div>
    <div class="section box">
      <div class="label">Plan</div>
      <div class="pre">{{ $note->plan }}</div>
    </div>
  </div>

  <div class="section box">
    <div><strong>Status:</strong> {{ strtoupper($note->status) }}</div>
    <div><strong>Signed By:</strong> {{ optional($note->signer)->name ?? '—' }}</div>
    <div><strong>Signed At:</strong> {{ optional($note->signed_at)->format('Y-m-d H:i') ?? '—' }}</div>
  </div>
</body>
</html>
