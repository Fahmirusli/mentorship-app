'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard, Briefcase, Users, Calendar,
  BookOpen, LogOut, Settings, Bell, User, Menu, X
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { authService } from '@/lib/auth';
import { Logo } from '@/components/Logo';

export function Sidebar({ role }: { role: 'mentor' | 'mentee' }) {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    setUser(currentUser);
  }, []);

  const menteeLinks = [
    { href: '/mentee/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/mentee/jobs', label: 'Job Market', icon: Briefcase },
    { href: '/mentee/mentors', label: 'Find Mentors', icon: Users },
    { href: '/mentee/schedule', label: 'Schedule', icon: Calendar },
  ];

  const mentorLinks = [
    { href: '/mentor/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/mentor/mentees', label: 'My Mentees', icon: Users },
    { href: '/mentor/schedule', label: 'Schedule', icon: Calendar },
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
      {/* Top Navigation Bar */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-2xl border-b border-slate-700/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Logo Section */}
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
                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
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

            {/* Right Section - User & Actions */}
            <div className="hidden md:flex items-center space-x-2">
              {/* Notifications */}
              <button
                onClick={handleNotifications}
                className="relative p-2 text-slate-300 hover:bg-slate-800/50 hover:text-white rounded-lg transition-colors"
              >
                <Bell className="w-5 h-5" />
                <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
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
                <div className="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                  <User className="w-4 h-4 text-white" />
                </div>
                <div className="hidden lg:block">
                  <p className="text-xs font-semibold text-white">
                    {user?.name ? `Welcome Back, ${user.name}` : 'Welcome Back'}
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
                    className={`
                      flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200
                      ${active
                        ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg'
                        : 'text-slate-300 hover:bg-slate-800/50 hover:text-white'
                      }
                    `}
                  >
                    <Icon className={`w-5 h-5 ${active ? 'text-white' : 'text-slate-400'}`} />
                    <span className="font-medium">{link.label}</span>
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
                  <span className="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
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