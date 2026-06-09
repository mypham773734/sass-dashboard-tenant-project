<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Audit Digest — {{ $tenantName }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .wrapper {
            max-width: 600px;
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
            background: linear-gradient(135deg, #0f172a, #1e3a5f);
            padding: 28px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {}
        .header-logo {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
        }
        .header-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-top: 2px;
        }
        .header-badge {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.12);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Stats bar */
        .stats-bar {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
        }
        .stat-item {
            flex: 1;
            padding: 20px 24px;
            text-align: center;
            border-right: 1px solid #e2e8f0;
        }
        .stat-item:last-child { border-right: none; }
        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }
        .stat-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 4px;
        }

        /* Body */
        .body { padding: 28px 40px; }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            margin: 0 0 12px;
        }

        /* Summary table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .summary-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-table th:last-child { text-align: right; }
        .summary-table td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .summary-table td:last-child { text-align: right; }
        .summary-table tr:last-child td { border-bottom: none; }
        .category-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }
        .count-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 20px;
        }

        /* Log table */
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            font-size: 12px;
        }
        .log-table th {
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .log-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #f8fafc;
            vertical-align: top;
            color: #334155;
        }
        .log-table tr:last-child td { border-bottom: none; }
        .log-table tr:hover td { background: #f8fafc; }
        .action-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
            font-family: 'SFMono-Regular', Consolas, monospace;
        }
        .entity-chip {
            font-size: 11px;
            color: #64748b;
        }
        .time-cell {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
        }
        .truncate {
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 32px;
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 28px;
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
            margin-bottom: 0;
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
            <div class="header-left">
                <div class="header-logo">FlowSaaS</div>
                <div class="header-sub">Daily Audit Digest · {{ $period }}</div>
            </div>
            <div class="header-badge">{{ $tenantName }}</div>
        </div>

        {{-- Stats bar --}}
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number">{{ $totalEvents }}</div>
                <div class="stat-label">Total Events</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ count($summary) }}</div>
                <div class="stat-label">Categories</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ \Carbon\Carbon::parse($since)->format('H:i') }}</div>
                <div class="stat-label">From</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">{{ \Carbon\Carbon::parse($until)->format('H:i') }}</div>
                <div class="stat-label">To</div>
            </div>
        </div>

        {{-- Body --}}
        <div class="body">

            {{-- Summary by category --}}
            @if(count($summary) > 0)
                <div class="section-title">Activity by Category</div>

                @php
                    $dotColors = [
                        'project'  => '#6366f1',
                        'task'     => '#0ea5e9',
                        'user'     => '#10b981',
                        'tenant'   => '#f59e0b',
                        'auth'     => '#ef4444',
                        'mail'     => '#8b5cf6',
                        'profile'  => '#14b8a6',
                    ];
                @endphp

                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Events</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $category => $count)
                            <tr>
                                <td>
                                    <span class="category-dot"
                                          style="background: {{ $dotColors[$category] ?? '#94a3b8' }}">
                                    </span>
                                    {{ ucfirst($category) }}
                                </td>
                                <td>
                                    <span class="count-badge">{{ $count }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Event log --}}
            <div class="section-title">Recent Events</div>

            @if(count($logs) === 0)
                <div class="empty-state">
                    No activity recorded during this period.
                </div>
            @else
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <span class="action-tag">{{ $log->action }}</span>
                                </td>
                                <td class="entity-chip truncate">
                                    {{ $log->entityType }}
                                    @if($log->entityId)
                                        #{{ $log->entityId }}
                                    @endif
                                </td>
                                <td class="time-cell">
                                    {{ \Carbon\Carbon::parse($log->createdAt)->format('H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($totalEvents > 50)
                    <p style="font-size:12px; color:#94a3b8; text-align:center; margin-bottom:24px;">
                        Showing 50 of {{ $totalEvents }} events.
                    </p>
                @endif
            @endif

            <hr class="divider">

            <p class="note">
                This digest covers activity in <strong>{{ $tenantName }}</strong>
                from {{ \Carbon\Carbon::parse($since)->format('M d, Y H:i') }}
                to {{ \Carbon\Carbon::parse($until)->format('M d, Y H:i') }} UTC.<br>
                Sent to workspace Owners and Admins only.
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
