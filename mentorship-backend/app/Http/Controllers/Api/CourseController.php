<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\Badge;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Mentor: Get courses they created
     * Mentee: Get courses that match their interests/skills
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isMentor()) {
            $courses = Course::where('mentor_id', $user->id)->get();
            return response()->json(['courses' => $courses]);
        }

        // For Mentee, get all courses for now. We can add filtering logic based on tags later.
        $courses = Course::with('mentor:id,name,avatar')->get();
        return response()->json(['courses' => $courses]);
    }

    /**
     * Mentor: Create a new course
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'tags' => 'nullable|array',
            'syllabus' => 'required|array|min:1',
        ]);

        $course = Course::create([
            'mentor_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'tags' => $validated['tags'] ?? [],
            'syllabus' => $validated['syllabus'] ?? [],
        ]);

        return response()->json(['message' => 'Course created successfully', 'course' => $course], 201);
    }

    /**
     * Get a specific course
     */
    public function show($id)
    {
        $course = Course::with('mentor:id,name,avatar,bio')->findOrFail($id);
        return response()->json(['course' => $course]);
    }

    /**
     * Mentor: Update an existing course
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        if ($course->mentor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'tags' => 'nullable|array',
            'syllabus' => 'sometimes|required|array|min:1',
        ]);

        $course->update($validated);

        // Recalculate progress for all enrollments if syllabus changed
        if (isset($validated['syllabus'])) {
            $totalTasks = count($validated['syllabus']);
            $enrollments = \App\Models\CourseEnrollment::where('course_id', $course->id)->get();
            foreach ($enrollments as $enrollment) {
                $completedTasks = $enrollment->completed_tasks ?? [];
                
                // Filter out tasks that no longer exist
                $validCompletedTasks = array_filter($completedTasks, function($index) use ($totalTasks) {
                    return $index < $totalTasks;
                });
                $validCompletedTasks = array_values($validCompletedTasks);
                
                $progressPercent = $totalTasks > 0 ? round((count($validCompletedTasks) / $totalTasks) * 100) : 0;
                
                $enrollment->update([
                    'completed_tasks' => $validCompletedTasks,
                    'progress_percent' => $progressPercent
                ]);
            }
        }

        return response()->json(['message' => 'Course updated successfully', 'course' => $course]);
    }

    /**
     * Mentor: Delete a course
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        if ($course->mentor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    /**
     * Mentee: Enroll in a course
     */
    public function enroll(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isMentee()) {
            return response()->json(['message' => 'Only mentees can enroll in courses'], 403);
        }

        $course = Course::findOrFail($id);

        // Check if already enrolled
        $existing = CourseEnrollment::where('mentee_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You are already enrolled in this course'], 400);
        }

        // Simulate payment success and enroll
        $enrollment = CourseEnrollment::create([
            'mentee_id' => $user->id,
            'course_id' => $course->id,
            'progress_percent' => 0,
            'completed_tasks' => [],
            'status' => 'active',
        ]);

        return response()->json(['message' => 'Enrolled successfully', 'enrollment' => $enrollment], 201);
    }

    /**
     * Mentee: Get my enrolled courses
     */
    public function myCourses(Request $request)
    {
        $user = $request->user();
        
        $enrollments = CourseEnrollment::with(['course.mentor', 'submissions'])
            ->where('mentee_id', $user->id)
            ->get();

        return response()->json(['enrollments' => $enrollments]);
    }

    /**
     * Mentee: Submit work for a task
     */
    public function submitTask(Request $request, $enrollmentId)
    {
        $user = $request->user();
        $enrollment = CourseEnrollment::with('course')->findOrFail($enrollmentId);

        if ($enrollment->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'task_index' => 'required|integer|min:0',
            'file' => 'nullable|file|max:51200',
            'link' => 'nullable|url',
            'notes' => 'nullable|string'
        ]);

        $taskIndex = $validated['task_index'];
        $totalTasks = count($enrollment->course->syllabus ?? []);

        if ($taskIndex >= $totalTasks) {
            return response()->json(['message' => 'Invalid task index'], 400);
        }

        $fileUrl = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('course_submissions', 'public');
            $fileUrl = asset('storage/' . $path);
        }

        $submission = \App\Models\CourseSubmission::updateOrCreate(
            [
                'course_enrollment_id' => $enrollment->id,
                'task_index' => $taskIndex,
            ],
            [
                'link' => $validated['link'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending'
            ]
        );

        if ($fileUrl) {
            $submission->file_url = $fileUrl;
            $submission->save();
        }

        return response()->json(['message' => 'Task submitted successfully', 'submission' => $submission]);
    }

    /**
     * Mentor: View pending submissions for their courses
     */
    public function mentorSubmissions(Request $request)
    {
        $user = $request->user();
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $courseIds = Course::where('mentor_id', $user->id)->pluck('id');

        $submissions = \App\Models\CourseSubmission::with(['enrollment.course', 'enrollment.mentee'])
            ->whereHas('enrollment', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['submissions' => $submissions]);
    }

    /**
     * Mentor: Review (Approve/Reject) a submission
     */
    public function reviewSubmission(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submission = \App\Models\CourseSubmission::with(['enrollment.course'])->findOrFail($id);

        if ($submission->enrollment->course->mentor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'mentor_feedback' => 'nullable|string'
        ]);

        $submission->update([
            'status' => $validated['status'],
            'mentor_feedback' => $validated['mentor_feedback']
        ]);

        if ($validated['status'] === 'approved') {
            $enrollment = $submission->enrollment;
            $completedTasks = $enrollment->completed_tasks ?? [];
            
            if (!in_array($submission->task_index, $completedTasks)) {
                $completedTasks[] = $submission->task_index;
                
                $totalTasks = count($enrollment->course->syllabus ?? []);
                $progressPercent = $totalTasks > 0 ? round((count($completedTasks) / $totalTasks) * 100) : 0;
                $status = $progressPercent >= 100 ? 'completed' : 'active';
                $enrollment->update([
                    'completed_tasks' => $completedTasks,
                    'progress_percent' => $progressPercent,
                    'status' => $status
                ]);

                // Gamification for completing courses
                if ($status === 'completed') {
                    $mentee = $enrollment->mentee;
                    // Count how many courses this mentee has completed
                    $completedCoursesCount = \App\Models\CourseEnrollment::where('mentee_id', $mentee->id)
                        ->where('status', 'completed')
                        ->count();

                    // Rule 1: First course completed
                    if ($completedCoursesCount === 1) {
                        $badgeName = "Course Pioneer";
                        $badgeDesc = "Completed your very first course!";
                        $this->awardCourseBadge($mentee, $badgeName, $badgeDesc);
                    }
                    // Rule 2: Every 5th course completed
                    elseif ($completedCoursesCount > 0 && $completedCoursesCount % 5 === 0) {
                        $badgeName = "Dedicated Learner ({$completedCoursesCount})";
                        $badgeDesc = "Completed {$completedCoursesCount} courses!";
                        $this->awardCourseBadge($mentee, $badgeName, $badgeDesc);
                    }
                }
            }
        }

        return response()->json(['message' => 'Submission reviewed successfully']);
    }

    private function awardCourseBadge($user, $name, $description)
    {
        $badge = Badge::firstOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'icon_url' => 'https://api.dicebear.com/7.x/shapes/svg?seed=' . urlencode($name),
                'required_points' => 0,
            ]
        );

        if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
            $user->badges()->attach($badge->id);
            // Optionally, we can also give them some points
            $user->points += 50;
            $user->save();
        }
    }

    /**
     * Mentor: Upload material for a course
     */
    public function uploadMaterial(Request $request)
    {
        $user = $request->user();
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:153600' // 150MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('course_materials', 'public');
        $url = asset('storage/' . $path);

        return response()->json([
            'message' => 'File uploaded successfully',
            'url' => $url
        ]);
    }
}
