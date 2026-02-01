@extends('layouts.app')

@section('title', 'Revenue')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Revenue & Transactions</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Overview of all successful payments</p>
        </div>
        <div style="background: rgba(102, 126, 234, 0.1); padding: 15px 25px; border-radius: 12px; border: 1px solid rgba(102, 126, 234, 0.2);">
            <div style="font-size: 13px; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 5px;">Total Revenue</div>
            <div style="font-size: 24px; font-weight: 800; color: #667eea;">RM {{ number_format($totalRevenue, 2) }}</div>
        </div>
    </div>

    <div class="content-card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Mentee</th>
                        <th>Mentor</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revenues as $revenue)
                    <tr>
                        <td>
                            <code style="background: var(--nav-active-bg); padding: 4px 8px; border-radius: 4px; font-size: 12px; color: var(--text-primary);">
                                {{ $revenue->bill_code ?? 'N/A' }}
                            </code>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-primary);">
                                {{ $revenue->user->name ?? 'Unknown' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-primary);">
                                {{ $revenue->appointment->mentor->name ?? 'Unknown' }}
                            </div>
                        </td>
                        <td style="color: var(--text-secondary);">
                            {{ optional($revenue->appointment)->appointment_date ? \Carbon\Carbon::parse($revenue->appointment->appointment_date)->format('M d, Y') : 'TBD' }} 
                            <span style="font-size: 12px; margin-left: 5px;">{{ optional($revenue->appointment)->appointment_time }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #48bb78;">
                                RM {{ number_format($revenue->amount, 2) }}
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">
                                PAID
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            No revenue records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid var(--border-color);">
            {{ $revenues->links() }}
        </div>
    </div>
</div>

<style>
/* Dashboard Styles Reuse */
:root { --bg-color: #f7f9fc; --card-bg: #ffffff; --text-primary: #1e293b; --text-secondary: #64748b; --border-color: #e2e8f0; }
[data-theme="dark"] { --bg-color: #0f111a; --card-bg: #1a1c23; --text-primary: #f8fafc; --text-secondary: #94a3b8; --border-color: #2d3748; }
.dashboard-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.page-title { font-size: 28px; font-weight: 700; color: var(--text-primary); margin: 0; }
.content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
th { color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600; }
td { color: var(--text-primary); font-size: 14px; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
</style>
@endsection
