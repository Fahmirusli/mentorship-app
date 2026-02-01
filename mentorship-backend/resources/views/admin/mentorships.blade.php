@extends('layouts.app')

@section('title', 'Mentorships')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Active Mentorships</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Track and manage mentorship sessions</p>
        </div>
        <div class="top-actions">
            <button class="btn btn-primary" onclick="openCreateModal()">
                + New Session
            </button>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(72, 187, 120, 0.2); color: #48bb78; padding: 15px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(72, 187, 120, 0.3);">
            {{ session('success') }}
        </div>
    @endif

    <div class="content-card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mentor</th>
                        <th>Mentee</th>
                        <th class="mobile-hide">Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mentorships as $mentorship)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(159, 122, 234, 0.1); color: #9f7aea; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    M
                                </div>
                                <div style="font-weight: 500; color: var(--text-primary);">{{ $mentorship->mentor->name ?? 'Unknown' }}</div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500; color: var(--text-primary);">{{ $mentorship->mentee->name ?? 'Unknown' }}</div>
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">
                            {{ $mentorship->appointment_date ? \Carbon\Carbon::parse($mentorship->appointment_date)->format('M d, Y') : 'TBD' }} 
                            <span style="font-size: 12px; margin-left: 5px;">{{ $mentorship->appointment_time }}</span>
                        </td>
                        <td>
                            <span class="badge" style="background: {{ $mentorship->status === 'confirmed' ? 'rgba(72, 187, 120, 0.1)' : ($mentorship->status === 'pending' ? 'rgba(237, 137, 54, 0.1)' : 'rgba(66, 153, 225, 0.1)') }}; color: {{ $mentorship->status === 'confirmed' ? '#48bb78' : ($mentorship->status === 'pending' ? '#ed8936' : '#4299e1') }}">
                                {{ ucfirst($mentorship->status) }}
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openViewModal({{ $mentorship }})" class="btn-icon" style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    👁️
                                </button>
                                <button onclick="openEditModal({{ $mentorship }})" class="btn-icon" style="color: #ed8936; background: rgba(237, 137, 54, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    ✏️
                                </button>
                                <form action="{{ route('admin.mentorships.delete', $mentorship->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this session?')" style="color: #f56565; background: rgba(245, 101, 101, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            No mentorship sessions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid var(--border-color);">
            {{ $mentorships->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Schedule New Session</h3>
        <form action="{{ route('admin.mentorships.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Mentor</label>
                <select name="mentor_id" class="form-input" required>
                    <option value="">Select Mentor</option>
                    @foreach($allMentors as $mentor)
                        <option value="{{ $mentor->id }}">{{ $mentor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Mentee</label>
                <select name="mentee_id" class="form-input" required>
                    <option value="">Select Mentee</option>
                    @foreach($allMentees as $mentee)
                        <option value="{{ $mentee->id }}">{{ $mentee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Date</label>
                    <input type="date" name="appointment_date" class="form-input" required>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Time</label>
                    <input type="time" name="appointment_time" class="form-input" required>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Status</label>
                <select name="status" class="form-input" required>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('createModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Session</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Edit Session</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Mentor</label>
                <select name="mentor_id" id="editMentor" class="form-input" required>
                    @foreach($allMentors as $mentor)
                        <option value="{{ $mentor->id }}">{{ $mentor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Mentee</label>
                <select name="mentee_id" id="editMentee" class="form-input" required>
                    @foreach($allMentees as $mentee)
                        <option value="{{ $mentee->id }}">{{ $mentee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Date</label>
                    <input type="date" name="appointment_date" id="editDate" class="form-input" required>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Time</label>
                    <input type="time" name="appointment_time" id="editTime" class="form-input" required>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Status</label>
                <select name="status" id="editStatus" class="form-input" required>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Session</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal (Read Only) -->
<div id="viewModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Session Details</h3>
        <div style="display: grid; gap: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">MENTOR</label>
                <div id="viewMentor" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">MENTEE</label>
                <div id="viewMentee" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">DATE</label>
                    <div id="viewDate" style="font-weight: 500;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">TIME</label>
                    <div id="viewTime" style="font-weight: 500;"></div>
                </div>
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
.btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; items-center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-secondary { background: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color); }
.form-input { width: 100%; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-primary); padding: 10px; border-radius: 8px; }
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: none; justify-content: center; align-items: center; }
.modal-content { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 500px; padding: 30px; color: var(--text-primary); }
.modal-open { display: flex; }
@media (max-width: 768px) { .mobile-hide { display: none; } }
</style>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.add('modal-open');
    }

    function openEditModal(mentorship) {
        document.getElementById('editModal').classList.add('modal-open');
        document.getElementById('editMentor').value = mentorship.mentor_id;
        document.getElementById('editMentee').value = mentorship.mentee_id;
        document.getElementById('editDate').value = mentorship.appointment_date; // Ensure format YYYY-MM-DD
        document.getElementById('editTime').value = mentorship.appointment_time;
        document.getElementById('editStatus').value = mentorship.status;
        document.getElementById('editForm').action = '/admin/mentorships/' + mentorship.id;
    }

    function openViewModal(mentorship) {
        document.getElementById('viewModal').classList.add('modal-open');
        // Safely handle potential nulls if relationship is broken, though controller ensures existence usually
        const mentorName = mentorship.mentor ? mentorship.mentor.name : 'Unknown';
        const menteeName = mentorship.mentee ? mentorship.mentee.name : 'Unknown';
        
        document.getElementById('viewMentor').textContent = mentorName;
        document.getElementById('viewMentee').textContent = menteeName;
        document.getElementById('viewDate').textContent = mentorship.appointment_date;
        document.getElementById('viewTime').textContent = mentorship.appointment_time;
        
        const badge = document.getElementById('viewStatusBadge');
        badge.textContent = mentorship.status.charAt(0).toUpperCase() + mentorship.status.slice(1);
        
        // Simple color logic
        if(mentorship.status === 'confirmed') {
            badge.style.background = 'rgba(72, 187, 120, 0.1)'; badge.style.color = '#48bb78';
        } else if(mentorship.status === 'pending') {
            badge.style.background = 'rgba(237, 137, 54, 0.1)'; badge.style.color = '#ed8936';
        } else {
             badge.style.background = 'rgba(66, 153, 225, 0.1)'; badge.style.color = '#4299e1';
        }
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('modal-open');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-backdrop')) {
             event.target.classList.remove('modal-open');
        }
    }
</script>
@endsection
