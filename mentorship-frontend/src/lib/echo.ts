import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { api } from './api';

// Export Pusher so we can use it directly if needed
if (typeof window !== 'undefined') {
  (window as any).Pusher = Pusher;
}

export const getEcho = () => {
  if (typeof window === 'undefined') return null;
  
  const token = localStorage.getItem('token');
  if (!token) return null;

  return new Echo({
    broadcaster: 'pusher',
    key: process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'app-key',
    wsHost: process.env.NEXT_PUBLIC_PUSHER_HOST || '127.0.0.1',
    wsPort: Number(process.env.NEXT_PUBLIC_PUSHER_PORT || 6001),
    wssPort: Number(process.env.NEXT_PUBLIC_PUSHER_PORT || 6001),
    forceTLS: process.env.NEXT_PUBLIC_PUSHER_SCHEME === 'https',
    disableStats: true,
    cluster: 'mt1',
    authorizer: (channel: any, options: any) => {
      return {
        authorize: (socketId: string, callback: any) => {
          api.post('/broadcasting/auth', {
            socket_id: socketId,
            channel_name: channel.name
          })
          .then(response => {
            callback(false, response);
          })
          .catch(error => {
            callback(true, error);
          });
        }
      };
    },
  });
};
