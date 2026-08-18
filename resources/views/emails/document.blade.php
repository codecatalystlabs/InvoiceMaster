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
        .highlight { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $company->name }}</h1>
    </div>
    <div class="content">
        @if($docNumber)
            <h2>{{ $docLabel }}: {{ $docNumber }}</h2>
        @endif
        <p>{!! nl2br(e($intro)) !!}</p>
        @if($amountLabel)
            <div class="highlight"><p><strong>Amount: {{ $amountLabel }}</strong></p></div>
        @endif
        @if($pdfPath)
            <p>The document is attached as a PDF.</p>
        @endif
        <p>Best regards,<br>{{ $company->name }}</p>
    </div>
    <div class="footer">
        <p>{{ $company->email }} @if($company->phone)| {{ $company->phone }}@endif</p>
        @if($company->address)<p>{{ $company->address }}</p>@endif
    </div>
</div>
</body>
</html>
