<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', 'Notification')</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f4; font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #333; }
        .wrapper { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 6px; overflow: hidden; border: 1px solid #e0e0e0; }
        .header { background: #1a56db; color: #ffffff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.3px; }
        .header p  { margin: 4px 0 0; font-size: 13px; opacity: 0.85; }
        .body { padding: 32px; }
        .body h2 { margin-top: 0; font-size: 18px; color: #1a56db; }
        .body p  { line-height: 1.65; }
        table.data { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.data th { background: #f0f4ff; text-align: left; padding: 9px 12px; font-size: 13px; border: 1px solid #dde3f0; }
        table.data td { padding: 9px 12px; font-size: 14px; border: 1px solid #dde3f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #fafbff; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; background: #e9f0ff; color: #1a56db; }
        .btn { display: inline-block; padding: 12px 28px; background: #1a56db; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 16px 0; }
        .footer { background: #f8f9fc; border-top: 1px solid #e0e0e0; padding: 18px 32px; font-size: 12px; color: #888; text-align: center; }
        .footer a { color: #1a56db; text-decoration: none; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        .highlight { background: #f0f4ff; border-left: 4px solid #1a56db; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0; }
        .amount { font-size: 18px; font-weight: bold; color: #1a56db; }
    </style>
</head>
<body>
<div class="wrapper">
    {{-- Header --}}
    <div class="header">
        <h1>{{ setting('store.company_name', config('app.name')) }}</h1>
        <p>@yield('subject')</p>
    </div>

    {{-- Body --}}
    <div class="body">
        @yield('body')
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>
            &copy; {{ date('Y') }} {{ setting('store.company_name', config('app.name')) }}.
            All rights reserved.
        </p>
        <p>
            {{ setting('store.address', '') }}
        </p>
        <p>
            <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
            @if(setting('notifications.mail.from_address'))
                &nbsp;&bull;&nbsp;
                <a href="mailto:{{ setting('notifications.mail.from_address') }}">
                    {{ setting('notifications.mail.from_address') }}
                </a>
            @endif
        </p>
        <p style="color:#bbb; font-size:11px;">This is an automated message — please do not reply directly to this email.</p>
    </div>
</div>
</body>
</html>
