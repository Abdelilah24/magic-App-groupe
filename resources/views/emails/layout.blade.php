<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
  .wrap { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #1e293b; color: #fff; padding: 28px 32px; }
  .header .brand { font-size: 20px; font-weight: 700; color: #f59e0b; }
  .body { padding: 32px; }
  .body h1 { font-size: 22px; color: #1e293b; margin: 0 0 16px; }
  .body p { color: #475569; line-height: 1.7; margin: 0 0 16px; font-size: 15px; }
  .btn { display: inline-block; background: #f59e0b; color: #fff !important; font-weight: 600; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-size: 15px; margin: 8px 0; }
  .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin: 16px 0; }
  .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
  .info-row:last-child { border-bottom: none; }
  .info-row .label { color: #94a3b8; }
  .info-row .value { font-weight: 600; color: #1e293b; }
  .total-row { background: #fef9ee; border-top: 2px solid #f59e0b; }
  .total-row .label { font-weight: 600; color: #92400e; }
  .total-row .value { color: #f59e0b; font-size: 16px; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
  .alert-box { border-radius: 8px; padding: 14px 18px; margin: 16px 0; font-size: 14px; }
  .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
  .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
  .alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <span class="brand">✦ Magic Hotels</span>
    <p style="margin:4px 0 0; font-size:13px; color:#94a3b8;">Portail Réservations Groupes</p>
  </div>
  <div class="body">
    @yield('body')
  </div>
  <div class="footer">
    <p>Magic Hotels — {{ config('app.url') }}</p>
    <p>Cet email a été généré automatiquement, merci de ne pas y répondre directement.</p>
  </div>
</div>
</body>
</html>
