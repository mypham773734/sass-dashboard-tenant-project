<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've been invited</title>
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
            padding: 36px 40px;
            text-align: center;
        }
        .header-logo {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .header-tagline {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
            margin-top: 4px;
        }
        .body {
            padding: 40px;
        }
        .title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px;
        }
        .subtitle {
            font-size: 15px;
            color: #64748b;
            margin: 0 0 32px;
            line-height: 1.6;
        }
        .workspace-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 32px;
        }
        .workspace-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .workspace-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        .invite-meta {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }
        .invite-meta strong {
            color: #334155;
        }
        .btn-wrapper {
            text-align: center;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 40px;
            border-radius: 8px;
            letter-spacing: 0.01em;
        }
        .btn:hover {
            background: #4338ca;
        }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 0 0 24px;
        }
        .fallback {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.7;
        }
        .fallback a {
            color: #6366f1;
            word-break: break-all;
        }
        .expiry {
            font-size: 12px;
            color: #f59e0b;
            margin-top: 16px;
            text-align: center;
        }
        .footer {
            padding: 24px 40px;
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
            <div class="header-tagline">Project Management Platform</div>
        </div>

        {{-- Body --}}
        <div class="body">

            <h1 class="title">You've been invited!</h1>
            <p class="subtitle">
                <strong>{{ $invitedBy }}</strong> has invited you to join their workspace on FlowSaaS.
            </p>

            {{-- Workspace card --}}
            <div class="workspace-card">
                <div class="workspace-label">Workspace</div>
                <div class="workspace-name">{{ $tenantName }}</div>
                <div class="invite-meta">
                    Invited to: <strong>{{ $invitedEmail }}</strong>
                </div>
            </div>

            {{-- CTA Button --}}
            <div class="btn-wrapper">
                <a href="{{ $acceptUrl }}" class="btn">
                    Accept Invitation
                </a>
            </div>

            @if($expiresAt)
                <p class="expiry">
                    ⏱ This invitation expires on {{ \Carbon\Carbon::parse($expiresAt)->format('M d, Y \a\t H:i') }} UTC
                </p>
            @endif

            <hr class="divider">

            {{-- Fallback link --}}
            <div class="fallback">
                <p>If the button above doesn't work, copy and paste this link into your browser:</p>
                <p><a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a></p>
                <p style="margin-top: 12px;">
                    If you didn't expect this invitation, you can safely ignore this email.
                </p>
            </div>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                This email was sent by FlowSaaS on behalf of <strong>{{ $tenantName }}</strong>.<br>
                © {{ date('Y') }} FlowSaaS. All rights reserved.
            </p>
        </div>

    </div>
</div>
</body>
</html>
