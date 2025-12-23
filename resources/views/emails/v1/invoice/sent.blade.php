<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.invoice.sent.subject', ['number' => $invoice->number, 'ownership' => $ownership->name ?? 'Property Management']) }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            direction: rtl;
            text-align: right;
        }

        body {
            font-family: Tahoma, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f7fa;
            padding: 20px;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 45px 35px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 26px;
            font-weight: 600;
        }

        .icon {
            font-size: 52px;
            margin-bottom: 18px;
            display: block;
        }

        .email-body {
            padding: 40px 35px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 22px;
        }

        .intro-text {
            font-size: 15px;
            color: #4a5568;
            margin-bottom: 32px;
        }

        .invoice-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 28px;
            margin: 32px 0;
            border: 1px solid #e2e8f0;
            border-right: 4px solid #667eea;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e9ecef;
            gap: 20px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            font-size: 14px;
            color: #495057;
            min-width: 140px;
            flex-shrink: 0;
        }

        .detail-label::after {
            content: ':';
            margin-right: 8px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 500;
            text-align: left;
            direction: ltr;
            flex: 1;
        }

        .total-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .total-label {
            font-size: 17px;
            font-weight: 600;
            min-width: 140px;
            flex-shrink: 0;
        }

        .total-label::after {
            content: ':';
            margin-right: 8px;
        }

        .total-value {
            font-size: 26px;
            font-weight: 700;
            direction: ltr;
            flex: 1;
            text-align: left;
        }

        .status-badge {
            display: inline-block;
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 22px;
            margin: 28px 0;
        }

        .contact-box {
            background: #e7f3ff;
            border-right: 4px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin: 28px 0;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 32px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-text {
            font-size: 14px;
            color: #6c757d;
        }

        .copyright {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 12px;
        }

        @media only screen and (max-width: 600px) {
            .detail-row,
            .total-row {
                flex-direction: column;
                gap: 8px;
                text-align: right;
            }

            .detail-value,
            .total-value {
                text-align: right;
            }
        }
    </style>
</head>

<body>
<div dir="rtl" class="email-wrapper">

    <div dir="rtl" class="email-header">
        <span class="icon">📄</span>
        <h1>{{ __('emails.invoice.sent.subject', ['number' => $invoice->number, 'ownership' => $ownership->name ?? 'Property Management']) }}</h1>
    </div>

    <div dir="rtl" class="email-body">

        <div dir="rtl" class="greeting">
            {{ __('emails.invoice.sent.greeting', ['name' => $tenantUser->name ?? $tenant->name ?? __('emails.tenant_invitation.future_tenant')]) }}
        </div>

        <div dir="rtl" class="intro-text">
            {{ __('emails.invoice.sent.intro') }}
        </div>

        <div dir="rtl" class="invoice-details">

            <div dir="rtl" class="detail-row">
                <span class="detail-label">{{ __('emails.invoice.sent.invoice_number') }}</span>
                <span class="detail-value">{{ $invoice->number }}</span>
            </div>

            @if($contract)
            <div dir="rtl" class="detail-row">
                <span class="detail-label">{{ __('emails.invoice.sent.contract_number') }}</span>
                <span class="detail-value">{{ $contract->number }}</span>
            </div>
            @endif

            <div dir="rtl" class="detail-row">
                <span class="detail-label">{{ __('emails.invoice.sent.period') }}</span>
                <span class="detail-value">{{ $invoice->period_start->format('Y-m-d') }} - {{ $invoice->period_end->format('Y-m-d') }}</span>
            </div>

            <div dir="rtl" class="detail-row">
                <span class="detail-label">{{ __('emails.invoice.sent.due_date') }}</span>
                <span class="detail-value">{{ $invoice->due->format('Y-m-d') }}</span>
            </div>

            <div dir="rtl" class="detail-row">
                <span class="detail-label">{{ __('emails.invoice.sent.amount') }}</span>
                <span class="detail-value">{{ number_format($invoice->amount, 2) }} {{ __('messages.currency') }}</span>
            </div>

            <div dir="rtl" class="total-row">
                <span class="total-label">{{ __('emails.invoice.sent.total') }}</span>
                <span class="total-value">{{ number_format($invoice->total, 2) }} {{ __('messages.currency') }}</span>
            </div>

        </div>

        <div dir="rtl" class="info-box">
            {{ __('emails.invoice.sent.payment_note') }}
        </div>

        <div dir="rtl" class="contact-box">
            {{ __('emails.invoice.sent.contact_us') }}
        </div>

    </div>

    <div dir="rtl" class="email-footer">
        <div dir="rtl" class="footer-text">
            {!! __('emails.invoice.sent.footer', ['ownership' => $ownership->name ?? 'Property Management']) !!}
        </div>
        <div dir="rtl" class="copyright">
            {{ __('emails.invoice.sent.copyright', ['year' => date('Y')]) }}
        </div>
    </div>

</div>
</body>
</html>
