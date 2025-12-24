<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة - {{ $invoice->number }}</title>
    <style>
        @font-face {
            font-family: 'Tajawal';
            src: url('{{ public_path("fonts/Tajawal-Regular.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Tajawal';
            src: url('{{ public_path("fonts/Tajawal-Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tajawal', 'DejaVu Sans', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #2d3748;
            background: #ffffff;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #667eea;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .company-details {
            color: #718096;
            font-size: 13px;
            line-height: 1.8;
        }

        .invoice-title {
            text-align: left;
        }

        .invoice-title h1 {
            font-size: 36px;
            color: #2d3748;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .invoice-number {
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
        }

        /* Welcome Message */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h2 {
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .welcome-section p {
            font-size: 15px;
            opacity: 0.95;
            line-height: 1.8;
        }

        /* Invoice Info */
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding: 25px;
            background: #f7fafc;
            border-radius: 10px;
        }

        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #667eea;
        }

        .info-box h3 {
            color: #667eea;
            font-size: 14px;
            margin-bottom: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-box p {
            color: #2d3748;
            font-size: 15px;
            margin: 5px 0;
        }

        /* Unit Info */
        .unit-info {
            background: #edf2f7;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-right: 4px solid #48bb78;
        }

        .unit-info h3 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .unit-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .unit-item {
            background: white;
            padding: 12px 18px;
            border-radius: 6px;
            flex: 1;
            min-width: 200px;
        }

        .unit-item strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .unit-item span {
            color: #2d3748;
            font-size: 15px;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 30px;
        }

        .items-section h3 {
            color: #2d3748;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: bold;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        thead th {
            padding: 15px;
            text-align: right;
            font-weight: bold;
            font-size: 14px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        tbody td {
            padding: 15px;
            color: #2d3748;
            font-size: 14px;
        }

        tbody td:last-child {
            font-weight: bold;
            color: #667eea;
        }

        /* Totals */
        .totals-section {
            background: #f7fafc;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .total-row:last-child {
            border-bottom: none;
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 20px;
        }

        .total-label {
            font-size: 15px;
            color: #4a5568;
        }

        .total-row:last-child .total-label {
            font-size: 20px;
            font-weight: bold;
            color: #2d3748;
        }

        .total-value {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
        }

        .total-row:last-child .total-value {
            font-size: 24px;
            color: #667eea;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 13px;
        }

        .footer-message {
            background: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #4a5568;
            line-height: 1.8;
        }

        .footer-note {
            margin-top: 15px;
            font-size: 12px;
            color: #a0aec0;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
        }

        .status-paid {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-pending {
            background: #feebc8;
            color: #7c2d12;
        }

        .status-overdue {
            background: #fed7d7;
            color: #742a2a;
        }

        .status-sent {
            background: #bee3f8;
            color: #2c5282;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-details">
                    @if($ownership->registration)
                        <div>السجل التجاري: {{ $ownership->registration }}</div>
                    @endif
                    @if($ownership->tax_id)
                        <div>الرقم الضريبي: {{ $ownership->tax_id }}</div>
                    @endif
                    @if($ownership->phone)
                        <div>الهاتف: {{ $ownership->phone }}</div>
                    @endif
                    @if($ownership->email)
                        <div>البريد الإلكتروني: {{ $ownership->email }}</div>
                    @endif
                    @if($ownership->street || $ownership->city)
                        <div>
                            @if($ownership->street) {{ $ownership->street }}، @endif
                            @if($ownership->city) {{ $ownership->city }} @endif
                        </div>
                    @endif
                </div>
            </div>
            <div class="invoice-title">
                <h1>فاتورة</h1>
                <div class="invoice-number">رقم الفاتورة: {{ $invoice->number }}</div>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="welcome-section">
            <h2>مرحباً بك عزيزي المستأجر</h2>
            <p>نشكرك على ثقتك بنا ونتمنى أن تكون تجربتك معنا ممتعة ومريحة</p>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="info-box">
                <h3>معلومات الفاتورة</h3>
                <p><strong>رقم الفاتورة:</strong> {{ $invoice->number }}</p>
                <p><strong>تاريخ الإصدار:</strong> {{ $invoice->generated_at ? $invoice->generated_at->format('Y-m-d') : date('Y-m-d') }}</p>
                <p><strong>تاريخ الاستحقاق:</strong> {{ $invoice->due->format('Y-m-d') }}</p>
                <p><strong>الحالة:</strong> 
                    <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->label() }}
                    </span>
                </p>
            </div>
            <div class="info-box">
                <h3>معلومات الفترة</h3>
                <p><strong>من:</strong> {{ $invoice->period_start->format('Y-m-d') }}</p>
                <p><strong>إلى:</strong> {{ $invoice->period_end->format('Y-m-d') }}</p>
                @if($contract)
                    <p><strong>العقد:</strong> {{ $contract->number }}</p>
                @endif
            </div>
        </div>

        <!-- Unit Information -->
        @if(count($units) > 0)
        <div class="unit-info">
            <h3>معلومات الوحدة</h3>
            <div class="unit-details">
                @foreach($units as $unit)
                <div class="unit-item">
                    <strong>رقم الوحدة:</strong>
                    <span>{{ $unit['number'] }}</span>
                </div>
                @if($unit['name'])
                <div class="unit-item">
                    <strong>اسم الوحدة:</strong>
                    <span>{{ $unit['name'] }}</span>
                </div>
                @endif
                @if($unit['building'])
                <div class="unit-item">
                    <strong>المبنى:</strong>
                    <span>{{ $unit['building'] }}</span>
                </div>
                @endif
                @if($unit['building_code'])
                <div class="unit-item">
                    <strong>رمز المبنى:</strong>
                    <span>{{ $unit['building_code'] }}</span>
                </div>
                @endif
                @if($unit['floor'])
                <div class="unit-item">
                    <strong>الطابق:</strong>
                    <span>{{ $unit['floor'] }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Items Table -->
        <div class="items-section">
            <h3>تفاصيل الفاتورة</h3>
            <table>
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    @if($items && count($items) > 0)
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }} ر.س</td>
                            <td>{{ number_format($item->total, 2) }} ر.س</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>إيجار الفترة المحددة</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">{{ number_format($invoice->amount, 2) }} ر.س</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span class="total-label">المبلغ الإجمالي:</span>
                <span class="total-value">{{ number_format($invoice->amount, 2) }} ر.س</span>
            </div>
            @if($invoice->tax && $invoice->tax > 0)
            <div class="total-row">
                <span class="total-label">الضريبة ({{ $invoice->tax_rate }}%):</span>
                <span class="total-value">{{ number_format($invoice->tax, 2) }} ر.س</span>
            </div>
            @endif
            <div class="total-row">
                <span class="total-label">المبلغ الإجمالي المستحق:</span>
                <span class="total-value">{{ number_format($invoice->total, 2) }} ر.س</span>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="footer-message">
            <strong>ملاحظات:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-message">
                <p>نشكرك على تعاونك معنا ونتمنى أن تكون تجربتك معنا ممتعة ومريحة</p>
                <p>لأي استفسارات، يرجى التواصل معنا على: {{ $ownership->phone ?? 'غير متوفر' }}</p>
            </div>
            <div class="footer-note">
                تم إنشاء هذه الفاتورة تلقائياً بتاريخ {{ date('Y-m-d H:i') }}
            </div>
        </div>
    </div>
</body>
</html>

