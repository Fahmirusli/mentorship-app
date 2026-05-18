import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  variable: "--font-geist-sans",
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "MentorCore — Mentorship & Career Platform",
  description: "Connect with expert mentors, find your dream job, and accelerate your career growth with AI-powered skill matching from LinkedIn, JobStreet, and MauKerja.",
  keywords: "mentorship, career, jobs, mentor, mentee, skill matching, JobStreet, LinkedIn, MauKerja",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={`${inter.variable} antialiased font-sans`}>
        {children}
      </body>
    </html>
  );
}
