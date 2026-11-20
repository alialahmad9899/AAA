<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppAnnouncementController extends Controller
{
    private function authorizeAppAdmin(): void
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);
    }

    public function edit(): View
    {
        $this->authorizeAppAdmin();

        $announcement = AppAnnouncement::query()->first() ?? new AppAnnouncement();

        return view('admin.app_announcements.index', compact('announcement'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAppAdmin();

        $validated = $request->validate([
            'announcement_type' => ['required', 'in:note,app_update'],
            'title_ar' => ['required', 'string', 'max:150'],
            'message_ar' => ['required', 'string', 'max:10000'],
            'title_en' => ['nullable', 'string', 'max:150'],
            'message_en' => ['nullable', 'string', 'max:10000'],
            'target_type' => ['required', 'in:all,male,female,age'],
            'target_gender' => ['nullable', 'required_if:target_type,age', 'in:all,male,female'],
            'age_min' => ['nullable', 'required_if:target_type,age', 'integer', 'min:18', 'max:100'],
            'age_max' => ['nullable', 'required_if:target_type,age', 'integer', 'min:18', 'max:100'],
            'button_enabled' => ['nullable', 'boolean'],
            'button_text_ar' => ['nullable', 'required_if:button_enabled,1', 'string', 'max:80'],
            'button_text_en' => ['nullable', 'string', 'max:80'],
            'button_url' => [
                'nullable',
                'required_if:button_enabled,1',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                    if (!in_array($scheme, ['http', 'https'], true)) {
                        $fail('يجب أن يبدأ رابط الزر بـ http:// أو https://.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['target_type'] === 'age'
            && (int) $validated['age_min'] > (int) $validated['age_max']) {
            return back()
                ->withErrors(['age_max' => 'يجب أن يكون الحد الأعلى للعمر أكبر من أو مساوياً للحد الأدنى.'])
                ->withInput();
        }

        $buttonEnabled = $request->boolean('button_enabled');
        $announcement = AppAnnouncement::query()->first() ?? new AppAnnouncement();

        $announcement->fill([
            'announcement_type' => $validated['announcement_type'],
            'title_ar' => trim($validated['title_ar']),
            'message_ar' => trim($validated['message_ar']),
            'title_en' => trim((string) ($validated['title_en'] ?? '')) ?: null,
            'message_en' => trim((string) ($validated['message_en'] ?? '')) ?: null,
            'target_type' => $validated['target_type'],
            // The age-based audience can target both genders or one selected gender.
            'target_gender' => $validated['target_type'] === 'age'
                ? $validated['target_gender']
                : 'all',
            'age_min' => $validated['target_type'] === 'age' ? (int) $validated['age_min'] : null,
            'age_max' => $validated['target_type'] === 'age' ? (int) $validated['age_max'] : null,
            'button_enabled' => $buttonEnabled,
            'button_text_ar' => $buttonEnabled ? trim((string) ($validated['button_text_ar'] ?? '')) : null,
            'button_text_en' => $buttonEnabled ? (trim((string) ($validated['button_text_en'] ?? '')) ?: null) : null,
            'button_url' => $buttonEnabled ? trim((string) ($validated['button_url'] ?? '')) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $announcement->save();

        return back()->with('success', 'تم حفظ نافذة التطبيق بنجاح.');
    }
}
