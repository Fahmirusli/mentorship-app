'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard, Briefcase, Users, Calendar,
  BookOpen, LogOut, Settings, Bell, User, Menu, X, CheckCircle, DollarSign
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { authService } from '@/lib/auth';
import { api } from '@/lib/api';
import { getEcho } from '@/lib/echo';
import { Logo } from '@/components/Logo';
import { toast, Toaster } from 'react-hot-toast';

export function Sidebar({ role }: { role: 'mentor' | 'mentee' }) {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    setUser(currentUser);

    // Fetch latest user data so navbar reflects profile updates without re-login.
    api.get('/user')
      .then((freshUser) => {
        setUser((prev: any) => ({ ...prev, ...freshUser }));
      })
      .catch((error) => {
        console.error('Failed to refresh user for navbar', error);
      });

    // Fetch initial unread count
    api.get('/notifications/unread-count')
      .then((res) => {
        if (res.unread_count !== undefined) {
          setUnreadCount(res.unread_count);
        }
      })
      .catch((err) => console.error('Failed to fetch unread count', err));

    const echo = getEcho();
    if (echo && currentUser?.id) {
      echo.private(`user.${currentUser.id}`)
        .listen('NotificationCreated', (e: any) => {
          setUnreadCount(prev => prev + 1);
          if (e.notification) {
            toast(
              (t) => (
                <div className="flex flex-col gap-1 cursor-pointer" onClick={() => {
                  toast.dismiss(t.id);
                  handleNotifications();
                }}>
                  <span className="font-bold text-sm text-gray-900">{e.notification.title}</span>
                  <span className="text-sm text-gray-600 line-clamp-2">{e.notification.body}</span>
                </div>
              ),
              { icon: '🔔', duration: 5000 }
            );
          }
        });
    }

    const handleProfileImageUpdated = (event: Event) => {
      const customEvent = event as CustomEvent<{ imageUrl?: string }>;
      if (customEvent.detail?.imageUrl) {
        setUser((prev: any) => ({ ...prev, profile_image: customEvent.detail.imageUrl }));
      }
    };

    const handleNotificationRead = () => {
      setUnreadCount(prev => Math.max(0, prev - 1));
    };

    const handleNotificationsReadAll = () => {
      setUnreadCount(0);
    };

    window.addEventListener('profile-image-updated', handleProfileImageUpdated as EventListener);
    window.addEventListener('notification-read', handleNotificationRead);
    window.addEventListener('notifications-read-all', handleNotificationsReadAll);
    
    return () => {
      window.removeEventListener('profile-image-updated', handleProfileImageUpdated as EventListener);
      window.removeEventListener('notification-read', handleNotificationRead);
      window.removeEventListener('notifications-read-all', handleNotificationsReadAll);
      if (echo && currentUser?.id) {
        echo.leave(`user.${currentUser.id}`);
      }
    };
  }, []);

  const menteeLinks = [
    { href: '/mentee/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/mentee/courses', label: 'Courses', icon: BookOpen },
    { href: '/mentee/jobs', label: 'Job Market', icon: Briefcase },
    { href: '/mentee/mentors', label: 'Find Mentors', icon: Users },
    { href: '/mentee/schedule', label: 'Schedule', icon: Calendar },
    { href: '/mentee/resources', label: 'Resources', icon: BookOpen },
  ];

  const mentorLinks = [
    { href: '/mentor/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/mentor/courses', label: 'Courses', icon: BookOpen },
    { href: '/mentor/mentees', label: 'My Mentees', icon: Users },
    { href: '/mentor/schedule', label: 'Schedule', icon: Calendar },
    { href: '/mentor/earnings', label: 'Earnings', icon: DollarSign },
    { href: '/mentor/resources', label: 'Resources', icon: BookOpen },
  ];

  const links = role === 'mentee' ? menteeLinks : mentorLinks;

  const isActive = (href: string) => pathname === href || pathname?.startsWith(href);

  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await authService.logout();
      router.push('/login');
    } catch (error) {
      console.error('Logout failed:', error);
      setIsLoggingOut(false);
    }
  };

  const handleNotifications = () => {
    router.push(`/${role}/notifications`);
  };

  const handleSettings = () => {
    router.push(`/${role}/settings`);
  };

  const handleProfile = () => {
    router.push(`/${role}/profile`);
  };

  return (
    <>
      <Toaster position="top-right" />
      {/* Top Navigation Bar */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-slate-900 text-white shadow-xl border-b border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Left Section: Logo & Links */}
            <div className="flex items-center gap-8 xl:gap-12">
              {/* Logo Section */}
              <div className="flex-shrink-0 flex items-center">
                <Logo />
              </div>

              {/* Desktop Navigation Links */}
              <div className="hidden md:flex items-center space-x-1">
              {links.map((link) => {
                const Icon = link.icon;
                const active = isActive(link.href);

                return (
                  <Link
                    key={link.href}
                    href={link.href}
                    className={`
                      flex items-center space-x-2 px-4 py-2 rounded-lg transition-all duration-200
                      ${active
                        ? role === 'mentor' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30'
                        : 'text-slate-300 hover:bg-slate-800/50 hover:text-white'
                      }
                    `}
                  >
                    <Icon className={`w-4 h-4 ${active ? 'text-white' : 'text-slate-400'}`} />
                    <span className="font-medium text-sm">{link.label}</span>
                  </Link>
                );
              })}
              </div>
            </div>

            {/* Right Section - User & Actions */}
            <div className="hidden md:flex items-center space-x-2">
              {/* Notifications */}
              <button
                onClick={handleNotifications}
                className="relative p-2 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
              >
                <Bell className="w-5 h-5" />
                {unreadCount > 0 && (
                  <span className="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-slate-900 animate-pulse-glow"></span>
                )}
              </button>

              {/* Settings */}
              <button
                onClick={handleSettings}
                className="p-2 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
              >
                <Settings className="w-5 h-5" />
              </button>

              {/* User Profile */}
              <button
                onClick={handleProfile}
                className="flex items-center space-x-2 px-3 py-2 bg-slate-800/50 rounded-lg hover:bg-slate-700/50 transition-colors cursor-pointer ml-2"
              >
                <div className={`w-8 h-8 ${role === 'mentor' ? 'bg-emerald-600' : 'bg-indigo-600'} rounded-full flex items-center justify-center`}>
                  {user?.profile_image ? (
                    <img
                      src={user.profile_image}
                      alt="Profile"
                      className="w-8 h-8 rounded-full object-cover"
                    />
                  ) : (
                    <User className="w-4 h-4 text-white" />
                  )}
                </div>
                <div className="hidden lg:block">
                  <p className="text-xs font-semibold text-white">
                    {user?.name || 'User'}
                  </p>
                </div>
              </button>

              {/* Logout */}
              <button
                onClick={handleLogout}
                disabled={isLoggingOut}
                className="flex items-center space-x-2 px-3 py-2 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <LogOut className="w-4 h-4" />
                <span className="text-sm font-medium hidden lg:block">
                  {isLoggingOut ? 'Logging out...' : 'Logout'}
                </span>
              </button>
            </div>

            {/* Mobile Menu Button */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="md:hidden p-2 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-slate-700/50 bg-slate-900">
            <div className="px-4 py-3 space-y-1">
              {links.map((link) => {
                const Icon = link.icon;
                const active = isActive(link.href);

                return (
                  <Link
                    key={link.href}
                    href={link.href}
                    onClick={() => setMobileMenuOpen(false)}
                    className={`flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 ${
                      active
                        ? role === 'mentor' ? 'bg-emerald-600 text-white' : 'bg-indigo-600 text-white'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                    }`}
                  >
                    <Icon className={`w-5 h-5 mr-3 ${active ? 'text-white' : 'text-slate-400'}`} />
                    {link.label}
                  </Link>
                );
              })}

              <div className="pt-3 mt-3 border-t border-slate-700/50 space-y-1">
                <button
                  onClick={() => {
                    handleNotifications();
                    setMobileMenuOpen(false);
                  }}
                  className="w-full flex items-center space-x-3 px-4 py-3 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
                >
                  <Bell className="w-5 h-5" />
                  <span className="font-medium">Notifications</span>
                  {unreadCount > 0 && (
                    <span className="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{unreadCount}</span>
                  )}
                </button>

                <button
                  onClick={() => {
                    handleSettings();
                    setMobileMenuOpen(false);
                  }}
                  className="w-full flex items-center space-x-3 px-4 py-3 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
                >
                  <Settings className="w-5 h-5" />
                  <span className="font-medium">Settings</span>
                </button>

                <button
                  onClick={handleLogout}
                  disabled={isLoggingOut}
                  className="w-full flex items-center space-x-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg transition-colors disabled:opacity-50"
                >
                  <LogOut className="w-5 h-5" />
                  <span className="font-medium">{isLoggingOut ? 'Logging out...' : 'Log Out'}</span>
                </button>
              </div>
            </div>
          </div>
        )}
      </nav>
    </>
  );
}