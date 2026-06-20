import React from 'react';
import { Award, Star, Trophy, Target } from 'lucide-react';

interface Badge {
    id: number;
    name: string;
    description: string;
    icon_url: string;
    required_points: number;
    is_earned: boolean;
    awarded_at?: string;
}

interface GamificationData {
    points: number;
    earned_badges: Badge[];
    all_badges: Badge[];
}

export function GamificationCard({ data }: { data: GamificationData }) {
    if (!data) return null;

    const level = Math.floor(data.points / 100) + 1;
    const pointsToNext = (level * 100) - data.points;
    const progress = ((data.points % 100) / 100) * 100;

    return (
        <div className="bg-gradient-to-br from-indigo-900 to-purple-900 rounded-2xl shadow-xl overflow-hidden text-white mb-8 relative">
            <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-fuchsia-500/10 rounded-full blur-3xl -ml-20 -mb-20"></div>
            
            <div className="p-8 relative z-10">
                <div className="flex flex-col md:flex-row items-center justify-between gap-8 mb-10">
                    <div className="flex items-center gap-6">
                        <div className="w-20 h-20 rounded-full bg-gradient-to-tr from-amber-300 to-orange-500 p-1 shadow-lg shadow-orange-500/30">
                            <div className="w-full h-full rounded-full bg-indigo-950 flex items-center justify-center border-2 border-indigo-900">
                                <span className="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-amber-200 to-orange-400">
                                    {level}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h2 className="text-2xl font-bold text-white mb-1">Level {level} Achiever</h2>
                            <p className="text-indigo-200 font-medium">{data.points} Total Points</p>
                        </div>
                    </div>
                    
                    <div className="flex-1 w-full max-w-sm">
                        <div className="flex justify-between text-sm mb-2 text-indigo-200 font-medium">
                            <span>Level {level}</span>
                            <span>{pointsToNext} pts to Level {level + 1}</span>
                        </div>
                        <div className="h-3 w-full bg-indigo-950/50 rounded-full overflow-hidden border border-indigo-500/30">
                            <div 
                                className="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full relative"
                                style={{ width: `${progress}%` }}
                            >
                                <div className="absolute inset-0 bg-white/20 w-full h-full animate-[shimmer_2s_infinite]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-8">
                    <h3 className="text-lg font-bold mb-6 flex items-center gap-2">
                        <Trophy className="w-5 h-5 text-amber-400" />
                        Your Badges
                    </h3>
                    
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        {data.all_badges.map((badge) => (
                            <div 
                                key={badge.id}
                                className={`relative p-4 rounded-xl border transition-all duration-300 flex flex-col items-center text-center ${
                                    badge.is_earned 
                                        ? 'bg-white/10 border-white/20 hover:bg-white/15 hover:-translate-y-1' 
                                        : 'bg-black/20 border-white/5 opacity-50 grayscale'
                                }`}
                            >
                                {badge.is_earned && (
                                    <div className="absolute -top-2 -right-2 w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center shadow-lg shadow-amber-500/50">
                                        <Star className="w-3 h-3 text-white fill-white" />
                                    </div>
                                )}
                                
                                <div className="w-16 h-16 mb-3 rounded-full bg-indigo-950/50 flex items-center justify-center">
                                    <img 
                                        src={badge.icon_url || `https://api.dicebear.com/7.x/icons/svg?seed=${badge.name}`} 
                                        alt={badge.name}
                                        className="w-10 h-10"
                                    />
                                </div>
                                
                                <h4 className="font-bold text-sm mb-1 text-white">{badge.name}</h4>
                                <p className="text-[10px] text-indigo-200 line-clamp-2">{badge.description}</p>
                                
                                {!badge.is_earned && (
                                    <div className="mt-2 text-[10px] font-bold text-amber-400/70 bg-amber-400/10 px-2 py-0.5 rounded-full">
                                        Requires {badge.required_points} pts
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
            
            <style jsx>{`
                @keyframes shimmer {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }
            `}</style>
        </div>
    );
}
