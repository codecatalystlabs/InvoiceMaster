<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 10px 20px; background: #0d6efd; color: #fff !important; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $company->name }}</h1>
    </div>
    <div class="content">
        <p>You have been invited to join <strong>{{ $company->name }}</strong> as <strong>{{ $invitation->role }}</strong>.</p>
        <p>Click the button below to create your account:</p>
        <p><a class="button" href="{{ $acceptUrl }}">Accept invitation</a></p>
        <p>If the button does not work, copy this link:<br>{{ $acceptUrl }}</p>
    </div>
    <div class="footer">
        <p>{{ $company->email }}</p>
    </div>
</div>
</body>
</html>
