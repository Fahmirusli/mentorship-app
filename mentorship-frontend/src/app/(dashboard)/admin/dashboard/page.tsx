'use client';

import { useState, useEffect } from 'react';
import { Users, Briefcase, DollarSign, TrendingUp, Search, Edit, Trash2, Loader, Plus, X, Globe, Save, Menu } from 'lucide-react';
import { api } from '@/lib/api';
import ThemeToggle from '@/components/ThemeToggle';

export default function AdminDashboard() {
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState({
        total_users: 0,
        total_mentors: 0,
        total_mentees: 0,
        total_sessions: 0,
        total_revenue: 0,
        active_jobs: 0,
        active_mentorships: 0
    });
    const [users, setUsers] = useState<any[]>([]);
    const [jobs, setJobs] = useState<any[]>([]);
    const [mentorships, setMentorships] = useState<any[]>([]);
    const [searchTerm, setSearchTerm] = useState('');

    // Scraper Modal State
    const [showScrapeModal, setShowScrapeModal] = useState(false);
    const [scrapeKeyword, setScrapeKeyword] = useState('');
    const [scraping, setScraping] = useState(false);

    // User Modal State
    const [showUserModal, setShowUserModal] = useState(false);
    const [editingUser, setEditingUser] = useState<any>(null);
    const [userForm, setUserForm] = useState({ name: '', email: '', role: 'mentee', is_active: true, password: '' });

    // Job Modal State
    const [showJobModal, setShowJobModal] = useState(false);
    const [editingJob, setEditingJob] = useState<any>(null);
    const [jobForm, setJobForm] = useState<any>({ title: '', company: '', description: '', location: '', source: 'Manual', is_active: true });

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            const statsRes = await api.get('/admin/dashboard');
            setStats(statsRes.data.stats);

            const usersRes = await api.get('/admin/users');
            setUsers(usersRes.data.data);

            const jobsRes = await api.get('/jobs?per_page=50');
            setJobs(jobsRes.data.data);

            const mentorshipsRes = await api.get('/admin/mentorships');
            setMentorships(mentorshipsRes.data.data);
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    // --- Scraper Functions ---
    const handleScrape = async () => {
        if (!scrapeKeyword.trim()) {
            alert('Please enter a keyword');
            return;
        }
        setScraping(true);
        try {
            await api.post('/jobs/scrape', { keyword: scrapeKeyword });
            alert(`Scraping for "${scrapeKeyword}" initiated successfully. Jobs will be updated shortly.`);
            setShowScrapeModal(false);
            setScrapeKeyword('');

            // Wait brief moment and refresh
            setTimeout(async () => {
                try {
                    const updatedJobs = await api.get('/jobs?per_page=50');
                    setJobs(updatedJobs.data.data);
                    const statsRes = await api.get('/admin/dashboard');
                    setStats(statsRes.data.stats);
                } catch (e) { console.error(e); }
            }, 1000);

        } catch (error) {
            console.error('Error triggering scrape:', error);
            alert('Failed to start scraping');
        } finally {
            setScraping(false);
        }
    };

    // --- User Functions ---
    const handleSaveUser = async () => {
        try {
            if (editingUser) {
                const res = await api.put(`/admin/users/${editingUser.id}`, userForm);
                setUsers(users.map(u => u.id === editingUser.id ? res.user : u)); // Adjust based on API response structure
                alert('User updated successfully');
            } else {
                alert('User creation not fully implemented via this modal endpoint.');
                return;
            }
            setShowUserModal(false);
            setEditingUser(null);
        } catch (error) {
            console.error('Error saving user:', error);
            alert('Failed to save user');
        }
    };

    const deleteUser = async (id: number) => {
        if (!confirm('Are you sure you want to delete this user?')) return;
        try {
            await api.delete(`/admin/users/${id}`);
            setUsers(users.filter(u => u.id !== id));
        } catch (error) {
            console.error('Error deleting user:', error);
            alert('Failed to delete user');
        }
    };

    // --- Job Functions ---
    const handleSaveJob = async () => {
        try {
            if (editingJob) {
                const res = await api.put(`/jobs/${editingJob.id}`, jobForm);
                setJobs(jobs.map(j => j.id === editingJob.id ? res : j));
            } else {
                const res = await api.post('/jobs', jobForm);
                setJobs([res, ...jobs]);
            }
            setShowJobModal(false);
            setEditingJob(null);
            fetchDashboardData();
        } catch (error) {
            console.error('Error saving job:', error);
            alert('Failed to save job');
        }
    };

    const deleteJob = async (id: number) => {
        if (!confirm('Are you sure you want to delete this job?')) return;
        try {
            await api.delete(`/jobs/${id}`);
            setJobs(jobs.filter(j => j.id !== id));
            setStats(prev => ({ ...prev, active_jobs: prev.active_jobs - 1 }));
        } catch (error) {
            console.error('Error deleting job:', error);
            alert('Failed to delete job');
        }
    };

    // --- Mentorship Functions ---
    const deleteMentorship = async (id: number) => {
        if (!confirm('Are you sure you want to delete this mentorship?')) return;
        try {
            await api.delete(`/mentorships/${id}`);
            setMentorships(mentorships.filter(m => m.id !== id));
            setStats(prev => ({ ...prev, active_mentorships: prev.active_mentorships - 1 }));
        } catch (error) {
            console.error('Error deleting mentorship:', error);
            alert('Failed to delete mentorship');
        }
    };

    const filteredUsers = users.filter(user =>
        user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                <div className="flex flex-col items-center">
                    <Loader className="w-10 h-10 animate-spin text-indigo-600 dark:text-indigo-400 mb-4" />
                    <p className="text-gray-500 dark:text-gray-400">Loading Dashboard...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-[#0f111a] text-gray-900 dark:text-gray-100 transition-colors duration-300">
            {/* Header / Top Bar */}
            <div className="bg-white dark:bg-[#1a1c23] border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-16">
                        <div className="flex items-center gap-4">
                            <h1 className="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                MentorCore Admin
                            </h1>
                        </div>
                        <div className="flex items-center gap-4">
                            <ThemeToggle />
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                    A
                                </div>
                                <span className="text-sm font-medium hidden sm:block">Admin</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8">

                {/* Stats Grid - Responsive Layout */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                    {/* Stat Card 1 */}
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</p>
                                <h3 className="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{stats.total_users}</h3>
                                <p className="text-xs text-green-500 mt-1 font-medium flex items-center">
                                    <TrendingUp className="w-3 h-3 mr-1" /> +12% this month
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-xl text-indigo-600 dark:text-indigo-400">
                                <Users className="w-6 h-6" />
                            </div>
                        </div>
                        <div className="mt-4 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div className="h-full bg-indigo-500 w-[70%] rounded-full"></div>
                        </div>
                    </div>

                    {/* Stat Card 2 */}
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Mentorships</p>
                                <h3 className="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{stats.active_mentorships}</h3>
                                <p className="text-xs text-green-500 mt-1 font-medium flex items-center">
                                    <TrendingUp className="w-3 h-3 mr-1" /> Active Sessions
                                </p>
                            </div>
                            <div className="p-3 bg-green-50 dark:bg-green-500/10 rounded-xl text-green-600 dark:text-green-400">
                                <Users className="w-6 h-6" />
                            </div>
                        </div>
                        <div className="mt-4 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div className="h-full bg-green-500 w-[45%] rounded-full"></div>
                        </div>
                    </div>

                    {/* Stat Card 3 */}
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                                <h3 className="text-3xl font-bold mt-2 text-gray-900 dark:text-white">RM{stats.total_revenue.toLocaleString()}</h3>
                                <p className="text-xs text-gray-400 mt-1">Gross income</p>
                            </div>
                            <div className="p-3 bg-yellow-50 dark:bg-yellow-500/10 rounded-xl text-yellow-600 dark:text-yellow-400">
                                <DollarSign className="w-6 h-6" />
                            </div>
                        </div>
                        <div className="mt-4 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div className="h-full bg-yellow-500 w-[60%] rounded-full"></div>
                        </div>
                    </div>

                    {/* Stat Card 4 */}
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl p-6 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Active Jobs</p>
                                <h3 className="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{stats.active_jobs}</h3>
                                <p className="text-xs text-indigo-400 mt-1">Across all platforms</p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-500/10 rounded-xl text-blue-600 dark:text-blue-400">
                                <Briefcase className="w-6 h-6" />
                            </div>
                        </div>
                        <div className="mt-4 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div className="h-full bg-blue-500 w-[85%] rounded-full"></div>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* User Management Section */}
                    <div className="lg:col-span-2 bg-white dark:bg-[#1a1c23] rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white">Recent Users</h2>
                            <div className="relative w-full sm:w-auto">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search users..."
                                    className="w-full sm:w-64 pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
                                />
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 font-medium">
                                    <tr>
                                        <th className="py-3 px-6">Name</th>
                                        <th className="py-3 px-6 hidden sm:table-cell">Role</th>
                                        <th className="py-3 px-6 hidden md:table-cell">Status</th>
                                        <th className="py-3 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {filteredUsers.slice(0, 5).map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td className="py-4 px-6">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                                        {user.name.charAt(0)}
                                                    </div>
                                                    <div>
                                                        <p className="font-medium text-gray-900 dark:text-white">{user.name}</p>
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">{user.email}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-6 hidden sm:table-cell">
                                                <span className={`px-2.5 py-1 rounded-full text-xs font-medium capitalize ${user.role === 'mentor'
                                                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400'
                                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                                                    }`}>
                                                    {user.role}
                                                </span>
                                            </td>
                                            <td className="py-4 px-6 hidden md:table-cell">
                                                <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${user.is_active
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                    }`}>
                                                    <span className={`w-1.5 h-1.5 rounded-full ${user.is_active ? 'bg-green-500' : 'bg-gray-500'}`}></span>
                                                    {user.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="py-4 px-6 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <button onClick={() => { setEditingUser(user); setUserForm({ ...user, password: '' }); setShowUserModal(true); }}
                                                        className="p-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
                                                        <Edit className="w-4 h-4" />
                                                    </button>
                                                    <button onClick={() => deleteUser(user.id)}
                                                        className="p-1.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="p-4 border-t border-gray-100 dark:border-gray-800 text-center">
                            <button className="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                View All Users
                            </button>
                        </div>
                    </div>

                    {/* Job Management Section - With Scraping Button Here */}
                    <div className="lg:col-span-1 bg-white dark:bg-[#1a1c23] rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-800">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white">Job Overview</h2>
                        </div>

                        {/* Action Buttons Area */}
                        <div className="p-6 space-y-3">
                            <button
                                onClick={() => {
                                    setEditingJob(null);
                                    setJobForm({ title: '', company: '', description: '', location: '', source: 'Manual', is_active: true });
                                    setShowJobModal(true);
                                }}
                                className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-colors shadow-lg shadow-indigo-500/20"
                            >
                                <Plus className="w-5 h-5" />
                                Add New Job manually
                            </button>

                            <button
                                onClick={() => {
                                    setScrapeKeyword('');
                                    setShowScrapeModal(true);
                                }}
                                className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-white dark:bg-gray-800 border-2 border-indigo-600 dark:border-indigo-500 text-indigo-600 dark:text-indigo-400 rounded-xl font-medium hover:bg-indigo-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <Globe className="w-5 h-5" />
                                + New Scraping Job
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto px-6 pb-6">
                            <h3 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Recent Jobs</h3>
                            <div className="space-y-3">
                                {jobs.slice(0, 5).map(job => (
                                    <div key={job.id} className="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 flex justify-between items-center group">
                                        <div className="min-w-0">
                                            <p className="font-medium text-gray-900 dark:text-white truncate">{job.title}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{job.company}</p>
                                        </div>
                                        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onClick={() => { setEditingJob(job); setJobForm({ ...job }); setShowJobModal(true); }} className="p-1 text-gray-400 hover:text-indigo-500">
                                                <Edit className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* --- MODALS --- */}

            {/* Scraper Modal - Fixed UI */}
            {showScrapeModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all scale-100">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Globe className="w-5 h-5 text-indigo-500" />
                                Web Scraper Configuration
                            </h2>
                            <button onClick={() => setShowScrapeModal(false)} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <div className="p-6">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Target Job Keyword
                            </label>
                            <input
                                type="text"
                                className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900 dark:text-white placeholder-gray-400"
                                value={scrapeKeyword}
                                onChange={(e) => setScrapeKeyword(e.target.value)}
                                placeholder="e.g. 'Software Engineer' or 'Data Analyst'"
                                autoFocus
                            />
                            <p className="mt-3 text-xs text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
                                <span className="font-semibold text-blue-600 dark:text-blue-400">Note:</span> Scraping runs in the background. Results will appear in the Jobs list automatically.
                            </p>
                        </div>
                        <div className="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                            <button
                                onClick={() => setShowScrapeModal(false)}
                                className="px-5 py-2.5 text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleScrape}
                                disabled={scraping}
                                className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                {scraping ? <Loader className="w-4 h-4 animate-spin" /> : <Globe className="w-4 h-4" />}
                                {scraping ? 'Initiating...' : 'Start Scraping'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* User Modal */}
            {showUserModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-800">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-800">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">{editingUser ? 'Edit User' : 'Create User'}</h2>
                        </div>
                        <div className="p-6 space-y-4">
                            <input
                                type="text" placeholder="Name"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={userForm.name}
                                onChange={(e) => setUserForm({ ...userForm, name: e.target.value })}
                            />
                            <input
                                type="email" placeholder="Email"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={userForm.email}
                                disabled={!!editingUser}
                                onChange={(e) => setUserForm({ ...userForm, email: e.target.value })}
                            />
                            <select
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={userForm.role}
                                onChange={(e) => setUserForm({ ...userForm, role: e.target.value })}
                            >
                                <option value="mentee">Mentee</option>
                                <option value="mentor">Mentor</option>
                                <option value="admin">Admin</option>
                            </select>
                            <label className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={userForm.is_active}
                                    onChange={(e) => setUserForm({ ...userForm, is_active: e.target.checked })}
                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active Account
                            </label>
                        </div>
                        <div className="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                            <button onClick={() => setShowUserModal(false)} className="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">Cancel</button>
                            <button onClick={handleSaveUser} className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Changes</button>
                        </div>
                    </div>
                </div>
            )}

            {/* Job Modal */}
            {showJobModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-[#1a1c23] rounded-2xl w-full max-w-lg shadow-2xl border border-gray-100 dark:border-gray-800">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-800">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">{editingJob ? 'Edit Job' : 'Create Job'}</h2>
                        </div>
                        <div className="p-6 space-y-4">
                            <input
                                type="text" placeholder="Job Title"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={jobForm.title}
                                onChange={(e) => setJobForm({ ...jobForm, title: e.target.value })}
                            />
                            <input
                                type="text" placeholder="Company"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={jobForm.company}
                                onChange={(e) => setJobForm({ ...jobForm, company: e.target.value })}
                            />
                            <input
                                type="text" placeholder="Location"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg dark:bg-gray-900 dark:text-white"
                                value={jobForm.location}
                                onChange={(e) => setJobForm({ ...jobForm, location: e.target.value })}
                            />
                            <textarea
                                placeholder="Description"
                                className="w-full px-4 py-2 border dark:border-gray-700 rounded-lg h-24 dark:bg-gray-900 dark:text-white"
                                value={jobForm.description}
                                onChange={(e) => setJobForm({ ...jobForm, description: e.target.value })}
                            />
                        </div>
                        <div className="p-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                            <button onClick={() => setShowJobModal(false)} className="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">Cancel</button>
                            <button onClick={handleSaveJob} className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Job</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
