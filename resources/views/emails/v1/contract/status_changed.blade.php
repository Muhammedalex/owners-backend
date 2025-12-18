<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.contract.status_changed.subject', ['ownership' => $ownership->name, 'contract_number' => $contract->number, 'new_status' => $newStatusLabel]) }}</title>
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
        .status-change {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-right: 4px solid #ffc107;
        }
        .status-change p {
            margin: 5px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-cancelled, .status-terminated {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-expired {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('emails.contract.status_changed.greeting', ['name' => $tenant?->user?->name ?? __('emails.tenant_invitation.future_tenant')]) }}</h1>
        </div>

        <div class="content">
            <p>{{ __('emails.contract.status_changed.intro') }}</p>

            <div class="status-change">
                <p><strong>{{ __('emails.contract.status_changed.contract_number') }}:</strong> {{ $contract->number }}</p>
                <p><strong>{{ __('emails.contract.status_changed.previous_status') }}:</strong> 
                    <span class="status-badge status-{{ $previousStatus }}">{{ $previousStatusLabel }}</span>
                </p>
                <p><strong>{{ __('emails.contract.status_changed.new_status') }}:</strong> 
                    <span class="status-badge status-{{ $newStatus }}">{{ $newStatusLabel }}</span>
                </p>
            </div>

            <div class="info-box">
                <p><strong>{{ __('emails.contract.status_changed.ownership') }}:</strong> {{ $ownership->name }}</p>
                @if($tenant && $tenant->user)
                <p><strong>{{ __('emails.contract.status_changed.tenant') }}:</strong> {{ $tenant->user->name }}</p>
                @endif
                <p><strong>{{ __('emails.contract.status_changed.contract_number') }}:</strong> {{ $contract->number }}</p>
                @if($contract->start)
                <p><strong>{{ __('contracts.start') }}:</strong> {{ $contract->start->format('Y-m-d') }}</p>
                @endif
                @if($contract->end)
                <p><strong>{{ __('contracts.end') }}:</strong> {{ $contract->end->format('Y-m-d') }}</p>
                @endif
            </div>

            <div class="button-container">
                <a href="{{ $contractUrl }}" class="button">{{ __('emails.contract.status_changed.view_contract') }}</a>
            </div>
        </div>

        <div class="footer">
            <p>{{ __('emails.contract.status_changed.footer', ['ownership' => $ownership->name]) }}</p>
            <p>{{ __('emails.contract.status_changed.copyright', ['year' => date('Y')]) }}</p>
        </div>
    </div>
</body>
</html>

