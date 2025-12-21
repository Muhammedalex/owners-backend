<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.forgot_password_otp.subject') }}</title>
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
        .otp-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .otp-code {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 10px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }
        .otp-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
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
            <h1>{{ __('emails.forgot_password_otp.greeting') }}</h1>
        </div>

        <div class="content">
            <p>{{ __('emails.forgot_password_otp.intro') }}</p>

            <div class="otp-box">
                <div class="otp-label">{{ __('emails.forgot_password_otp.otp_label') }}</div>
                <div class="otp-code">{{ $otp }}</div>
                <div style="font-size: 12px; opacity: 0.9; margin-top: 10px;">
                    {{ __('emails.forgot_password_otp.valid_for') }}
                </div>
            </div>

            <div class="warning-box">
                <p><strong>{{ __('emails.forgot_password_otp.security_warning') }}</strong></p>
                <p>{{ __('emails.forgot_password_otp.do_not_share') }}</p>
            </div>

            <div class="info-box">
                <p><strong>{{ __('emails.forgot_password_otp.instructions_title') }}</strong></p>
                <p>{{ __('emails.forgot_password_otp.instructions') }}</p>
            </div>

            <p>{{ __('emails.forgot_password_otp.ignore_message') }}</p>
        </div>

        <div class="footer">
            <p>{{ __('emails.forgot_password_otp.footer') }}</p>
            <p>{{ __('emails.forgot_password_otp.copyright', ['year' => date('Y')]) }}</p>
        </div>
    </div>
</body>
</html>

