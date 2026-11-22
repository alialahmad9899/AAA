<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Utility\SubscriptionUtility;
use App\Models\Career;
use App\Models\Member;
use App\Models\Address;
use App\Models\IgnoredUser;
use App\Models\ReportedUser;
use App\Models\ChatThread;
use App\Models\Chat;
use Illuminate\Http\Request;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Services\SmartMatchingService;
use App\Utility\MemberUtility;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ActiveUserResource;
use App\Http\Resources\IgnoredUserResource;
use App\Http\Resources\PackageResource;
use App\Models\ExpressInterest;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Shortlist;
use App\Models\ViewGalleryImage;
use App\Models\ViewProfilePicture;
use Laravel\Sanctum\PersonalAccessToken;

class MemberController extends Controller
{
    /**
     * Attach sent/received interest state to visible profiles in one bounded query.
     */
    private function attachInterestStatusToProfiles($profiles, User $viewer)
    {
        $profiles = collect($profiles);
        $viewerId = (int) $viewer->id;
        $profileIds = $profiles->pluck('id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->filter(static function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values();

        if ($profileIds->isEmpty()) {
            return $profiles;
        }

        $interestRows = ExpressInterest::query()
            ->where(function ($query) use ($viewerId, $profileIds) {
                $query->where(function ($sent) use ($viewerId, $profileIds) {
                    $sent->where('interested_by', $viewerId)
                        ->whereIn('user_id', $profileIds);
                })->orWhere(function ($received) use ($viewerId, $profileIds) {
                    $received->where('user_id', $viewerId)
                        ->whereIn('interested_by', $profileIds);
                });
            })
            ->get(['user_id', 'interested_by']);

        $interestedIds = [];
        foreach ($interestRows as $interest) {
            $otherUserId = (int) $interest->interested_by === $viewerId
                ? (int) $interest->user_id
                : (int) $interest->interested_by;
            if ($otherUserId > 0) {
                $interestedIds[$otherUserId] = true;
            }
        }

        $profiles->each(function ($profile) use ($interestedIds) {
            $profile->setAttribute('is_interested', isset($interestedIds[(int) $profile->id]));
        });

        return $profiles;
    }

    /**
     * جلب أعضاء الجنس الآخر، مع تشغيل محرك التوافق الموحد عند تفعيل المطابقة الذكية.
     * لا نفرض اعتماداً إدارياً على مستخدمي الموبايل؛ نستبعد فقط الحسابات المحظورة/المعطلة.
     */
    public function matched_profile(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->member) {
            return ActiveUserResource::collection(collect([]))->additional([
                'result' => true,
            ]);
        }

        $matchEnabled = filter_var(
            $request->input('match_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $gender = (int) $user->member->gender;
        if (!in_array($gender, [1, 2], true)) {
            return ActiveUserResource::collection(collect([]))->additional([
                'result' => true,
            ]);
        }

        // Pagination is opt-in: older app versions keep the complete-list contract.
        $isPagedRequest = $request->has('per_page');
        $perPage = $isPagedRequest
            ? max(1, min(30, (int) $request->input('per_page', 20)))
            : null;
        $page = $isPagedRequest
            ? max(1, (int) $request->input('page', 1))
            : null;
        $pagination = null;

        if ($matchEnabled) {
            if (!MemberUtility::can_auto_match($user->id)) {
                return response()->json([
                    'result' => false,
                    'status' => 'matching_locked',
                    'message' => translate('Smart matching is not enabled for this account.'),
                ], 403);
            }

            // Smart matching needs global ranking; page only after the scores are ordered.
            $matches = app(SmartMatchingService::class)->getMatches($user);
            if ($isPagedRequest) {
                $pageRows = $matches
                    ->slice(($page - 1) * $perPage, $perPage + 1)
                    ->values();
                $hasMore = $pageRows->count() > $perPage;
                $matches = $pageRows->take($perPage)->values();
                $pagination = [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'has_more' => $hasMore,
                ];
            }

            $matches = $this->attachInterestStatusToProfiles($matches, $user);
            $additional = [
                'result' => true,
                'smart_matching' => true,
            ];
            if ($pagination !== null) {
                $additional['pagination'] = $pagination;
            }

            return ActiveUserResource::collection($matches)->additional($additional);
        }

        $oppositeGender = $gender === 1 ? 2 : 1;
        $usersQuery = User::query()
            ->where('user_type', 'member')
            ->where('id', '!=', $user->id)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', function ($query) use ($oppositeGender) {
                $query->where('gender', $oppositeGender);
            })
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', $user->id);
            })
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $matchingService = app(SmartMatchingService::class);
        $matchingService->applyPartnerAgeRangeFilter($usersQuery, $user);

        if ($isPagedRequest) {
            $members = $usersQuery
                ->offset(($page - 1) * $perPage)
                ->limit($perPage + 1)
                ->get();
            $hasMore = $members->count() > $perPage;
            if ($hasMore) {
                $members = $members->take($perPage)->values();
            }
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => $hasMore,
            ];
        } else {
            $members = $usersQuery->get();
        }

        // Compatibility data is calculated only for the requested page.
        $members = $matchingService->scoreCandidates(
            $user,
            $members,
            false
        );
        $members = $this->attachInterestStatusToProfiles($members, $user);

        $additional = [
            'result' => true,
            'smart_matching' => false,
        ];
        if ($pagination !== null) {
            $additional['pagination'] = $pagination;
        }

        return ActiveUserResource::collection($members)->additional($additional);
    }

    public function member_listing(Request $request)
    {
        $validated = $request->validate([
            'age_from' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'age_to' => ['sometimes', 'nullable', 'integer', 'min:18', 'max:100'],
            'member_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'marital_status' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'religion_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'caste_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'sub_caste_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'mother_tongue' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'state_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'min_height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'member_type' => ['sometimes', 'nullable', 'integer', 'in:0,1,2'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $age_from = $validated['age_from'] ?? null;
        $age_to = $validated['age_to'] ?? null;
        $member_code = $validated['member_code'] ?? null;
        $marital_status = $validated['marital_status'] ?? null;
        $religion_id = $validated['religion_id'] ?? null;
        $caste_id = $validated['caste_id'] ?? null;
        $sub_caste_id = $validated['sub_caste_id'] ?? null;
        $mother_tongue = $validated['mother_tongue'] ?? null;
        $profession = $validated['profession'] ?? null;
        $country_id = $validated['country_id'] ?? null;
        $state_id = $validated['state_id'] ?? null;
        $city_id = $validated['city_id'] ?? null;
        $min_height = $validated['min_height'] ?? null;
        $max_height = $validated['max_height'] ?? null;
        $member_type = $validated['member_type'] ?? 0;
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $users_query = User::query()
            ->with([
                'member.marital_status',
                'member.mothereTongue',
                'addresses' => function ($query) {
                    $query->where('type', 'present')->with(['country', 'city']);
                },
                'career',
                'physical_attributes',
                'spiritual_backgrounds.religion',
                'spiritual_backgrounds.caste',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->where('user_type', 'member')
            ->where('id', '!=', auth()->user()->id)
            ->where('blocked', 0)
            ->where('deactivated', 0);

        // Gender Check
        $users_query->whereIn('id', Member::query()
            ->select('user_id')
            ->where('gender', '!=', auth()->user()->member->gender));

        // Ignored member and ignored by member check
        $users_query->whereNotIn('id', function ($query) {
            $query->select('user_id')
                ->from('ignored_users')
                ->where('ignored_by', auth()->user()->id)
                ->orWhere('user_id', auth()->user()->id);
        })->whereNotIn('id', function ($query) {
            $query->select('ignored_by')
                ->from('ignored_users')
                ->where('ignored_by', auth()->user()->id)
                ->orWhere('user_id', auth()->user()->id);
        });

        if ($member_type === 1 || $member_type === 2) {
            $users_query->where('membership', $member_type);
        }

        // Every active filter is applied even when its matching ID list is
        // empty. An empty match must yield zero rows, never a broader result.
        if ($age_from !== null) {
            $start = date('Y-m-d', strtotime('-' . ((int) $age_from + 1) . ' years'));
            $users_query->whereIn('id', Member::query()
                ->select('user_id')
                ->where('birthday', '<=', $start));
        }
        if ($age_to !== null) {
            $end = date('Y-m-d', strtotime('-' . ((int) $age_to + 1) . ' years +1 day'));
            $users_query->whereIn('id', Member::query()
                ->select('user_id')
                ->where('birthday', '>=', $end));
        }

        if (!empty($member_code)) {
            $users_query->where('code', $member_code);
        }

        if ($marital_status !== null) {
            $users_query->whereIn('id', Member::query()
                ->select('user_id')
                ->where('marital_status_id', $marital_status));
        }

        if (!empty($sub_caste_id)) {
            $users_query->whereIn('id', SpiritualBackground::query()
                ->select('user_id')
                ->where('sub_caste_id', $sub_caste_id));
        } elseif (!empty($caste_id)) {
            $users_query->whereIn('id', SpiritualBackground::query()
                ->select('user_id')
                ->where('caste_id', $caste_id));
        } elseif (!empty($religion_id)) {
            $users_query->whereIn('id', SpiritualBackground::query()
                ->select('user_id')
                ->where('religion_id', $religion_id));
        }

        if (!empty($profession)) {
            $users_query->whereIn('id', Career::query()
                ->select('user_id')
                ->where('designation', 'like', '%' . $profession . '%'));
        }

        // Apply every selected level of the location hierarchy. The mobile
        // client sends distinct country_id, state_id, and city_id values.
        if (!empty($country_id)) {
            $users_query->whereIn('id', Address::query()
                ->select('user_id')
                ->where('country_id', $country_id));
        }
        if (!empty($state_id)) {
            $users_query->whereIn('id', Address::query()
                ->select('user_id')
                ->where('state_id', $state_id));
        }
        if (!empty($city_id)) {
            $users_query->whereIn('id', Address::query()
                ->select('user_id')
                ->where('city_id', $city_id));
        }

        if ($mother_tongue !== null) {
            $users_query->whereIn('id', Member::query()
                ->select('user_id')
                ->where('mothere_tongue', $mother_tongue));
        }

        if ($min_height !== null) {
            $users_query->whereIn('id', PhysicalAttribute::query()
                ->select('user_id')
                ->where('height', '>=', $min_height));
        }
        if ($max_height !== null) {
            $users_query->whereIn('id', PhysicalAttribute::query()
                ->select('user_id')
                ->where('height', '<=', $max_height));
        }

        $membersPage = $users_query->paginate($perPage, ['users.*'], 'page', $page);
        $data['members'] = ActiveUserResource::collection($membersPage->getCollection());
        $data['pagination'] = [
            'current_page' => $membersPage->currentPage(),
            'last_page' => $membersPage->lastPage(),
            'per_page' => $membersPage->perPage(),
            'total' => $membersPage->total(),
            'has_more' => $membersPage->hasMorePages(),
        ];
        $data['age_from'] = $age_from;
        $data['age_to'] = $age_to;
        $data['member_code'] = $member_code;
        $data['marital_status'] = $marital_status;
        $data['religion_id'] = $religion_id;
        $data['caste_id'] = $caste_id;
        $data['sub_caste_id'] = $sub_caste_id;
        $data['mother_tongue'] = $mother_tongue;
        $data['profession'] = $profession;
        $data['country_id'] = $country_id;
        $data['state_id'] = $state_id;
        $data['city_id'] = $city_id;
        $data['min_height'] = $min_height;
        $data['max_height'] = $max_height;
        $data['member_type'] = $member_type;

        return $this->response_data($data);
    }

    public function ignored_user_list()
    {
        return IgnoredUserResource::collection(IgnoredUser::where('ignored_by', auth()->user()->id)->latest()->paginate(10))->additional([
            'result' => true
        ]);
    }

    public function add_to_ignore_list(Request $request)
    {
        if (User::find($request->user_id)) {
            try {
                IgnoredUser::create($request->only('user_id') + [
                    'ignored_by' => auth()->user()->id
                ]);

                return $this->success_message('You have ignored this member');
            } catch (\Throwable $th) {
                return $this->failure_message('Something went wrong');
            }
        }
        return $this->failure_message('Invalid Member to ignore.');
    }

    public function remove_from_ignored_list(Request $request)
    {
        $ignored_user = IgnoredUser::where('user_id', $request->user_id)->where('ignored_by', auth()->user()->id)->first();
        if ($ignored_user) {
            IgnoredUser::destroy($ignored_user->id);
            return $this->success_message('You have removed this member from your ignored list');
        }
        return $this->failure_message('Something went wrong');
    }

    /**
     * Normalize attachment evidence from the stored message, retaining both
     * numeric upload IDs and stored paths/URLs for admin review.
     */
    private function normalizeChatEvidenceAttachments($rawAttachments): array
    {
        if (is_string($rawAttachments) && trim($rawAttachments) !== '') {
            $decoded = json_decode($rawAttachments, true);
            $rawAttachments = is_array($decoded)
                ? $decoded
                : explode(',', $rawAttachments);
        } elseif ($rawAttachments === null || $rawAttachments === '') {
            $rawAttachments = [];
        } elseif (!is_array($rawAttachments)) {
            $rawAttachments = [$rawAttachments];
        }

        $references = [];
        $ids = [];
        foreach ($rawAttachments as $item) {
            if (is_array($item)) {
                $item = $item['attachment'] ?? $item['url'] ??
                    $item['file_name_url'] ?? $item['file_name'] ?? null;
            }
            if (!is_scalar($item) || is_bool($item)) {
                continue;
            }

            $reference = trim((string) $item);
            if ($reference === '') {
                continue;
            }
            $references[] = mb_substr($reference, 0, 1500);
            if (is_numeric($reference)) {
                $ids[] = (int) $reference;
            }
        }

        return [
            'attachment_ids' => array_values(array_unique($ids)),
            'attachment_refs' => array_values(array_unique($references)),
        ];
    }

    public function report_member(Request $request)
    {
        $targetUserId = (int) ($request->user_id ?? $request->id ?? 0);
        $reporterId = (int) auth()->id();
        $messageId = (int) $request->input('reported_message_id', 0);
        $reason = (string) ($request->reason ?? $request->report_reason ?? 'بدون سبب محدد');

        if ($targetUserId <= 0 || $targetUserId === $reporterId || !User::find($targetUserId)) {
            return response()->json([
                'result' => false,
                'message' => 'عضو غير صالح للإبلاغ عنه.'
            ], 422);
        }

        $reportedUser = ReportedUser::where('user_id', $targetUserId)
            ->where('reported_by', $reporterId)
            ->first();

        // A member may have one account-level report, plus additional reports
        // for distinct messages/images. Never discard already stored evidence.
        if ($reportedUser && $messageId <= 0) {
            return response()->json([
                'result' => false,
                'message' => 'لقد سبق وأن بلغت على هذا الشخص من قبل.'
            ], 200);
        }

        $chatEvidence = null;
        $chatThreadId = (int) $request->input('chat_thread_id', 0);
        $reportedItem = null;

        if ($chatThreadId > 0) {
            $thread = ChatThread::query()
                ->whereKey($chatThreadId)
                ->where(function ($query) use ($reporterId, $targetUserId) {
                    $query->where(function ($participants) use ($reporterId, $targetUserId) {
                        $participants->where('sender_user_id', $reporterId)
                            ->where('receiver_user_id', $targetUserId);
                    })->orWhere(function ($participants) use ($reporterId, $targetUserId) {
                        $participants->where('sender_user_id', $targetUserId)
                            ->where('receiver_user_id', $reporterId);
                    });
                })
                ->first();

            if (!$thread) {
                return response()->json([
                    'result' => false,
                    'message' => 'تعذر التحقق من المحادثة المرتبطة بالبلاغ.'
                ], 403);
            }

            $chatEvidence = $thread->chats()
                ->with('sender')
                ->latest('id')
                ->limit(5)
                ->get()
                ->reverse()
                ->values()
                ->map(function ($chat) use ($reporterId) {
                    $attachmentEvidence = $this->normalizeChatEvidenceAttachments(
                        $chat->attachment ?? null
                    );

                    return [
                        'message_id' => (int) $chat->id,
                        'sender_user_id' => (int) $chat->sender_user_id,
                        'sender_name' => trim(
                            (string) optional($chat->sender)->first_name . ' ' .
                            (string) optional($chat->sender)->last_name
                        ),
                        'is_reporter' => (int) $chat->sender_user_id === $reporterId,
                        'message' => mb_substr((string) ($chat->message ?? ''), 0, 5000),
                        'created_at' => optional($chat->created_at)->toIso8601String(),
                        'attachment_ids' => $attachmentEvidence['attachment_ids'],
                        'attachment_refs' => $attachmentEvidence['attachment_refs'],
                    ];
                })
                ->all();

            if ($messageId > 0) {
                // The server selects evidence by ID and verifies that the
                // reported member actually sent this item in this thread.
                $reportedMessage = Chat::query()
                    ->with('sender')
                    ->whereKey($messageId)
                    ->where('chat_thread_id', $thread->id)
                    ->where('sender_user_id', $targetUserId)
                    ->first();

                if (!$reportedMessage) {
                    return response()->json([
                        'result' => false,
                        'message' => 'تعذر التحقق من الرسالة المحددة للبلاغ.'
                    ], 403);
                }

                $attachmentEvidence = $this->normalizeChatEvidenceAttachments(
                    $reportedMessage->attachment ?? null
                );

                $reportedItem = [
                    'is_reported_item' => true,
                    'message_id' => (int) $reportedMessage->id,
                    'sender_user_id' => (int) $reportedMessage->sender_user_id,
                    'sender_name' => trim(
                        (string) optional($reportedMessage->sender)->first_name . ' ' .
                        (string) optional($reportedMessage->sender)->last_name
                    ),
                    'message' => mb_substr((string) ($reportedMessage->message ?? ''), 0, 5000),
                    'created_at' => optional($reportedMessage->created_at)->toIso8601String(),
                    'content_type' => count($attachmentIds) > 0 ? 'image' : 'message',
                    'attachment_ids' => $attachmentEvidence['attachment_ids'],
                    'attachment_refs' => $attachmentEvidence['attachment_refs'],
                    'reason' => mb_substr($reason, 0, 500),
                    'reported_at' => now()->toIso8601String(),
                ];
            }
        } elseif ($messageId > 0) {
            return response()->json([
                'result' => false,
                'message' => 'يجب ربط بلاغ الرسالة بالمحادثة الصحيحة.'
            ], 422);
        }

        if ($reportedUser) {
            $existingEvidence = $reportedUser->chat_evidence;
            if (!is_array($existingEvidence)) {
                $existingEvidence = [];
            }

            if ($reportedItem) {
                foreach ($existingEvidence as $item) {
                    if (is_array($item) &&
                        (int) ($item['message_id'] ?? 0) === $messageId &&
                        ($item['is_reported_item'] ?? false) === true) {
                        return response()->json([
                            'result' => true,
                            'message' => 'تم تسجيل بلاغ هذه الرسالة مسبقاً.'
                        ], 200);
                    }
                }
                $reportedUser->chat_evidence = array_values([
                    ...$existingEvidence,
                    $reportedItem,
                ]);
                $reportedUser->save();
            }

            $report = $reportedUser;
        } else {
            if ($reportedItem && is_array($chatEvidence)) {
                $chatEvidence[] = $reportedItem;
            }

            $report = ReportedUser::create([
                'user_id' => $targetUserId,
                'reported_by' => $reporterId,
                'reason' => $reason,
                'chat_evidence' => $chatEvidence,
            ]);
        }

        // Notifications must not undo or hide a successfully stored report.
        try {
            $reporter = auth()->user();
            $admins = User::where('user_type', 'admin')->get(['id']);
            foreach ($admins as $admin) {
                \App\Utility\NotificationUtility::set_notification(
                    'member_report',
                    $reportedItem
                        ? 'تم استلام بلاغ جديد عن رسالة أو صورة داخل محادثة.'
                        : 'تم استلام بلاغ جديد عن أحد الأعضاء.',
                    route('reported_members', $targetUserId),
                    $reporter->id,
                    $admin->id,
                    'admin'
                );
            }
        } catch (\Throwable $exception) {
            \Log::warning('Member report admin notification failed.', [
                'report_id' => $report->id ?? null,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'result' => true,
            'message' => $reportedItem
                ? 'تم إرسال البلاغ وحفظ الرسالة أو الصورة المحددة لمراجعة الإدارة.'
                : ($chatThreadId > 0
                    ? 'تم إرسال البلاغ وحفظ آخر رسائل المحادثة لمراجعة الإدارة.'
                    : 'تم إرسال بلاغك بنجاح وسيتولى الفريق مراجعته.')
        ], 200);
    }

    public function update_account_deactivation_status(Request $request)
    {
        $user = User::findOrFail(auth()->user()->id);
        $user->deactivated = $request->deacticvation;
        $user->save();

        $deacticvation_msg = $request->deacticvation == 1 ? translate('deactivated') : translate('reactivated');
        return $this->success_message('Your account ' . $deacticvation_msg . ' successfully!');
    }

    public function member_info($id)
    {
        $data = array();

        $shortlist = Shortlist::where('user_id', $id)->where('shortlisted_by', auth()->id())->first();
        $profile_reported = ReportedUser::where('user_id', $id)->where('reported_by', auth()->id())->first();
        $profile_view_resquest_status = ViewProfilePicture::where('user_id', $id)->where('requested_by', auth()->id())->where('status', 1)->first();
        $gallery_view_resquest_status = ViewGalleryImage::where('user_id', $id)->where('requested_by', auth()->id())->where('status', 1)->first();
        $do_interest = ExpressInterest::where('user_id', $id)->where('interested_by', auth()->id())->first();
        $received_interest = ExpressInterest::where('user_id', auth()->id())->where('interested_by', $id)->first();

        $data['interest_status']      = ($do_interest ? 'sent interest' : $received_interest) ? 'received interest' : 'no interest';
        $data['shortlist_status']    = $shortlist ? 1 : 0;
        $data['report_status']        = $profile_reported ? true : false;
        $data['profile_view_resquest_status']   = $profile_view_resquest_status ? true : false;
        $data['gallery_view_resquest_status']   = $gallery_view_resquest_status ? true : false;

        return $this->response_data($data);
    }

    public function member_validate(Request $request)
    {
        $false_response = [
            "result" => false,
            "user" =>   [
                'member_name' => "",
                'member_email' => "",
                'member_photo' => "",
                'remaining_interest' => "",
                'remaining_contact_view' => "",
                'remaining_photo_gallery' => "",
                'remaining_profile_image_view' => "",
                'remaining_gallery_image_view' => "",
                'current_package_info' => ""
            ]
        ];

        $token = PersonalAccessToken::findToken($request->bearerToken());
        if (!$token) {
            return response()->json($false_response);
        }

        $user = $token->tokenable;
        if ($user == null) {
            return response()->json($false_response);
        }

        SubscriptionUtility::deactivateIfInvalid($user);
        $user->refresh();

        $data['member_name'] = $user->first_name . ' ' . $user->last_name;
        $data['member_email'] = $user->email;
        $data['member_photo'] = uploaded_asset($user->photo) !== null ? uploaded_asset($user->photo) : static_asset('assets/img/avatar-place.png');
        $data['remaining_interest'] = get_remaining_package_value($user->id, 'remaining_interest');
        $data['remaining_contact_view'] = get_remaining_package_value($user->id, 'remaining_contact_view');
        $data['remaining_photo_gallery'] = get_remaining_package_value($user->id, 'remaining_photo_gallery');
        $data['remaining_profile_image_view'] = (get_setting('profile_picture_privacy') == 'only_me') ? get_remaining_package_value($user->id, 'remaining_profile_image_view') : '';
        $data['remaining_gallery_image_view'] = (get_setting('gallery_image_privacy') == 'only_me') ? get_remaining_package_value($user->id, 'remaining_gallery_image_view') : '';

        $activePackage = SubscriptionUtility::currentActivePaidPackage($user);
        $current_package_info = array(
            'package_id' => $activePackage ? $activePackage->id : null,
            'package_name' => $activePackage ? $activePackage->name : '',
            'package_expiry' => $activePackage ? date('d.m.Y', strtotime($user->member->package_validity)) : translate('Expired'),
            'is_premium' => (bool) $activePackage,
        );
        $data['current_package_info'] = $current_package_info;

        return $this->response_data($data);
    }

    public function getVerifyForm()
    {
        $user = auth()->user();
        if ($user->verification_info == null) {
            $formData = get_setting('verification_form');
            return response()->json(json_decode($formData));
        } else {
            return $this->failure_message(translate('Sorry! You have sent verification request already.'));
        }
    }

    public function isApproved(){
        $user = User::where('id',  auth()->user()->id)->first();
        $verification_info = false;
        if($user->verification_info != null){
            $verification_info = true;
        }
        return response()->json(['is_approved' => $user->approved, 'verification_info' => $verification_info]);
    }

    public function store_verification_info(Request $request)
    {
        $data = array();
        $i = 0;
        foreach (json_decode(Setting::where('type', 'verification_form')->first()->value) as $key => $element) {
            $item = array();
            if ($element->type == 'text') {
                $item['type'] = 'text';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i];
            } elseif ($element->type == 'select' || $element->type == 'radio') {
                $item['type'] = 'select';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i];
            } elseif ($element->type == 'multi_select') {
                $item['type'] = 'multi_select';
                $item['label'] = $element->label;
                $item['value'] = json_encode(explode(',', $request['element_' . $i]));
            } 
            elseif ($element->type == 'file') {
                $item['type'] = 'file';
                $item['label'] = $element->label;
                $item['value'] = $request['element_' . $i]->store('uploads/verification_form');
            }
            array_push($data, $item);
            $i++;
        }
        $user = auth()->user();
        $user->verification_info = json_encode($data);
        if ($user->save()) {
            return $this->success_message(translate('Your verification request has been submitted successfully!'));
        } 

        return $this->failure_message(translate('Something Wenr Wrong!'));
    }
}