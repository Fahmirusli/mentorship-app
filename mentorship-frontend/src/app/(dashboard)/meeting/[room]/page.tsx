'use client';

import { useParams, useRouter } from 'next/navigation';
import { JitsiMeeting } from '@jitsi/react-sdk';
import { useState, useEffect } from 'react';
import { authService } from '@/lib/auth';
import { Loader, ArrowLeft } from 'lucide-react';

export default function MeetingPage() {
    const params = useParams();
    const router = useRouter();
    const [user, setUser] = useState<any>(null);
    const roomName = params.room as string;

    useEffect(() => {
        setUser(authService.getCurrentUser());
    }, []);

    if (!user) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-900">
                <Loader className="w-8 h-8 animate-spin text-indigo-500" />
            </div>
        );
    }

    return (
        <div className="h-screen w-full bg-gray-900 flex flex-col">
            <div className="p-4 bg-gray-900 border-b border-gray-800 flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <button 
                        onClick={() => router.back()}
                        className="p-2 text-gray-400 hover:bg-gray-800 hover:text-white rounded-lg transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </button>
                    <div>
                        <h1 className="text-white font-semibold">Mentorship Session</h1>
                        <p className="text-sm text-gray-400">Room: {roomName}</p>
                    </div>
                </div>
            </div>

            <div className="flex-1">
                <JitsiMeeting
                    domain="meet.jit.si"
                    roomName={roomName}
                    configOverwrite={{
                        startWithAudioMuted: true,
                        startWithVideoMuted: true,
                        disableModeratorIndicator: true,
                        prejoinPageEnabled: false,
                        prejoinConfig: { enabled: false },
                        requireDisplayName: false,
                        enableEmailInStats: false
                    }}
                    interfaceConfigOverwrite={{
                        DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                        SHOW_PROMOTIONAL_CLOSE_PAGE: false
                    }}
                    userInfo={{
                        displayName: user.name,
                        email: user.email
                    }}
                    onApiReady={(externalApi) => {
                        externalApi.addListener('readyToClose', () => {
                            router.push(`/${user.role || (window.location.pathname.includes('mentor') ? 'mentor' : 'mentee')}/dashboard`);
                        });
                        externalApi.addListener('videoConferenceLeft', () => {
                            router.push(`/${user.role || (window.location.pathname.includes('mentor') ? 'mentor' : 'mentee')}/dashboard`);
                        });
                    }}
                    getIFrameRef={(iframeRef) => {
                        iframeRef.style.height = '100%';
                        iframeRef.style.width = '100%';
                    }}
                />
            </div>
        </div>
    );
}
