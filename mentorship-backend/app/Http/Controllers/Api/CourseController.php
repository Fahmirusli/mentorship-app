<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
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
            'syllabus' => 'nullable|array',
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
            'syllabus' => 'nullable|array',
        ]);

        $course->update($validated);

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
        
        $enrollments = CourseEnrollment::with(['course.mentor'])
            ->where('mentee_id', $user->id)
            ->get();

        return response()->json(['enrollments' => $enrollments]);
    }

    /**
     * Mentee: Update progress (check off a task)
     */
    public function updateProgress(Request $request, $enrollmentId)
    {
        $user = $request->user();
        $enrollment = CourseEnrollment::with('course')->findOrFail($enrollmentId);

        if ($enrollment->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'task_index' => 'required|integer|min:0',
            'completed' => 'required|boolean',
        ]);

        $taskIndex = $validated['task_index'];
        $completed = $validated['completed'];

        $completedTasks = $enrollment->completed_tasks ?? [];
        $syllabus = $enrollment->course->syllabus ?? [];
        $totalTasks = count($syllabus);

        if ($taskIndex >= $totalTasks) {
            return response()->json(['message' => 'Invalid task index'], 400);
        }

        if ($completed) {
            if (!in_array($taskIndex, $completedTasks)) {
                $completedTasks[] = $taskIndex;
            }
        } else {
            $completedTasks = array_values(array_diff($completedTasks, [$taskIndex]));
        }

        $progressPercent = $totalTasks > 0 ? round((count($completedTasks) / $totalTasks) * 100) : 0;
        $status = $progressPercent >= 100 ? 'completed' : 'active';

        $enrollment->update([
            'completed_tasks' => $completedTasks,
            'progress_percent' => $progressPercent,
            'status' => $status,
        ]);

        return response()->json(['message' => 'Progress updated', 'enrollment' => $enrollment]);
    }
}
