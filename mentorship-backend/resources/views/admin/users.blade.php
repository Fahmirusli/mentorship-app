@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">User Management</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Manage mentors, mentees, and admins</p>
        </div>
        <div class="top-actions">
            <button class="btn btn-primary" onclick="openCreateModal()">
                + Create User
            </button>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(72, 187, 120, 0.2); color: #48bb78; padding: 15px; border-radius: 12px; margin-bottom: 24px; border: 1px solid rgba(72, 187, 120, 0.3);">
            {{ session('success') }}
        </div>
    @endif

    <div class="content-card">
        <div class="card-header" style="gap: 15px;">
            <h3 class="card-title">Users List</h3>
            <form action="{{ route('admin.users') }}" method="GET" style="display: flex; gap: 10px;">
                <input type="text" name="search" placeholder="Search by name or email..." class="form-input" value="{{ request('search') }}" style="width: 250px;">
                <button type="submit" class="btn btn-secondary">🔍</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th class="mobile-hide">Email Verified</th>
                        <th class="mobile-hide">Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-primary);">{{ $user->name }}</div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background: {{ $user->role === 'admin' ? 'rgba(245, 101, 101, 0.1)' : ($user->role === 'mentor' ? 'rgba(159, 122, 234, 0.1)' : 'rgba(66, 153, 225, 0.1)') }}; color: {{ $user->role === 'admin' ? '#f56565' : ($user->role === 'mentor' ? '#9f7aea' : '#4299e1') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="mobile-hide">
                            @if($user->email_verified_at)
                                <span class="badge" style="background: rgba(72, 187, 120, 0.1); color: #48bb78;">Verified</span>
                            @else
                                <span class="badge" style="background: rgba(237, 137, 54, 0.1); color: #ed8936;">Unverified</span>
                            @endif
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">
                            {{ $user->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-icon" 
                                    onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ $user->role }}')"
                                    style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    ✏️
                                </button>
                                
                                @if(!$user->email_verified_at)
                                <form action="{{ route('admin.users.verify', $user->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Verify this user?')"
                                        style="color: #48bb78; background: rgba(72, 187, 120, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Verify User">
                                        ✅
                                    </button>
                                </form>
                                @endif

                                @if($user->role !== 'admin')
                                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this user?')"
                                        style="color: #f56565; background: rgba(245, 101, 101, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        🗑️
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="padding: 20px; border-top: 1px solid var(--border-color);">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div id="createModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Create New User</h3>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Name</label>
                <input type="text" name="name" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Email</label>
                <input type="email" name="email" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" required class="form-input" minlength="8">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Role</label>
                <select name="role" class="form-input">
                    <option value="mentee">Mentee</option>
                    <option value="mentor">Mentor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('createModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Edit User</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Name</label>
                <input type="text" name="name" id="editName" required class="form-input">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Email</label>
                <input type="email" name="email" id="editEmail" required class="form-input">
            </div>
             <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px;">Role</label>
                <select name="role" id="editRole" class="form-input">
                    <option value="mentee">Mentee</option>
                    <option value="mentor">Mentor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Include same CSS variables and classes as jobs/dashboard */
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
@media (max-width: 768px) { .mobile-hide { display: none; } }
</style>

<script>
    function openCreateModal() { document.getElementById('createModal').classList.add('modal-open'); }
    function openEditModal(id, name, email, role) {
        document.getElementById('editModal').classList.add('modal-open');
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('editForm').action = '/admin/users/' + id;
    }
    function closeModal(id) { document.getElementById(id).classList.remove('modal-open'); }
    window.onclick = function(event) { if (event.target.classList.contains('modal-backdrop')) event.target.classList.remove('modal-open'); }
</script>
@endsection
