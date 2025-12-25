<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.verify_email.subject') }}</title>
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
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            padding: 15px 40px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }
        .link-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            word-break: break-all;
        }
        .link-container p {
            margin: 5px 0;
            font-size: 12px;
            color: #7f8c8d;
        }
        .link-container a {
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            border-right: 4px solid #ffc107;
        }
        .warning-box p {
            margin: 5px 0;
            color: #856404;
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
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('emails.verify_email.greeting', ['name' => $user->name ?? $user->email]) }}</h1>
        </div>

        <div class="content">
            <p>{{ __('emails.verify_email.intro') }}</p>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button">
                    {{ __('emails.verify_email.button_text') }}
                </a>
            </div>

            <div class="link-container">
                <p>{{ __('emails.verify_email.link_label') }}</p>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
            </div>

            <div class="warning-box">
                <p><strong>{{ __('emails.verify_email.security_warning') }}</strong></p>
                <p>{{ __('emails.verify_email.expiry_notice') }}</p>
            </div>

            <div class="info-box">
                <p><strong>{{ __('emails.verify_email.instructions_title') }}</strong></p>
                <p>{{ __('emails.verify_email.instructions') }}</p>
            </div>

            <p>{{ __('emails.verify_email.ignore_message') }}</p>
        </div>

        <div class="footer">
            <p>{{ __('emails.verify_email.footer') }}</p>
            <p>{{ __('emails.verify_email.copyright', ['year' => date('Y')]) }}</p>
        </div>
    </div>
</body>
</html>

