'use client';

import { useState, useEffect } from 'react';
import { Settings as SettingsIcon, Lock, Bell, Globe, Save, Loader } from 'lucide-react';
import { api } from '@/lib/api';

export default function Settings() {
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [settings, setSettings] = useState({
        email_notifications: true,
        push_notifications: true,
        session_reminders: true,
        marketing_emails: false,
        language: 'en',
        timezone: 'Asia/Kuala_Lumpur'
    });
    const [passwordData, setPasswordData] = useState({
        current_password: '',
        new_password: '',
        confirm_password: ''
    });

    const handleSaveSettings = async () => {
        setSaving(true);
        try {
            await api.put('/user/settings', settings);
            alert('Settings saved successfully!');
        } catch (error) {
            console.error('Error saving settings:', error);
            alert('Failed to save settings');
        } finally {
            setSaving(false);
        }
    };

    const handleChangePassword = async () => {
        if (passwordData.new_password !== passwordData.confirm_password) {
            alert('Passwords do not match');
            return;
        }

        try {
            await api.post('/user/change-password', {
                current_password: passwordData.current_password,
                new_password: passwordData.new_password
            });
            alert('Password changed successfully!');
            setPasswordData({ current_password: '', new_password: '', confirm_password: '' });
        } catch (error) {
            console.error('Error changing password:', error);
            alert('Failed to change password');
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-4xl mx-auto">
                <div className="bg-white rounded-xl shadow-sm p-8">
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Settings</h1>
                            <p className="text-gray-600 mt-2">Manage your account preferences</p>
                        </div>
                        <SettingsIcon className="w-12 h-12 text-indigo-600" />
                    </div>

                    {/* Notifications */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <div className="flex items-center gap-3">
                            <Bell className="w-5 h-5 text-gray-700" />
                            <h2 className="text-xl font-semibold text-gray-900">Notifications</h2>
                        </div>

                        <div className="space-y-4">
                            <label className="flex items-center justify-between cursor-pointer">
                                <div>
                                    <p className="font-medium text-gray-900">Email Notifications</p>
                                    <p className="text-sm text-gray-600">Receive notifications via email</p>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={settings.email_notifications}
                                    onChange={(e) => setSettings({ ...settings, email_notifications: e.target.checked })}
                                    className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                />
                            </label>

                            <label className="flex items-center justify-between cursor-pointer">
                                <div>
                                    <p className="font-medium text-gray-900">Push Notifications</p>
                                    <p className="text-sm text-gray-600">Receive push notifications in browser</p>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={settings.push_notifications}
                                    onChange={(e) => setSettings({ ...settings, push_notifications: e.target.checked })}
                                    className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                />
                            </label>

                            <label className="flex items-center justify-between cursor-pointer">
                                <div>
                                    <p className="font-medium text-gray-900">Session Reminders</p>
                                    <p className="text-sm text-gray-600">Get reminded before scheduled sessions</p>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={settings.session_reminders}
                                    onChange={(e) => setSettings({ ...settings, session_reminders: e.target.checked })}
                                    className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                />
                            </label>

                            <label className="flex items-center justify-between cursor-pointer">
                                <div>
                                    <p className="font-medium text-gray-900">Marketing Emails</p>
                                    <p className="text-sm text-gray-600">Receive updates and promotions</p>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={settings.marketing_emails}
                                    onChange={(e) => setSettings({ ...settings, marketing_emails: e.target.checked })}
                                    className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                />
                            </label>
                        </div>
                    </div>

                    {/* Preferences */}
                    <div className="space-y-6 mb-8 pb-8 border-b">
                        <div className="flex items-center gap-3">
                            <Globe className="w-5 h-5 text-gray-700" />
                            <h2 className="text-xl font-semibold text-gray-900">Preferences</h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Language</label>
                                <select
                                    value={settings.language}
                                    onChange={(e) => setSettings({ ...settings, language: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                >
                                    <option value="en">English</option>
                                    <option value="ms">Bahasa Malaysia</option>
                                    <option value="zh">中文</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select
                                    value={settings.timezone}
                                    onChange={(e) => setSettings({ ...settings, timezone: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                >
                                    <option value="Asia/Kuala_Lumpur">Asia/Kuala Lumpur (GMT+8)</option>
                                    <option value="Asia/Singapore">Asia/Singapore (GMT+8)</option>
                                    <option value="Asia/Jakarta">Asia/Jakarta (GMT+7)</option>
                                </select>
                            </div>
                        </div>

                        <button
                            onClick={handleSaveSettings}
                            disabled={saving}
                            className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            {saving ? (
                                <>
                                    <Loader className="w-4 h-4 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                <>
                                    <Save className="w-4 h-4" />
                                    Save Settings
                                </>
                            )}
                        </button>
                    </div>

                    {/* Change Password */}
                    <div className="space-y-6">
                        <div className="flex items-center gap-3">
                            <Lock className="w-5 h-5 text-gray-700" />
                            <h2 className="text-xl font-semibold text-gray-900">Change Password</h2>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input
                                    type="password"
                                    value={passwordData.current_password}
                                    onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input
                                    type="password"
                                    value={passwordData.new_password}
                                    onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input
                                    type="password"
                                    value={passwordData.confirm_password}
                                    onChange={(e) => setPasswordData({ ...passwordData, confirm_password: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                />
                            </div>

                            <button
                                onClick={handleChangePassword}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Change Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
