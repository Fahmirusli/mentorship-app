'use client';

import { useState } from 'react';
import { CreditCard, Loader, CheckCircle, XCircle } from 'lucide-react';
import { api } from '@/lib/api';

interface PaymentButtonProps {
    amount: number;
    description: string;
    appointmentId?: number;
    onSuccess?: () => void;
    onError?: () => void;
}

export function PaymentButton({ amount, description, appointmentId, onSuccess, onError }: PaymentButtonProps) {
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState<'idle' | 'processing' | 'success' | 'error'>('idle');

    const handlePayment = async () => {
        setLoading(true);
        setStatus('processing');

        try {
            const data = await api.post('/payment/initiate', {
                amount,
                description,
                appointment_id: appointmentId,
            });

            if (data.payment_url) {
                // Redirect to ToyyibPay payment page
                window.location.href = data.payment_url;
            } else {
                throw new Error('Payment URL not received');
            }
        } catch (error) {
            console.error('Payment failed:', error);
            setStatus('error');
            if (onError) onError();

            setTimeout(() => {
                setStatus('idle');
                setLoading(false);
            }, 3000);
        }
    };

    if (status === 'success') {
        return (
            <div className="flex items-center gap-2 px-6 py-3 bg-green-100 text-green-700 rounded-lg">
                <CheckCircle className="w-5 h-5" />
                <span>Payment Successful!</span>
            </div>
        );
    }

    if (status === 'error') {
        return (
            <div className="flex items-center gap-2 px-6 py-3 bg-red-100 text-red-700 rounded-lg">
                <XCircle className="w-5 h-5" />
                <span>Payment Failed</span>
            </div>
        );
    }

    return (
        <button
            onClick={handlePayment}
            disabled={loading}
            className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition"
        >
            {loading ? (
                <>
                    <Loader className="w-5 h-5 animate-spin" />
                    Processing...
                </>
            ) : (
                <>
                    <CreditCard className="w-5 h-5" />
                    Pay RM{amount.toFixed(2)}
                </>
            )}
        </button>
    );
}
