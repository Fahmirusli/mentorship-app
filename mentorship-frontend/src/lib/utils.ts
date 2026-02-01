import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

export function formatDateTime(date: string): string {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function calculateMatchPercentage(
  userSkills: string[],
  requiredSkills: string[]
): number {
  if (!requiredSkills.length) return 0;

  const matchingSkills = userSkills.filter((skill) =>
    requiredSkills.some((req) => req.toLowerCase() === skill.toLowerCase())
  );

  return Math.round((matchingSkills.length / requiredSkills.length) * 100);
}

export function getMatchingSkills(
  userSkills: string[],
  requiredSkills: string[]
): string[] {
  return userSkills.filter((skill) =>
    requiredSkills.some((req) => req.toLowerCase() === skill.toLowerCase())
  );
}

export function getMissingSkills(
  userSkills: string[],
  requiredSkills: string[]
): string[] {
  return requiredSkills.filter(
    (req) => !userSkills.some((skill) => skill.toLowerCase() === req.toLowerCase())
  );
}