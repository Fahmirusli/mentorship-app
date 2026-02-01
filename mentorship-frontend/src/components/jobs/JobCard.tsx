import React from 'react';
import { JobRecommendation } from '@/types';

interface JobCardProps {
  job: JobRecommendation;
}

export default function JobCard({ job }: JobCardProps) {
  // Dynamic styling based on NLP match score [cite: 1977]
  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-700 bg-green-100 border-green-200';
    if (score >= 50) return 'text-yellow-700 bg-yellow-100 border-yellow-200';
    return 'text-red-700 bg-red-100 border-red-200';
  };

  return (
    <div className="bg-white p-5 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
      <div className="flex justify-between items-start mb-2">
        <div>
          <h3 className="font-bold text-lg text-gray-900">{job.title}</h3>
          <p className="text-gray-600 font-medium">{job.company}</p>
        </div>
        <div className={`px-3 py-1 rounded-full text-xs font-bold border ${getScoreColor(job.match_score)}`}>
          {job.match_score}% Match
        </div>
      </div>
      
      <div className="mb-4">
        <span className="text-xs font-semibold text-gray-400 bg-gray-100 px-2 py-1 rounded">
          Source: {job.source}
        </span>
      </div>

      {/* Skill Gap Analysis Display [cite: 2060] */}
      <div className="mb-4">
        <p className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Skill Gap Analysis</p>
        <div className="flex flex-wrap gap-2">
          {job.missing_skills.length > 0 ? (
            job.missing_skills.map((skill, index) => (
              <span key={index} className="px-2 py-1 bg-red-50 text-red-600 text-xs rounded border border-red-100 font-medium">
                Missing: {skill}
              </span>
            ))
          ) : (
            <span className="px-2 py-1 bg-green-50 text-green-600 text-xs rounded border border-green-100 font-medium">
              Perfect Match!
            </span>
          )}
        </div>
      </div>

      <a 
        href={job.url} 
        target="_blank"
        className="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium"
      >
        View Application
      </a>
    </div>
  );
}