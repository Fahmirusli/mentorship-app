<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        // Get resources for the authenticated mentor
        if ($request->user()->isMentor()) {
            $resources = Resource::where('mentor_id', $request->user()->id)
                ->latest()
                ->get();
            return response()->json($resources);
        }
        
        // Or get resources for a specific mentor (for mentees)
        if ($request->has('mentor_id')) {
            $resources = Resource::where('mentor_id', $request->mentor_id)
                ->latest()
                ->get();
            return response()->json($resources);
        }

        return response()->json(['message' => 'Mentor ID required'], 400);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isMentor()) {
            return response()->json(['message' => 'Only mentors can add resources'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:link,file,video',
            'visibility' => 'required|in:public,private,mentees_only',
            'url' => 'required_without:file|string',
            'file' => 'required_without:url|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,mp4|max:204800',
        ]);

        $url = $validated['url'] ?? '';
        $type = $validated['type'];
        $visibility = $validated['visibility'] ?? 'public';

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('resources', 'public');
            $url = asset('storage/' . $path);
            $type = 'file';
        }

        $resource = Resource::create([
            'mentor_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'url' => $url,
            'type' => $type,
            'visibility' => $visibility,
        ]);

        return response()->json($resource, 201);
    }

    public function destroy(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);

        if ($resource->mentor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($resource->type === 'file' && $resource->url) {
            $appUrl = rtrim(config('app.url'), '/');
            $path = str_replace($appUrl . '/storage/', '', $resource->url);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $resource->delete();

        return response()->json(['message' => 'Resource deleted successfully']);
    }
}
