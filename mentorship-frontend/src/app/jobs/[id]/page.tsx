'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import {
    ArrowLeft, MapPin, Building, Briefcase, DollarSign,
    ExternalLink, Calendar, CheckCircle, AlertCircle
} from 'lucide-react';
import { api } from '@/lib/api';

export default function JobDetailsPage() {
    const { id } = useParams();
    const router = useRouter();
    const [job, setJob] = useState<any>(null);
    const [analysis, setAnalysis] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        if (id) {
            fetchJobDetails();
        }
    }, [id]);

    const fetchJobDetails = async () => {
        try {
            const response = await api.get(`/jobs/${id}`);
            setJob(response.job);
            setAnalysis(response.match_analysis);
        } catch (err) {
            console.error('Error fetching job details:', err);
            setError('Failed to load job details. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const getMatchColor = (score: number) => {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-yellow-600';
        return 'text-red-600';
    };

    if (loading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>
        );
    }

    if (error || !job) {
        return (
            <div className="min-h-screen bg-gray-50 p-8">
                <div className="max-w-3xl mx-auto text-center">
                    <h1 className="text-2xl font-bold text-gray-900 mb-4">Error Loading Job</h1>
                    <p className="text-gray-600 mb-6">{error || 'Job not found'}</p>
                    <button
                        onClick={() => router.back()}
                        className="text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        ← Back to Jobs
                    </button>
                </div>
            </div>
        );
    }

    const requirements = Array.isArray(job.requirements)
        ? job.requirements
        : (typeof job.requirements === 'string' ? JSON.parse(job.requirements || '[]') : []);

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <button
                    onClick={() => router.back()}
                    className="flex items-center text-gray-600 hover:text-gray-900 mb-6 transition"
                >
                    <ArrowLeft className="w-5 h-5 mr-2" />
                    Back to Listings
                </button>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white rounded-xl shadow-sm p-8">
                            <div className="flex justify-between items-start mb-6">
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 mb-2">{job.title}</h1>
                                    <div className="flex items-center text-gray-600 space-x-4">
                                        <span className="flex items-center"><Building className="w-4 h-4 mr-1" /> {job.company}</span>
                                        <span className="flex items-center"><MapPin className="w-4 h-4 mr-1" /> {job.location}</span>
                                    </div>
                                </div>
                                {analysis && analysis.match_score !== undefined && (
                                    <div className="text-center bg-indigo-50 px-4 py-3 rounded-lg border border-indigo-100">
                                        <div className="text-xs text-indigo-600 font-semibold uppercase tracking-wide">Match Score</div>
                                        <div className={`text-3xl font-bold ${getMatchColor(analysis.match_score)}`}>
                                            {Math.round(analysis.match_score)}%
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-4 mb-8">
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-sm text-gray-500 mb-1">Source</div>
                                    <div className="font-semibold text-gray-900 flex items-center">
                                        <ExternalLink className="w-4 h-4 mr-2 text-indigo-500" />
                                        {job.source}
                                    </div>
                                </div>
                                <div className="p-4 bg-gray-50 rounded-lg">
                                    <div className="text-sm text-gray-500 mb-1">Posted Date</div>
                                    <div className="font-semibold text-gray-900 flex items-center">
                                        <Calendar className="w-4 h-4 mr-2 text-indigo-500" />
                                        {new Date(job.posted_date || job.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                                {job.salary && (
                                    <div className="col-span-2 p-4 bg-green-50 rounded-lg border border-green-100">
                                        <div className="text-sm text-green-600 mb-1">Salary Range</div>
                                        <div className="text-xl font-bold text-green-800 flex items-center">
                                            <DollarSign className="w-5 h-5 mr-1" />
                                            {job.salary}
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="prose max-w-none text-gray-700">
                                <h3 className="text-xl font-semibold text-gray-900 mb-4">Job Description</h3>
                                <div className="whitespace-pre-line leading-relaxed">
                                    {job.description}
                                </div>
                            </div>
                        </div>

                        {/* Application Section */}
                        <div className="bg-white rounded-xl shadow-sm p-8 text-center">
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Interested in this role?</h3>
                            <p className="text-gray-600 mb-6">Apply directly on the company website.</p>
                            <a
                                href={job.external_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition"
                            >
                                Apply Now <ExternalLink className="w-5 h-5 ml-2" />
                            </a>
                        </div>
                    </div>

                    {/* Sidebar Analysis */}
                    <div className="space-y-6">
                        {analysis ? (
                            <div className="bg-white rounded-xl shadow-sm p-6 sticky top-8">
                                <h3 className="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <Briefcase className="w-5 h-5 mr-2 text-indigo-600" />
                                    Skill Analysis
                                </h3>

                                <div className="space-y-6">
                                    <div>
                                        <h4 className="text-sm font-semibold text-green-700 mb-2 flex items-center">
                                            <CheckCircle className="w-4 h-4 mr-1" /> Matches
                                        </h4>
                                        <div className="flex flex-wrap gap-2">
                                            {analysis.matching_skills && analysis.matching_skills.length > 0 ? (
                                                analysis.matching_skills.map((skill: string) => (
                                                    <span key={skill} className="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                                                        {skill}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="text-sm text-gray-500 italic">No exact skill matches</span>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-semibold text-red-700 mb-2 flex items-center">
                                            <AlertCircle className="w-4 h-4 mr-1" /> Missing Skills
                                        </h4>
                                        <div className="flex flex-wrap gap-2">
                                            {analysis.missing_skills && analysis.missing_skills.length > 0 ? (
                                                analysis.missing_skills.map((skill: string) => (
                                                    <span key={skill} className="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">
                                                        {skill}
                                                    </span>
                                                ))
                                            ) : analysis.total_requirements === 0 ? (
                                                <span className="text-sm text-gray-500 italic">This job has no skill requirements listed</span>
                                            ) : (
                                                <span className="text-sm text-gray-500 italic">No missing skills! You're a great fit.</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white rounded-xl shadow-sm p-6">
                                <p className="text-gray-500 text-center">Log in as a Mentee to see your match analysis.</p>
                            </div>
                        )}

                        <div className="bg-indigo-900 rounded-xl shadow-sm p-6 text-white">
                            <h3 className="font-bold text-lg mb-2">Need to upskill?</h3>
                            <p className="text-indigo-200 text-sm mb-4">Find a mentor who can help you learn the missing skills for this job.</p>
                            <button
                                onClick={() => router.push('/mentee/mentors')}
                                className="w-full py-2 bg-white text-indigo-900 rounded-lg font-semibold hover:bg-gray-100 transition"
                            >
                                Find a Mentor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
