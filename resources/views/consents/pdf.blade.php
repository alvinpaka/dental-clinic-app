<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Consent - {{ $patient->name }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; line-height: 1.55; }
    .header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px; }
    .title { font-size: 20px; font-weight: bold; }
    .muted { color: #666; }
    .section { margin-bottom: 18px; }
    .box { border:1px solid #ddd; padding:16px; border-radius:6px; background:#fafafa; }
    .sig img { max-height: 90px; }
    .badge { display:inline-block; padding:4px 8px; font-size:11px; border-radius:9999px; background:#ebf5ff; color:#1d4ed8; margin-left:8px; }
  </style>
</head>
<body>
  <div class="header">
    <div>
      <div class="title">Consent: {{ $consent->title }}</div>
      <div class="muted">Patient: {{ $patient->name }} · Signed: {{ optional($consent->signed_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
    </div>
    <div class="muted">#{{ $consent->id }}</div>
  </div>

  <div class="section box">
    {!! $content_html ?? nl2br(e($consent->content_snapshot)) !!}
  </div>

  <div class="section box">
    <div><strong>Signed By:</strong> {{ $consent->signed_by_name }}</div>
    <div><strong>Signed At:</strong> {{ optional($consent->signed_at)->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
    @if($signature_path)
      <div class="sig" style="margin-top:8px;">
        <strong>Signature:</strong><br>
        <img src="{{ $signature_path }}" alt="Signature">
      </div>
    @endif
  </div>
</body>
</html>
