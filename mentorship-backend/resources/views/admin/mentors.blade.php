@extends('layouts.app')

@section('title', 'Mentors')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Mentors Management</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Manage platform mentors</p>
        </div>
        <div class="top-actions">
            <button class="btn btn-primary" onclick="openCreateModal()">
                + Create Mentor
            </button>
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

    <div class="content-card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mentor Name</th>
                        <th class="mobile-hide">Email</th>
                        <th>Rating</th>
                        <th class="mobile-hide">Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mentors as $mentor)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(159, 122, 234, 0.1); color: #9f7aea; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    M
                                </div>
                                <div style="font-weight: 600; color: var(--text-primary);">{{ $mentor->name }}</div>
                            </div>
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">{{ $mentor->email }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="font-weight: 700; color: #ed8936;">{{ $mentor->average_rating }}</span>
                                <span style="color: #ed8936;">★</span>
                            </div>
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">
                            {{ $mentor->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openViewModal({{ $mentor }})" class="btn-icon" style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    👁️
                                </button>
                                <button onclick="openEditModal({{ $mentor }})" class="btn-icon" style="color: #ed8936; background: rgba(237, 137, 54, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    ✏️
                                </button>
                                <form action="{{ route('admin.users.delete', $mentor->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Remove this mentor?')"
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
            {{ $mentors->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Create New Mentor</h3>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <input type="hidden" name="role" value="mentor">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Full Name</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Email Address</label>
                <input type="email" name="email" class="form-input" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" class="form-input" required minlength="8">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('createModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Mentor</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Edit Mentor</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="role" value="mentor">
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Full Name</label>
                <input type="text" name="name" id="editName" class="form-input" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Email Address</label>
                <input type="email" name="email" id="editEmail" class="form-input" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Mentor</button>
            </div>
        </form>
    </div>
</div>

<!-- View Modal (Read Only) -->
<div id="viewModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Mentor Details</h3>
         <div style="display: grid; gap: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">NAME</label>
                <div id="viewName" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">EMAIL</label>
                <div id="viewEmail" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">ROLE</label>
                <span class="badge" style="background: rgba(159, 122, 234, 0.1); color: #9f7aea;">Mentor</span>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
            <button type="button" onclick="closeModal('viewModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<style>
/* Reuse Styles */
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

    function openEditModal(user) {
        document.getElementById('editModal').classList.add('modal-open');
        document.getElementById('editName').value = user.name;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editForm').action = '/admin/users/' + user.id;
    }
    
    function openViewModal(user) {
        document.getElementById('viewModal').classList.add('modal-open');
        document.getElementById('viewName').textContent = user.name;
        document.getElementById('viewEmail').textContent = user.email;
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
