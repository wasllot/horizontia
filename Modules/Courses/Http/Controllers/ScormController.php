<?php

namespace Modules\Courses\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Courses\Models\ScormProgress;

class ScormController extends Controller
{
    /**
     * Handle SCORM 1.2 LMSCommit/LMSSetValue calls via AJAX.
     */
    public function saveProgress(Request $request, $curriculumId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $cmi = $request->input('cmi', []);

        // Find or create progress record
        $progress = ScormProgress::firstOrNew([
            'user_id' => $user->id,
            'curriculum_id' => $curriculumId
        ]);

        // Map SCORM 1.2 standard CMI elements to our database
        if (isset($cmi['core']['lesson_status'])) {
            $progress->lesson_status = $cmi['core']['lesson_status'];
        }

        if (isset($cmi['core']['score']['raw'])) {
            $progress->score_raw = $cmi['core']['score']['raw'];
        }

        if (isset($cmi['core']['session_time'])) {
            $progress->session_time = $cmi['core']['session_time'];
        }

        if (isset($cmi['suspend_data'])) {
            $progress->suspend_data = $cmi['suspend_data'];
        }

        $progress->save();

        return response()->json(['success' => true]);
    }

    /**
     * Handle SCORM 1.2 LMSInitialize call via AJAX to get existing progress.
     */
    public function getProgress($curriculumId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $progress = ScormProgress::where('user_id', $user->id)
            ->where('curriculum_id', $curriculumId)
            ->first();

        if ($progress) {
            return response()->json([
                'success' => true,
                'cmi' => [
                    'core' => [
                        'lesson_status' => $progress->lesson_status,
                        'score' => [
                            'raw' => $progress->score_raw
                        ]
                    ],
                    'suspend_data' => $progress->suspend_data
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No progress found']);
    }
}
