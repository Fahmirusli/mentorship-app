@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<!-- Dark Theme Styles -->
<style>
    :root {
        --bg-color: #f7f9fc;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
    }
    
    [data-theme="dark"] {
        --bg-color: #0f111a;
        --card-bg: #1a1c23;
        --text-primary: #f8fafc;
        --text-secondary: #94a3b8;
        --border-color: #2d3748;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        transition: background-color 0.3s, color 0.3s;
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    /* Top Bar */
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
    }

    .theme-toggle {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 10px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    
    .theme-toggle:hover {
        background: var(--border-color);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }

    /* Main Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    @media (max-width: 1024px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    .content-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    
    th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 600;
    }
    
    td {
        color: var(--text-primary);
        font-size: 14px;
    }

    /* Modal */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .modal-content {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        width: 100%;
        max-width: 450px;
        padding: 30px;
        transform: scale(0.9);
        transition: transform 0.3s;
    }

    .modal-open {
        display: flex;
        opacity: 1;
    }
    
    .modal-open .modal-content {
        transform: scale(1);
    }

    /* Inputs */
    .form-input {
        width: 100%;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 12px;
        border-radius: 10px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-input:focus {
        border-color: #667eea;
        outline: none;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: var(--bg-color);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }
    
    /* Notification Bell */
    .notification-dropdown {
        position: relative;
    }
    
    .bell-icon {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 20px;
        cursor: pointer;
        position: relative;
        padding: 10px;
        border-radius: 8px;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
    }
    
    .bell-icon:hover {
        background: var(--border-color);
    }
    
    .notification-dot {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 10px;
        height: 10px;
        background-color: #e53e3e;
        border-radius: 50%;
        border: 2px solid var(--card-bg);
    }
    
    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        width: 300px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 50;
        margin-top: 10px;
        padding: 10px;
    }
    
    .dropdown-menu.show {
        display: block;
    }
    
    .dropdown-menu h4 {
        margin: 0 0 10px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 14px;
    }
    
    .notif-item {
        padding: 10px;
        border-radius: 6px;
        background: rgba(102, 126, 234, 0.05);
        border-left: 4px solid #667eea;
    }
    
    .notif-item strong {
        display: block;
        color: var(--text-primary);
        font-size: 14px;
        margin-bottom: 4px;
    }
    
    .notif-item p {
        margin: 0;
        font-size: 13px;
        color: var(--text-secondary);
    }
    
    .notif-item small {
        display: block;
        margin-top: 6px;
        font-size: 11px;
        color: var(--text-secondary);
    }
</style>

<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Dashboard Overview</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="notification-dropdown">
                @php 
                    $scrapeSchedule = \App\Models\JobScrapeSchedule::first(); 
                    $hasNotification = $scrapeSchedule && $scrapeSchedule->last_run_status && $scrapeSchedule->last_run_at;
                @endphp
                
                <button class="bell-icon" onclick="document.getElementById('notif-menu').classList.toggle('show')">
                    🔔
                    @if($hasNotification)
                        <span class="notification-dot"></span>
                    @endif
                </button>
                
                <div id="notif-menu" class="dropdown-menu">
                    <h4>Notifications</h4>
                    @if($hasNotification)
                        <div class="notif-item">
                            <strong>Automated Scraping Finished</strong>
                            <p>Status: {{ $scrapeSchedule->last_run_status }}</p>
                            <small>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $scrapeSchedule->last_run_at, $scrapeSchedule->timezone ?: 'Asia/Kuala_Lumpur')->diffForHumans() }}</small>
                        </div>
                    @else
                        <div class="notif-item" style="border:none; background:transparent;">
                            <p>No new notifications.</p>
                        </div>
                    @endif
                </div>
            </div>

            <button class="theme-toggle" onclick="toggleTheme()" id="themeIcon">
                🌙 Dark Mode
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">👥</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Total Users</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 8px 0;">{{ number_format($stats['total_users']) }}</div>
            <div style="color: #48bb78; font-size: 13px; font-weight: 600;">↑ 12% this month</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1); color: #ed8936;">💼</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Active Jobs</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 8px 0;">{{ number_format($stats['total_jobs']) }}</div>
            <div style="color: var(--text-secondary); font-size: 13px;">Across all platforms</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(159, 122, 234, 0.1); color: #9f7aea;">⚡</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Mentorships</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 8px 0;">{{ $stats['active_mentorships'] }}</div>
            <div style="color: #48bb78; font-size: 13px; font-weight: 600;">Active Sessions</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">💰</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Revenue</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin: 8px 0;">RM{{ number_format($stats['total_revenue'] ?? 0) }}</div>
            <div style="color: var(--text-secondary); font-size: 13px;">Gross Income</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Main Chart -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">User Growth</h3>
                    <select class="form-input" style="width: auto; padding: 6px 12px;">
                        <option>Last 6 Months</option>
                        <option>Last Year</option>
                    </select>
                </div>
                <div style="height: 300px;">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            
            <!-- Revenue Chart -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Revenue Growth (RM)</h3>
                    <select class="form-input" style="width: auto; padding: 6px 12px;">
                        <option>Last 6 Months</option>
                        <option>Last Year</option>
                    </select>
                </div>
                <div style="height: 300px;">
                    <canvas id="revenueGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Job Sources & Scraper -->
        <div class="content-card" style="height: 100%; display: flex; flex-direction: column;">
            <div class="card-header">
                <h3 class="card-title">Job Sources</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; flex: 1; justify-content: space-between; gap: 20px;">
                <div>
                     @foreach($jobStats as $stat)
                        @php 
                            $max = $jobStats->max('count') ?: 1;
                            $percent = ($stat->count / $max) * 100;
                        @endphp
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 500;">{{ $stat->source }}</span>
                                <span style="color: var(--text-secondary);">{{ number_format($stat->count) }}</span>
                            </div>
                            <div style="height: 6px; background: var(--bg-color); border-radius: 4px; overflow: hidden;">
                                <div style="width: {{ $percent }}%; height: 100%; background: #667eea; border-radius: 4px;"></div>
                            </div>
                        </div>
                    @endforeach
                     @if($jobStats->isEmpty())
                        <p style="color: var(--text-secondary); text-align: center;">No job data available.</p>
                    @endif
                </div>

                <!-- MOVED SCAPER BUTTON HERE -->
                <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; border: 1px dashed #667eea; margin-top: auto;">
                    <h4 style="margin: 0 0 10px 0; font-size: 16px;">Automated Scraping</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 15px;">Run a new scraper job to fetch latest listings.</p>
                    <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="openScrapeModal()">
                        + Start New Scraper Job
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Recent Mentorship Activity</h3>
            <a href="{{ route('admin.mentorships') }}" style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 14px;">View All</a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mentor</th>
                        <th>Mentee</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMentorships as $mentorship)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                    {{ substr($mentorship->mentor->name ?? 'U', 0, 1) }}
                                </div>
                                <span style="font-weight: 500;">{{ $mentorship->mentor->name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td>{{ $mentorship->mentee->name ?? 'Unknown' }}</td>
                        <td>
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: {{ $mentorship->status == 'confirmed' ? 'rgba(72, 187, 120, 0.1)' : 'rgba(237, 137, 54, 0.1)' }}; color: {{ $mentorship->status == 'confirmed' ? '#48bb78' : '#ed8936' }}">
                                {{ ucfirst($mentorship->status) }}
                            </span>
                        </td>
                        <td style="color: var(--text-secondary);">{{ $mentorship->created_at->diffForHumans() }}</td>
                        <td>
                            <button style="background: none; border: none; cursor: pointer; color: var(--text-secondary);">⋮</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-secondary);">No recent activity found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scraper Modal -->
<div id="scrapeModal" class="modal-backdrop">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700;">Run Web Scraper</h3>
            <button onclick="closeScrapeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-secondary);">×</button>
        </div>
        
        <form action="{{ route('admin.jobs.scrape') }}" method="POST">
            @csrf
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Target Keyword</label>
                <input type="text" name="keyword" class="form-input" placeholder="e.g. 'Data Analyst' or 'React Developer'" required autofocus>
                <p style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    This will search LinkedIn, Hiredly, and Indeed for the specified role.
                </p>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeScrapeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Start Scraping Job</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Theme Toggling
    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('admin_theme', newTheme);
        updateThemeIcon(newTheme);
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        icon.innerHTML = theme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode';
    }

    // Initialize Theme
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('admin_theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    });

    // Modal Functions
    function openScrapeModal() {
        const modal = document.getElementById('scrapeModal');
        modal.classList.add('modal-open');
    }
    
    function closeScrapeModal() {
        const modal = document.getElementById('scrapeModal');
        modal.classList.remove('modal-open');
    }

    // Close on click outside
    document.getElementById('scrapeModal').addEventListener('click', function(e) {
        if (e.target === this) closeScrapeModal();
    });

    // Chart
    const ctx = document.getElementById('userGrowthChart').getContext('2d');
    
    // Detect theme for chart colors
    const isDark = localStorage.getItem('admin_theme') === 'dark';
    const gridColor = isDark ? '#2d3748' : '#f1f5f9';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [{
                label: 'User Signups',
                data: @json($userCounts),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#667eea',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, borderDash: [4, 4] },
                    ticks: { color: textColor, padding: 10 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, padding: 10 }
                }
            }
        }
    });

    const ctxRev = document.getElementById('revenueGrowthChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [{
                label: 'Monthly Sales (RM)',
                data: @json($salesData),
                borderColor: '#48bb78',
                backgroundColor: 'rgba(72, 187, 120, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#48bb78',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, borderDash: [4, 4] },
                    ticks: { color: textColor, padding: 10 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, padding: 10 }
                }
            }
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('notif-menu');
        const bell = document.querySelector('.bell-icon');
        if (dropdown && dropdown.classList.contains('show')) {
            if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        }
    });
</script>
@endsection
