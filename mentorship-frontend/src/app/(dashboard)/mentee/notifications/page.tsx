'use client';

import { useState, useEffect } from 'react';
import { Bell, Check, Trash2, Loader } from 'lucide-react';
import { api } from '@/lib/api';

interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    read: boolean;
    created_at: string;
}

export default function Notifications() {
    const [loading, setLoading] = useState(true);
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [filter, setFilter] = useState<'all' | 'unread'>('all');

    useEffect(() => {
        fetchNotifications();
    }, []);

    const fetchNotifications = async () => {
        try {
            // Mock notifications for now - replace with actual API call
            const mockNotifications: Notification[] = [
                {
                    id: 1,
                    type: 'appointment',
                    title: 'New Session Booked',
                    message: 'John Doe has booked a session with you for tomorrow at 2:00 PM',
                    read: false,
                    created_at: new Date().toISOString()
                },
                {
                    id: 2,
                    type: 'message',
                    title: 'New Message',
                    message: 'You have a new message from Sarah Smith',
                    read: false,
                    created_at: new Date(Date.now() - 3600000).toISOString()
                },
                {
                    id: 3,
                    type: 'feedback',
                    title: 'New Feedback Received',
                    message: 'Mike Johnson left you a 5-star review',
                    read: true,
                    created_at: new Date(Date.now() - 86400000).toISOString()
                }
            ];

            setNotifications(mockNotifications);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            setLoading(false);
        }
    };

    const markAsRead = async (id: number) => {
        try {
            // await api.put(`/notifications/${id}/read`);
            setNotifications(notifications.map(n =>
                n.id === id ? { ...n, read: true } : n
            ));
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    };

    const deleteNotification = async (id: number) => {
        try {
            // await api.delete(`/notifications/${id}`);
            setNotifications(notifications.filter(n => n.id !== id));
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    };

    const markAllAsRead = async () => {
        try {
            // await api.post('/notifications/mark-all-read');
            setNotifications(notifications.map(n => ({ ...n, read: true })));
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    };

    const getNotificationIcon = (type: string) => {
        switch (type) {
            case 'appointment':
                return '📅';
            case 'message':
                return '💬';
            case 'feedback':
                return '⭐';
            default:
                return '🔔';
        }
    };

    const filteredNotifications = filter === 'unread'
        ? notifications.filter(n => !n.read)
        : notifications;

    const unreadCount = notifications.filter(n => !n.read).length;

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <Loader className="w-8 h-8 animate-spin text-indigo-600" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-4xl mx-auto">
                <div className="bg-white rounded-xl shadow-sm p-8">
                    <div className="flex items-center justify-between mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Notifications</h1>
                            <p className="text-gray-600 mt-2">
                                {unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}
                            </p>
                        </div>
                        <Bell className="w-12 h-12 text-indigo-600" />
                    </div>

                    {/* Filter and Actions */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex gap-2">
                            <button
                                onClick={() => setFilter('all')}
                                className={`px-4 py-2 rounded-lg font-medium transition ${filter === 'all'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                            >
                                All
                            </button>
                            <button
                                onClick={() => setFilter('unread')}
                                className={`px-4 py-2 rounded-lg font-medium transition ${filter === 'unread'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                            >
                                Unread ({unreadCount})
                            </button>
                        </div>

                        {unreadCount > 0 && (
                            <button
                                onClick={markAllAsRead}
                                className="px-4 py-2 text-indigo-600 hover:bg-indigo-50 rounded-lg font-medium transition"
                            >
                                Mark all as read
                            </button>
                        )}
                    </div>

                    {/* Notifications List */}
                    <div className="space-y-3">
                        {filteredNotifications.length === 0 ? (
                            <div className="text-center py-12 bg-gray-50 rounded-lg">
                                <Bell className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500">No notifications</p>
                                <p className="text-sm text-gray-400 mt-2">
                                    {filter === 'unread' ? 'All caught up!' : 'You have no notifications yet'}
                                </p>
                            </div>
                        ) : (
                            filteredNotifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={`p-4 rounded-lg border transition ${notification.read
                                            ? 'bg-white border-gray-200'
                                            : 'bg-indigo-50 border-indigo-200'
                                        }`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="text-2xl">{getNotificationIcon(notification.type)}</div>

                                        <div className="flex-1">
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <h3 className="font-semibold text-gray-900">{notification.title}</h3>
                                                    <p className="text-gray-600 text-sm mt-1">{notification.message}</p>
                                                    <p className="text-xs text-gray-400 mt-2">
                                                        {new Date(notification.created_at).toLocaleString()}
                                                    </p>
                                                </div>

                                                <div className="flex items-center gap-2 ml-4">
                                                    {!notification.read && (
                                                        <button
                                                            onClick={() => markAsRead(notification.id)}
                                                            className="p-2 text-indigo-600 hover:bg-indigo-100 rounded-lg transition"
                                                            title="Mark as read"
                                                        >
                                                            <Check className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => deleteNotification(notification.id)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
