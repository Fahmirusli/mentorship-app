'use client';

import Link from 'next/link';
import { ArrowRight, Users, Briefcase, TrendingUp, Sparkles, Shield, Zap, Star, ChevronRight } from 'lucide-react';
import { useState, useEffect } from 'react';

function AnimatedCounter({ target, suffix = '' }: { target: number; suffix?: string }) {
  const [count, setCount] = useState(0);
  useEffect(() => {
    let start = 0;
    const increment = target / 40;
    const timer = setInterval(() => {
      start += increment;
      if (start >= target) {
        setCount(target);
        clearInterval(timer);
      } else {
        setCount(Math.floor(start));
      }
    }, 50);
    return () => clearInterval(timer);
  }, [target]);
  return <span>{count.toLocaleString()}{suffix}</span>;
}

export default function HomePage() {
  const [isVisible, setIsVisible] = useState(false);
  useEffect(() => setIsVisible(true), []);

  return (
    <div className="min-h-screen bg-[#0a0a0f] text-white overflow-hidden">
      {/* Animated background blobs */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute -top-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl animate-blob" />
        <div className="absolute top-1/3 -left-40 w-80 h-80 bg-purple-600/15 rounded-full blur-3xl animate-blob animation-delay-2000" />
        <div className="absolute bottom-0 right-1/3 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl animate-blob animation-delay-4000" />
      </div>

      {/* Header */}
      <header className="relative z-50 border-b border-white/5">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                <Sparkles className="w-4 h-4 text-white" />
              </div>
              <span className="text-xl font-bold gradient-text">MentorCore</span>
            </div>
            <nav className="hidden md:flex items-center space-x-8">
              <a href="#features" className="text-gray-400 hover:text-white transition-colors text-sm">Features</a>
              <a href="#how-it-works" className="text-gray-400 hover:text-white transition-colors text-sm">How It Works</a>
              <a href="#stats" className="text-gray-400 hover:text-white transition-colors text-sm">Stats</a>
            </nav>
            <div className="flex items-center space-x-3">
              <Link href="/login" className="text-gray-300 hover:text-white transition text-sm font-medium px-4 py-2">
                Login
              </Link>
              <Link href="/register" className="px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full hover:from-indigo-500 hover:to-purple-500 transition-all text-sm font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:scale-105 transform">
                Get Started
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="relative pt-20 pb-32 px-4 sm:px-6 lg:px-8">
        <div className="max-w-5xl mx-auto text-center">
          <div className={`transition-all duration-1000 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
            <div className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full mb-8 text-sm text-indigo-300">
              <Zap className="w-3.5 h-3.5" />
              AI-Powered Career Matching
              <ChevronRight className="w-3.5 h-3.5" />
            </div>
            <h1 className="text-5xl md:text-7xl font-bold mb-6 leading-tight tracking-tight">
              Find Your Perfect
              <br />
              <span className="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent animate-gradient-x">
                Mentor & Career
              </span>
            </h1>
            <p className="text-lg md:text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
              Connect with industry experts, get AI-matched job recommendations from
              <span className="text-indigo-400 font-medium"> LinkedIn</span>,
              <span className="text-blue-400 font-medium"> JobStreet</span> &
              <span className="text-purple-400 font-medium"> MauKerja</span>.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href="/register" className="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-500 hover:to-purple-500 transition-all font-semibold text-lg shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:scale-105 transform">
                Start Your Journey
                <ArrowRight className="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </Link>
              <Link href="/login" className="inline-flex items-center justify-center px-8 py-4 bg-white/5 text-white border border-white/10 rounded-xl hover:bg-white/10 transition-all font-semibold text-lg hover:border-white/20">
                I'm a Mentor
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="relative py-24 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              Everything You Need to <span className="gradient-text">Succeed</span>
            </h2>
            <p className="text-gray-400 text-lg max-w-xl mx-auto">Powerful features designed to accelerate your career</p>
          </div>

          <div className="grid md:grid-cols-3 gap-6 stagger-children">
            {[
              { icon: Users, title: 'Expert Mentors', desc: 'Connect with verified industry professionals who guide your career path.', color: 'from-indigo-500 to-blue-500', bg: 'bg-indigo-500/10' },
              { icon: Briefcase, title: 'Smart Job Matching', desc: 'AI scrapes LinkedIn, JobStreet & MauKerja to find your perfect fit.', color: 'from-blue-500 to-cyan-500', bg: 'bg-blue-500/10' },
              { icon: TrendingUp, title: 'Skill Gap Analysis', desc: 'Know exactly what skills to learn with NLP-powered gap detection.', color: 'from-purple-500 to-pink-500', bg: 'bg-purple-500/10' },
              { icon: Shield, title: 'Secure Payments', desc: 'Book sessions with confidence via ToyyibPay secure gateway.', color: 'from-green-500 to-emerald-500', bg: 'bg-green-500/10' },
              { icon: Zap, title: 'Real-time Chat', desc: 'Communicate with mentors instantly through built-in messaging.', color: 'from-yellow-500 to-orange-500', bg: 'bg-yellow-500/10' },
              { icon: Star, title: 'Rating & Feedback', desc: 'Rate your sessions and help others find the best mentors.', color: 'from-pink-500 to-rose-500', bg: 'bg-pink-500/10' },
            ].map((feature, i) => (
              <div key={i} className="group p-6 rounded-2xl bg-white/[0.03] border border-white/[0.06] hover:bg-white/[0.06] hover:border-indigo-500/30 transition-all duration-300 card-hover cursor-default">
                <div className={`w-12 h-12 ${feature.bg} rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform`}>
                  <feature.icon className="w-6 h-6 text-white" />
                </div>
                <h3 className="text-lg font-bold text-white mb-2">{feature.title}</h3>
                <p className="text-gray-400 text-sm leading-relaxed">{feature.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section id="how-it-works" className="relative py-24 px-4 sm:px-6 lg:px-8">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">
              How It <span className="gradient-text">Works</span>
            </h2>
            <p className="text-gray-400 text-lg">Three steps to transform your career</p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              { step: '01', title: 'Create Profile', desc: 'Sign up, add your skills, and tell us your career goals.' },
              { step: '02', title: 'Get Matched', desc: 'Our AI finds mentors & jobs that perfectly match your profile.' },
              { step: '03', title: 'Grow & Succeed', desc: 'Book sessions, learn new skills, and land your dream job.' },
            ].map((item, i) => (
              <div key={i} className="relative text-center group">
                <div className="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-2xl font-bold shadow-xl shadow-indigo-500/20 group-hover:shadow-indigo-500/40 group-hover:scale-110 transition-all">
                  {item.step}
                </div>
                <h3 className="text-xl font-bold text-white mb-3">{item.title}</h3>
                <p className="text-gray-400 text-sm leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Stats */}
      <section id="stats" className="relative py-24 px-4 sm:px-6 lg:px-8">
        <div className="max-w-5xl mx-auto">
          <div className="rounded-3xl bg-gradient-to-br from-indigo-600/20 via-purple-600/10 to-transparent border border-indigo-500/20 p-12">
            <div className="grid md:grid-cols-4 gap-8 text-center">
              {[
                { value: 500, suffix: '+', label: 'Expert Mentors' },
                { value: 1000, suffix: '+', label: 'Active Mentees' },
                { value: 5000, suffix: '+', label: 'Job Listings' },
                { value: 95, suffix: '%', label: 'Success Rate' },
              ].map((stat, i) => (
                <div key={i} className="group">
                  <div className="text-4xl md:text-5xl font-bold mb-2 gradient-text">
                    <AnimatedCounter target={stat.value} suffix={stat.suffix} />
                  </div>
                  <div className="text-gray-400 text-sm">{stat.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="relative py-24 px-4 sm:px-6 lg:px-8">
        <div className="max-w-3xl mx-auto text-center">
          <h2 className="text-3xl md:text-5xl font-bold mb-6">
            Ready to <span className="gradient-text">Transform</span> Your Career?
          </h2>
          <p className="text-xl text-gray-400 mb-10">
            Join thousands of professionals who accelerated their growth
          </p>
          <Link href="/register" className="group inline-flex items-center px-10 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl hover:from-indigo-500 hover:to-purple-500 transition-all font-bold text-lg shadow-2xl shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-105 transform">
            Get Started Free
            <ArrowRight className="ml-3 w-5 h-5 group-hover:translate-x-1 transition-transform" />
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-white/5 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="grid md:grid-cols-4 gap-8 mb-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <div className="w-7 h-7 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                  <Sparkles className="w-3.5 h-3.5 text-white" />
                </div>
                <span className="font-bold text-white">MentorCore</span>
              </div>
              <p className="text-gray-500 text-sm">AI-powered mentorship & career platform for the modern professional.</p>
            </div>
            <div>
              <h4 className="text-white font-semibold mb-4 text-sm">Product</h4>
              <ul className="space-y-2 text-sm text-gray-500">
                <li><a href="#features" className="hover:text-gray-300 transition">Features</a></li>
                <li><a href="#how-it-works" className="hover:text-gray-300 transition">How it Works</a></li>
                <li><a href="#" className="hover:text-gray-300 transition">Pricing</a></li>
              </ul>
            </div>
            <div>
              <h4 className="text-white font-semibold mb-4 text-sm">Company</h4>
              <ul className="space-y-2 text-sm text-gray-500">
                <li><a href="#" className="hover:text-gray-300 transition">About</a></li>
                <li><a href="#" className="hover:text-gray-300 transition">Blog</a></li>
                <li><a href="#" className="hover:text-gray-300 transition">Contact</a></li>
              </ul>
            </div>
            <div>
              <h4 className="text-white font-semibold mb-4 text-sm">Legal</h4>
              <ul className="space-y-2 text-sm text-gray-500">
                <li><a href="#" className="hover:text-gray-300 transition">Privacy</a></li>
                <li><a href="#" className="hover:text-gray-300 transition">Terms</a></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-white/5 pt-8 text-center text-sm text-gray-600">
            © 2026 MentorCore. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
}