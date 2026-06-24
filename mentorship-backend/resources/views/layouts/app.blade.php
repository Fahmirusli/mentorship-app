<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Uplifts Admin')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Base Variables (Defaults) */
        :root {
            --sidebar-bg: #ffffff;
            --sidebar-text: #4a5568;
            --sidebar-border: #e2e8f0;
            --nav-hover: #edf2f7;
            --nav-active-bg: #edf2f7;
            --nav-active-text: #667eea;
            --scrollbar-thumb: #cbd5e0;
        }

        /* Dark Mode Variables - Matched with Dashboard */
        [data-theme="dark"] {
            --sidebar-bg: #1a1c23;
            --sidebar-text: #a0aec0;
            --sidebar-border: #2d3748;
            --nav-hover: #2d3748;
            --nav-active-bg: rgba(102, 126, 234, 0.1);
            --nav-active-text: #818cf8;
            --scrollbar-thumb: #4a5568;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color, #f7f9fc); /* Fallback or variable from dashboard */
            color: var(--text-primary, #1e293b);
            transition: background 0.3s, color 0.3s;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease, background 0.3s, border-color 0.3s;
            z-index: 50;
        }

        .logo {
            padding: 30px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.25);
        }

        .logo-text h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .logo-text p {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .nav-section {
            padding: 0 16px;
            margin-bottom: 24px;
        }

        .nav-section-title {
            padding: 0 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background-color: var(--nav-hover);
            color: var(--text-primary);
        }

        .nav-link.active {
            background-color: var(--nav-active-bg);
            color: var(--nav-active-text);
            font-weight: 600;
        }

        .nav-link svg {
            width: 20px;
            height: 20px;
            opacity: 0.8;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px; /* Sidebar width */
            padding: 30px;
            min-height: 100vh;
            background: var(--bg-color);
            transition: margin-left 0.3s;
        }

        /* Mobile Sidebar Toggle */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-size: 24px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* Responsive Breakpoint */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
            }
        }
        
        /* Pagination Override for Dark Mode */
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .page-link {
            position: relative;
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .page-link:hover {
            background-color: var(--nav-hover);
        }
        .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #667eea;
            border-color: #667eea;
        }

    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <div class="container">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">🎓</div>
                <div class="logo-text">
                    <h3>Uplifts</h3>
                    <p>Admin Console</p>
                </div>
            </div>
            
            <div class="nav-section">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/></svg>
                    Dashboard
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Users</div>
                <a href="{{ route('admin.users') }}" class="nav-link {{ request()->is('admin/users') ? 'active' : '' }}">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    All Users
                </a>
                <a href="{{ route('admin.mentees') }}" class="nav-link {{ request()->is('admin/mentees') ? 'active' : '' }}">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                    Mentees
                </a>
                <a href="{{ route('admin.mentors') }}" class="nav-link {{ request()->is('admin/mentors') ? 'active' : '' }}">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                    Mentors
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <a href="{{ route('admin.mentorships') }}" class="nav-link {{ request()->routeIs('admin.mentorships') ? 'active' : '' }}">
                    <span class="nav-icon">🎓</span> Mentorships
                </a>
                <a href="{{ route('admin.feedbacks') }}" class="nav-link {{ request()->routeIs('admin.feedbacks') ? 'active' : '' }}">
                    <span class="nav-icon">⭐</span> Feedback
                </a>
                <a href="{{ route('admin.revenue') }}" class="nav-link {{ request()->routeIs('admin.revenue') ? 'active' : '' }}">
                    <span class="nav-icon">💰</span> Revenue
                </a>
                <a href="{{ route('admin.jobs') }}" class="nav-link {{ request()->is('admin/jobs*') ? 'active' : '' }}">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/></svg>
                    Jobs
                </a>
            </div>

            <div class="nav-section" style="margin-top: auto;">
                 <a href="#" class="nav-link {{ request()->is('') ? 'active' : '' }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="color: #f56565;"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
                    <span style="color: #f56565;">Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
        
        <div class="main-content">
            @yield('content')
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
        
        // Initialize Theme from LocalStorage (Early Script)
        (function() {
            const savedTheme = localStorage.getItem('admin_theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
        })();
    </script>
</body>
</html>
