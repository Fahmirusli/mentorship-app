import { GraduationCap } from 'lucide-react';
import Link from 'next/link';

export function Logo({ className = '', collapsed = false, size = 'md' }: { className?: string; collapsed?: boolean; size?: 'sm' | 'md' | 'lg' }) {
    const sizeClasses = {
        sm: 'w-6 h-6',
        md: 'w-8 h-8',
        lg: 'w-12 h-12'
    };
    const textSizeClasses = {
        sm: 'text-lg',
        md: 'text-xl',
        lg: 'text-3xl'
    };

    return (
        <Link href="/" className={`flex items-center gap-2 ${className}`}>
            <div className={`${sizeClasses[size]} bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shrink-0`}>
                <GraduationCap className={`${size === 'sm' ? 'w-4 h-4' : size === 'md' ? 'w-5 h-5' : 'w-8 h-8'} text-white`} />
            </div>
            {!collapsed && (
                <span className={`font-bold ${textSizeClasses[size]} bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent`}>
                    MentorCore
                </span>
            )}
        </Link>
    );
}
