<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.tenant_invitation.subject', ['ownership' => $ownership->name]) }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 14px;
            color: #7f8c8d;
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-right: 4px solid #3498db;
        }
        .info-box p {
            margin: 5px 0;
        }
        .warning {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('emails.tenant_invitation.greeting', ['name' => $invitation->name ?: __('emails.tenant_invitation.future_tenant')]) }}</h1>
        </div>

        <div class="content">
            <p>{{ __('emails.tenant_invitation.intro', ['ownership' => $ownership->name]) }}</p>

            <div class="info-box">
                <p><strong>{{ __('emails.tenant_invitation.ownership') }}:</strong> {{ $ownership->name }}</p>
                @if($invitation->email)
                <p><strong>{{ __('emails.tenant_invitation.invited_email') }}:</strong> {{ $invitation->email }}</p>
                @endif
                @if($invitation->phone)
                <p><strong>{{ __('emails.tenant_invitation.invited_phone') }}:</strong> {{ $invitation->phone }}</p>
                @endif
            </div>

            <p>{{ __('emails.tenant_invitation.instructions') }}</p>

            <div class="button-container">
                <a href="{{ $invitationUrl }}" class="button">{{ __('emails.tenant_invitation.register_button') }}</a>
            </div>

            <p class="warning">{{ __('emails.tenant_invitation.expiry_warning', ['date' => $expiresAt]) }}</p>

            @if($invitation->notes)
            <div class="info-box">
                <p><strong>{{ __('emails.tenant_invitation.notes') }}:</strong></p>
                <p>{{ $invitation->notes }}</p>
            </div>
            @endif

            <p>{{ __('emails.tenant_invitation.ignore_message') }}</p>
        </div>

        <div class="footer">
            <p>{{ __('emails.tenant_invitation.footer', ['ownership' => $ownership->name]) }}</p>
            <p>{{ __('emails.tenant_invitation.copyright', ['year' => date('Y')]) }}</p>
        </div>
    </div>
</body>
</html>

