@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<div class="dashboard-container">
    <div class="top-bar">
        <div>
            <h1 class="page-title">Feedback & Reviews</h1>
            <p style="color: var(--text-secondary); margin-top: 5px;">Monitor mentorship feedback from users</p>
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
                        <th>From User</th>
                        <th>To Mentor/User</th>
                        <th>Rating</th>
                        <th class="mobile-hide">Comment</th>
                        <th class="mobile-hide">Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedbacks as $feedback)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $feedback->fromUser->name ?? 'Unknown' }}
                            </div>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                {{ ucfirst($feedback->fromUser->role ?? '') }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);">
                                {{ $feedback->toUser->name ?? 'Unknown' }}
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="font-weight: 700; color: #ed8936;">{{ $feedback->rating }}.0</span>
                                <span style="color: #ed8936;">★</span>
                            </div>
                        </td>
                        <td class="mobile-hide">
                            <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-secondary);" title="{{ $feedback->comment }}">
                                {{ $feedback->comment }}
                            </div>
                        </td>
                        <td class="mobile-hide" style="color: var(--text-secondary);">
                            {{ $feedback->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="openViewModal('{{ addslashes($feedback->fromUser->name ?? 'Unknown') }}', '{{ addslashes($feedback->toUser->name ?? 'Unknown') }}', {{ $feedback->rating }}, '{{ addslashes($feedback->comment) }}')" 
                                    class="btn-icon" style="color: #667eea; background: rgba(102, 126, 234, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    👁️
                                </button>
                                <form action="{{ route('admin.feedbacks.delete', $feedback->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this feedback?')" 
                                        style="color: #f56565; background: rgba(245, 101, 101, 0.1); width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            No feedback records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid var(--border-color);">
            {{ $feedbacks->links() }}
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal-backdrop">
    <div class="modal-content">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Feedback Details</h3>
        <div style="display: grid; gap: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">FROM</label>
                <div id="viewFrom" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">TO</label>
                <div id="viewTo" style="font-weight: 500; font-size: 16px;"></div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">RATING</label>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span id="viewRating" style="font-weight: 700; font-size: 18px; color: #ed8936;"></span>
                    <span style="color: #ed8936;">★</span>
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary);">COMMENT</label>
                <div id="viewComment" style="font-weight: 400; line-height: 1.5; background: var(--bg-color); padding: 15px; border-radius: 8px; margin-top: 5px;"></div>
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
.btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; items-center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-secondary { background: var(--bg-color); color: var(--text-secondary); border: 1px solid var(--border-color); }
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; display: none; justify-content: center; align-items: center; }
.modal-content { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; width: 100%; max-width: 500px; padding: 30px; color: var(--text-primary); }
.modal-open { display: flex; }
@media (max-width: 768px) { .mobile-hide { display: none; } }
</style>

<script>
    function openViewModal(from, to, rating, comment) {
        document.getElementById('viewModal').classList.add('modal-open');
        document.getElementById('viewFrom').textContent = from;
        document.getElementById('viewTo').textContent = to;
        document.getElementById('viewRating').textContent = rating + '.0';
        document.getElementById('viewComment').textContent = comment;
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
