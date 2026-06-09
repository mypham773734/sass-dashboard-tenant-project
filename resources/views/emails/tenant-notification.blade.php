<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $eventTitle }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            padding: 0 16px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 28px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-logo {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
        }
        .header-badge {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.15);
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.04em;
        }
        .event-banner {
            padding: 20px 40px;
            border-bottom: 1px solid #e2e8f0;
        }
        .event-type-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .event-type-security   { background: #fef2f2; color: #dc2626; }
        .event-type-member     { background: #eff6ff; color: #2563eb; }
        .event-type-role       { background: #f5f3ff; color: #7c3aed; }
        .event-type-settings   { background: #f0fdf4; color: #16a34a; }
        .event-type-default    { background: #f8fafc; color: #64748b; }
        .event-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .body {
            padding: 32px 40px;
        }
        .meta-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        .meta-item {
            flex: 1;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .meta-value {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
        }
        .description-box {
            background: #f8fafc;
            border-left: 3px solid #6366f1;
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .description-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .description-text {
            font-size: 14px;
            color: #334155;
            line-height: 1.6;
            margin: 0;
        }
        .btn-wrapper {
            text-align: center;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 32px;
            border-radius: 8px;
        }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 0 0 20px;
        }
        .note {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.7;
        }
        .footer {
            padding: 20px 40px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
            line-height: 1.7;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <div class="header-logo">FlowSaaS</div>
            <div class="header-badge">{{ $tenantName }}</div>
        </div>

        {{-- Event banner --}}
        <div class="event-banner">
            @php
                $badgeClass = match($eventType) {
                    'security'         => 'event-type-security',
                    'member_added',
                    'member_removed'   => 'event-type-member',
                    'role_changed'     => 'event-type-role',
                    'settings_changed' => 'event-type-settings',
                    default            => 'event-type-default',
                };
                $badgeLabel = match($eventType) {
                    'security'         => 'Security',
                    'member_added'     => 'Member Added',
                    'member_removed'   => 'Member Removed',
                    'role_changed'     => 'Role Changed',
                    'settings_changed' => 'Settings',
                    default            => 'Notification',
                };
            @endphp
            <span class="event-type-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            <h1 class="event-title">{{ $eventTitle }}</h1>
        </div>

        {{-- Body --}}
        <div class="body">

            {{-- Meta: actor + time --}}
            <div class="meta-row">
                <div class="meta-item">
                    <div class="meta-label">Performed by</div>
                    <div class="meta-value">{{ $actorName }}</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Date & Time</div>
                    <div class="meta-value">
                        {{ \Carbon\Carbon::parse($occurredAt)->format('M d, Y · H:i') }} UTC
                    </div>
                </div>
            </div>

            {{-- Description --}}
            <div class="description-box">
                <div class="description-label">Details</div>
                <p class="description-text">{{ $description }}</p>
            </div>

            {{-- CTA (optional) --}}
            @if($actionUrl)
                <div class="btn-wrapper">
                    <a href="{{ $actionUrl }}" class="btn">{{ $actionLabel }}</a>
                </div>
            @endif

            <hr class="divider">

            <p class="note">
                This notification was sent because you are an Owner or Admin of
                <strong>{{ $tenantName }}</strong>.<br>
                If this action was not expected, please review your workspace settings immediately.
            </p>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                FlowSaaS · Workspace: <strong>{{ $tenantName }}</strong><br>
                © {{ date('Y') }} FlowSaaS. All rights reserved.
            </p>
        </div>

    </div>
</div>
</body>
</html>
