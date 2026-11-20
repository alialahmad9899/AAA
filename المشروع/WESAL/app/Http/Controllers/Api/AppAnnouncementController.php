<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\AppAnnouncement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppAnnouncementController extends Controller
{
    /**
     * Return the active announcement only when the authenticated member matches
     * the audience rules configured by the administrator.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $announcement = AppAnnouncement::query()->first();

        if (!$user
            || $user->user_type !== 'member'
            || !$announcement
            || !$announcement->is_active
            || !$announcement->matchesMember($user->member)) {
            return response()->json([
                'result' => true,
                'data' => null,
            ]);
        }

        $language = strtolower((string) $request->query(
            'lang',
            $request->header('app_language', 'ar')
        ));
        $isEnglish = str_starts_with($language, 'en');

        $title = $isEnglish
            ? ($announcement->title_en ?: $announcement->title_ar)
            : ($announcement->title_ar ?: $announcement->title_en);
        $message = $isEnglish
            ? ($announcement->message_en ?: $announcement->message_ar)
            : ($announcement->message_ar ?: $announcement->message_en);
        $buttonText = $isEnglish
            ? ($announcement->button_text_en ?: $announcement->button_text_ar)
            : ($announcement->button_text_ar ?: $announcement->button_text_en);

        return response()->json([
            'result' => true,
            'data' => [
                'id' => (int) $announcement->id,
                'type' => $announcement->announcement_type,
                'title' => (string) $title,
                'message' => (string) $message,
                'button_enabled' => (bool) $announcement->button_enabled,
                'button_text' => (string) ($buttonText ?? ''),
                'button_url' => $announcement->button_enabled
                    ? (string) ($announcement->button_url ?? '')
                    : '',
            ],
        ]);
    }
}
