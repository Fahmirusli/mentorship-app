@extends('layouts.app')

@section('title', 'Jobs Management')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Jobs Management</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Manage job listings and scraping sources</p>
        </div>
        <div class="top-actions">
            <button class="btn btn-primary" onclick="openCreateModal()">
                + Add Job
            </button>
            <button class="btn btn-secondary" onclick="openScrapeModal()">
                <span>🕷️</span> + Start New Scraper Job
            </button>
            <button class="btn btn-secondary" onclick="openScheduleModal()">
                <span>⏰</span> Schedule Scraper
            </button>
        </div>
    </div>

    <div class="content-card" style="margin-bottom: 24px;">
        <div class="card-header" style="justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">Scrape Schedule</h3>
                <p style="color: var(--text-secondary); font-size: 13px;">Timezone: Asia/Kuala_Lumpur</p>
            </div>
            <div>
                @if($schedule && $schedule->enabled)
                    <span class="badge" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">Enabled</span>
                @else
                    <span class="badge" style="background: rgba(160, 174, 192, 0.1); color: #a0aec0;">Disabled</span>
                @endif
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div>
                <div style="font-size: 12px; color: var(--text-secondary);">Next Run Time</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                    {{ $schedule?->run_time ?? 'Not set' }}
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--text-secondary);">Keyword</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                    {{ $schedule?->keyword ?? 'Software Engineer' }}
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--text-secondary);">Last Run</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                    {{ $schedule?->last_run_at ? $schedule->last_run_at->format('M j, Y H:i') : 'Not yet' }}
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--text-secondary);">Last Status</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                    {{ $schedule?->last_run_status ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(72, 187, 120, 0.2); color: #48bb78; padding: 15px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(72, 187, 120, 0.3);">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: rgba(245, 101, 101, 0.2); color: #f56565; padding: 15px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(245, 101, 101, 0.3);">
            {{ session('error') }}
        </div>
    @endif

    <!-- Job Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">💼</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">Total Jobs</div>
            <div style="font-size: 28px; font-weight: 700; color: var(--text-primary); margin: 5px 0;">{{ $jobStats['total'] }}</div>
        </div>
        @foreach($jobStats['by_source'] as $source)
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(237, 137, 54, 0.1); color: #ed8936;">🌐</div>
            <div style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">{{ $source->source ? ucfirst($source->source) : 'Unknown' }}</div>
            <div style="font-size: 24px; font-weight: 700; color: var(--text-primary); margin: 5px 0;">{{ $source->count }}</div>
        </div>
        @endforeach
    </div>

    <!-- Filters & Table -->
    <div class="content-card">
        <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 15px;">
            <h3 class="card-title">Job Listings</h3>
            
            <form action="{{ route('admin.jobs') }}" method="GET" style="display: flex; gap: 15px; width: 100%; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" placeholder="Search jobs..." class="form-input" value="{{ request('search') }}">
                </div>
                <div>
                    <select name="source" class="form-input" onchange="this.form.submit()">
                        <option value="">All Sources</option>
                        <option value="JobStreet" {{ request('source') == 'JobStreet' ? 'selected' : '' }}>JobStreet</option>
                        <option value="LinkedIn" {{ request('source') == 'LinkedIn' ? 'selected' : '' }}>LinkedIn</option>
                        <option value="Hiredly" {{ request('source') == 'Hiredly' ? 'selected' : '' }}>Hiredly</option>
                        <option value="Manual" {{ request('source') == 'Manual' ? 'selected' : '' }}>Manual</option>
                    </select>
                </div>
                @if(request()->anyFilled(['search', 'source']))
                    <a href="{{ route('admin.jobs') }}" class="btn btn-secondary">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th class="mobile-hide">Source</th>
                        <th class="mobile-hide">Posted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">{{ $job->title }}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">{{ $job->location }}</div>
                        </td>
                        <td>{{ $job->company }}</td>
                        <td class="mobile-hide">
                            <span class="badge" style="background: rgba(66, 153, 225, 0.1); color: #4299e1;">
                                {{ $job->source ? ucfirst($job->source) : 'Manual' }}
                            </span>
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">
                            {{ $job->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <form action="{{ route('admin.jobs.toggle', $job->id) }}" method="POST">
                                @csrf
                                <button type="submit" style="background: none; border: none; cursor: pointer;">
                                    <span class="badge" style="background: {{ $job->is_active ? 'rgba(72, 187, 120, 0.1)' : 'rgba(160, 174, 192, 0.1)' }}; color: {{ $job->is_active ? '#48bb78' : '#a0aec0' }}; display: inline-flex; align-items: center; gap: 5px;">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                        {{ $job->is_active ? 'Visible' : 'Hidden' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-icon" 
                                    onclick="openViewModal({{ $job }})"
                                    style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    👁️
                                </button>
                                <button class="btn-icon" 
                                    onclick="openEditModal({{ $job->id }}, '{{ addslashes($job->title) }}', '{{ addslashes($job->company) }}', '{{ addslashes($job->location) }}', '{{ addslashes($job->salary ?? '') }}', {{ $job->is_active ? 'true' : 'false' }})"
                                    style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    ✏️
                                </button>
                                
                                <form action="{{ route('admin.jobs.delete', $job->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this job?')"
                                        style="color: #f56565; background: rgba(245, 101, 101, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding: 20px; border-top: 1px solid var(--border-color);">
            {{ $jobs->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Create Job Modal -->
<div id="createModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Add New Job</h3>
        <form action="{{ route('admin.jobs.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Job Title</label>
                <input type="text" name="title" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Company</label>
                <input type="text" name="company" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Location</label>
                <input type="text" name="location" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Salary Range (Optional)</label>
                <input type="text" name="salary" class="form-input" placeholder="e.g. $80k - $120k">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Status</label>
                <select name="is_active" class="form-input">
                    <option value="1">Active (Visible)</option>
                    <option value="0">Draft (Hidden)</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('createModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Job</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Job Modal -->
<div id="editModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Edit Job</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Job Title</label>
                <input type="text" name="title" id="editTitle" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Company</label>
                <input type="text" name="company" id="editCompany" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Location</label>
                <input type="text" name="location" id="editLocation" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Salary Range</label>
                <input type="text" name="salary" id="editSalary" class="form-input">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Status</label>
                <select name="is_active" id="editStatus" class="form-input">
                    <option value="1">Active (Visible)</option>
                    <option value="0">Draft (Hidden)</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Job</button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Scrape Modal -->
<div id="scheduleModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Schedule Job Scraper</h3>
        <form action="{{ route('admin.jobs.schedule') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Run Time (24h)</label>
                <input type="time" name="run_time" required class="form-input" value="{{ $schedule?->run_time ?? '02:00' }}">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Keyword</label>
                <input type="text" name="keyword" class="form-input" placeholder="Software Engineer" value="{{ $schedule?->keyword ?? '' }}">
            </div>
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" id="scheduleEnabled" name="enabled" value="1" {{ $schedule && $schedule->enabled ? 'checked' : '' }}>
                <label for="scheduleEnabled" style="font-size: 13px; font-weight: 600;">Enable schedule</label>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('scheduleModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Job Details</h3>
        <div style="display: grid; gap: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">JOB TITLE</label>
                <div id="viewTitle" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">COMPANY</label>
                <div id="viewCompany" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">LOCATION</label>
                <div id="viewLocation" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">SALARY</label>
                <div id="viewSalary" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">SOURCE</label>
                <div id="viewSource" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">STATUS</label>
                <span id="viewStatusBadge" class="badge"></span>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
            <button type="button" onclick="closeModal('viewModal')" class="btn btn-secondary">Close</button>
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
                    This will fetch jobs from RapidAPI JSearch for the specified role.
                </p>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeScrapeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Start Scraping Job</button>
            </div>
        </form>
    </div>
</div>

<!-- Styles Reuse from Dashboard -->
<style>
/* ... (Include base styles or ensure they are in layout) */
/* Ideally, move dashboard CSS to layout or a shared CSS file. 
   For now, I'll rely on the styles defined in dashboard.blade.php IF they were global, 
   but they were scoped. I must duplicate or move them.
   Since I cannot easily move to a CSS file without asset compilation setup,
   I will include the necessary CSS here matching the dashboard.
*/
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

.dashboard-container { max-width: 1400px; margin: 0 auto; padding: 24px; }
.top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.page-title { font-size: 28px; font-weight: 700; color: var(--text-primary); margin: 0; }
.top-actions { display: flex; gap: 10px; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; }
.stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 10px; }

.content-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; }
.card-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 18px; font-weight: 600; margin: 0; color: var(--text-primary); }

.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
th { color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600; }
td { color: var(--text-primary); font-size: 14px; }

.badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.form-input { width: 100%; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 8px; }

.modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: none; justify-content: center; align-items: center; }
.modal-content { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 500px; padding: 30px; color: var(--text-primary); }
.modal-open { display: flex; }

.btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; items-center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-secondary { background: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color); }

@media (max-width: 768px) {
    .mobile-hide { display: none; }
}
</style>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('modal-open');
    }

    function openScheduleModal() {
        document.getElementById('scheduleModal').classList.add('modal-open');
    }

    function openEditModal(id, title, company, location, salary, isActive) {
        document.getElementById('editModal').classList.add('modal-open');
        document.getElementById('editTitle').value = title;
        document.getElementById('editCompany').value = company;
        document.getElementById('editLocation').value = location;
        document.getElementById('editSalary').value = salary;
        document.getElementById('editStatus').value = isActive ? '1' : '0';
        document.getElementById('editForm').action = '/admin/jobs/' + id;
    }

    function openViewModal(job) {
        document.getElementById('viewModal').classList.add('modal-open');
        document.getElementById('viewTitle').textContent = job.title;
        document.getElementById('viewCompany').textContent = job.company;
        document.getElementById('viewLocation').textContent = job.location;
        document.getElementById('viewSalary').textContent = job.salary ? job.salary : 'Not specified';
        document.getElementById('viewSource').textContent = job.source ? job.source : 'Manual';
        
        const badge = document.getElementById('viewStatusBadge');
        if(job.is_active) {
            badge.textContent = 'Active (Visible)';
            badge.style.background = 'rgba(72, 187, 120, 0.1)'; badge.style.color = '#48bb78';
        } else {
             badge.textContent = 'Draft (Hidden)';
             badge.style.background = 'rgba(160, 174, 192, 0.1)'; badge.style.color = '#a0aec0';
        }
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('modal-open');
    }
    
    
    // Scraper Modal Functions
    function openScrapeModal() {
        document.getElementById('scrapeModal').classList.add('modal-open');
    }
    
    function closeScrapeModal() {
        document.getElementById('scrapeModal').classList.remove('modal-open');
    }

    // Auto-close on outside click (Updated to include scrapeModal)
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-backdrop')) {
            event.target.classList.remove('modal-open');
        }
    }
</script>
@endsection
