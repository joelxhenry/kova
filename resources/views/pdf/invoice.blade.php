<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        /* Reset & Base */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "DejaVu Sans", sans-serif; }
        html, body, div, span, p, table, thead, tbody, tr, th, td, h1, h2, h3, h4, h5, h6 {
            font-family: "DejaVu Sans", sans-serif;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #172726;
            background: #fff;
        }

        /* Layout */
        .container { padding: 40px; }
        .row { width: 100%; overflow: hidden; }
        .col-left { float: left; width: 55%; }
        .col-right { float: right; width: 40%; text-align: right; }
        .clearfix::after { content: ""; display: table; clear: both; }

        /* Typography */
        h1 { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: #172726; }
        h2 { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #476664; margin-bottom: 6px; }
        .text-muted { color: #476664; }
        .text-sm { font-size: 11px; }
        .text-right { text-align: right; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }

        /* Accent stripe at top */
        .accent-bar {
            height: 4px;
            background: #F95831;
            border-radius: 2px;
            margin-bottom: 32px;
        }

        /* Header */
        .header { margin-bottom: 36px; }
        .invoice-label { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #476664; }
        .invoice-number { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 700; margin-top: 2px; }

        /* Addresses */
        .addresses { margin-bottom: 32px; }
        .address-block { margin-bottom: 16px; }
        .address-block .name { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 600; color: #172726; }

        /* Meta info */
        .meta { margin-bottom: 8px; }
        .meta-item { margin-bottom: 8px; }
        .meta-label { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #476664; }
        .meta-value { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 12px; margin-top: 1px; }

        /* Line items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table th {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #476664;
            padding: 10px 8px;
            background: #f4f8f7;
            border-bottom: 2px solid #D3E2DE;
            text-align: left;
        }
        .items-table th.num { text-align: right; }
        .items-table td {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            padding: 10px 8px;
            border-bottom: 1px solid #e8eeed;
            font-size: 12px;
            vertical-align: top;
        }
        .items-table td.num {
            text-align: right;
        }
        .items-table td.amount { font-weight: 600; }

        /* Totals */
        .totals { float: right; width: 260px; margin-bottom: 32px; }
        .total-row { display: table; width: 100%; padding: 5px 0; font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 12px; }
        .total-label { display: table-cell; text-align: left; color: #476664; }
        .total-value { display: table-cell; text-align: right; width: 120px; }
        .total-row.grand {
            border-top: 2px solid #D3E2DE;
            padding-top: 8px;
            margin-top: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        .total-row.grand .total-label { color: #172726; }
        .total-row.grand .total-value { color: #172726; }
        .total-row.deduction .total-label { color: #476664; }

        /* Net receivable highlight */
        .total-row.net {
            border-top: 2px solid #D3E2DE;
            padding-top: 8px;
            margin-top: 4px;
            font-size: 14px;
            font-weight: 600;
            color: #F95831;
        }
        .total-row.net .total-label { color: #F95831; }
        .total-row.net .total-value { color: #F95831; }

        /* Footer */
        .footer { clear: both; border-top: 1px solid #D3E2DE; padding-top: 20px; margin-top: 20px; }
        .footer-section { margin-bottom: 12px; }
        .footer-label { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #476664; margin-bottom: 4px; }
        .footer-text { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 11px; color: #476664; white-space: pre-line; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Accent stripe --}}
        <div class="accent-bar"></div>

        {{-- Header --}}
        <div class="header row clearfix">
            <div class="col-left">
                <h1>{{ $businessSettings['business_name'] ?: $user->name }}</h1>
                @if($businessSettings['business_address_line_1'])
                    <div class="text-sm text-muted" style="margin-top: 6px; line-height: 1.6;">
                        {{ $businessSettings['business_address_line_1'] }}<br>
                        @if($businessSettings['business_address_line_2']){{ $businessSettings['business_address_line_2'] }}<br>@endif
                        @php
                            $cityState = array_filter([$businessSettings['business_city'], $businessSettings['business_state_or_parish']]);
                        @endphp
                        @if(count($cityState)){{ implode(', ', $cityState) }}<br>@endif
                        @if($businessSettings['business_postal_code']){{ $businessSettings['business_postal_code'] }}<br>@endif
                        @if($businessSettings['business_country']){{ $businessSettings['business_country'] }}@endif
                    </div>
                @endif
                <div class="text-sm text-muted" style="margin-top: 4px;">
                    @if($businessSettings['business_phone']){{ $businessSettings['business_phone'] }}<br>@endif
                    @if($businessSettings['business_email']){{ $businessSettings['business_email'] }}@endif
                </div>
            </div>
            <div class="col-right">
                <div class="invoice-label">Invoice</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            </div>
        </div>

        {{-- Bill To + Meta --}}
        <div class="addresses row clearfix">
            <div class="col-left">
                <h2>Bill To</h2>
                <div class="address-block">
                    <div class="name">{{ $invoice->client->name }}</div>
                    @if($invoice->client->trn)
                        <div class="text-sm text-muted">TRN: {{ $invoice->client->trn }}</div>
                    @endif
                    @php
                        $clientAddr = array_filter([
                            $invoice->client->address_line_1,
                            $invoice->client->address_line_2,
                            implode(', ', array_filter([$invoice->client->city, $invoice->client->state_or_parish])),
                            $invoice->client->postal_code,
                            $invoice->client->country,
                        ]);
                    @endphp
                    @if(count($clientAddr))
                        <div class="text-sm text-muted" style="margin-top: 4px; line-height: 1.6;">
                            {!! implode('<br>', $clientAddr) !!}
                        </div>
                    @endif
                    <div class="text-sm text-muted" style="margin-top: 4px;">
                        @if($invoice->client->email){{ $invoice->client->email }}<br>@endif
                        @if($invoice->client->phone){{ $invoice->client->phone }}@endif
                    </div>
                </div>
            </div>
            <div class="col-right">
                <div class="meta">
                    <div class="meta-item">
                        <div class="meta-label">Issue Date</div>
                        <div class="meta-value">{{ $invoice->issue_date->format('F j, Y') }}</div>
                    </div>
                    @if($invoice->due_date)
                        <div class="meta-item">
                            <div class="meta-label">Due Date</div>
                            <div class="meta-value">{{ $invoice->due_date->format('F j, Y') }}</div>
                        </div>
                    @endif
                    @if($businessSettings['payment_terms'])
                        <div class="meta-item">
                            <div class="meta-label">Terms</div>
                            <div class="meta-value text-muted">{{ $businessSettings['payment_terms'] }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Unit</th>
                    <th class="num" style="width: 10%;">Qty</th>
                    <th class="num" style="width: 17%;">Unit Price</th>
                    <th class="num" style="width: 18%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-muted">{{ $item->unit ?: '—' }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                        <td class="num">${{ number_format((float)$item->unit_price, 2) }}</td>
                        <td class="num amount">${{ number_format((float)$item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="total-row grand">
                <span class="total-label">Total</span>
                <span class="total-value">${{ number_format((float)$invoice->total, 2) }}</span>
            </div>
        </div>

        {{-- Footer --}}
        @if($invoice->notes || ($businessSettings['payment_instructions'] && !$invoice->hide_payment_instructions))
            <div class="footer">
                @if($invoice->notes)
                    <div class="footer-section">
                        <div class="footer-label">Notes</div>
                        <div class="footer-text">{{ $invoice->notes }}</div>
                    </div>
                @endif
                @if($businessSettings['payment_instructions'] && !$invoice->hide_payment_instructions)
                    <div class="footer-section">
                        <div class="footer-label">Payment Instructions</div>
                        <div class="footer-text">{{ $businessSettings['payment_instructions'] }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
