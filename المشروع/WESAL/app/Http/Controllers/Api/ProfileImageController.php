<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProfileImageRequest;
use App\Models\Member;
use App\Models\User;
use App\Models\ViewProfilePicture;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use App\Utility\SubscriptionUtility;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;

class ProfileImageController extends Controller
{
    public function image_view_request()
    {
        $my_profile_pic_view_requests = DB::table('view_profile_pictures')
            ->orderBy('id', 'desc')
            ->where('user_id', auth()->id())
            ->join('users', 'view_profile_pictures.user_id', '=', 'users.id')
            ->select('view_profile_pictures.id')
            ->distinct()
            ->paginate(10);
        return ProfileImageRequest::collection($my_profile_pic_view_requests);
        // return $this->response_data($my_profile_pic_view_requests);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_image_view_request(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:users,id']]);
        $authUser = auth()->user();
        if ((int) $request->id === (int) $authUser->id) {
            return $this->failure_message('You cannot request your own profile picture.');
        }

        $created = SubscriptionUtility::withQuota(
            (int) $authUser->id,
            'remaining_profile_image_view',
            function ($sender) use ($request) {
                $existing = ViewProfilePicture::where('user_id', $request->id)
                    ->where('requested_by', $sender->id)
                    ->first();
                if ($existing) {
                    return null;
                }
                $record = new ViewProfilePicture();
                $record->user_id = (int) $request->id;
                $record->requested_by = (int) $sender->id;
                $record->save();
                return $record;
            }
        );

        if (!$created) {
            return $this->failure_message('You do not have an active package or remaining profile-picture request quota, or the request already exists.');
        }

        $notifyUser = User::find($request->id);
        if ($notifyUser) {
            try {
                $notifyType = 'profile_picture_view';
                $message = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? '')) . ' ' . translate(' wants to see your profile picture.');
                $route = 'profile-picture-view-request.index';
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notifyUser->fcm_token, $notifyUser, $notifyType, $message, $authUser->id);
                }
                Notification::send($notifyUser, new DbStoreNotification($notifyType, unique_notify_id(), $authUser->id, $created->id, $message, $route));
            } catch (\Throwable $exception) {
                \Log::warning('Profile picture view notification failed.', ['message' => $exception->getMessage()]);
            }
            if ($notifyUser->email && get_email_template('profile_picture_view_request_email', 'status')) {
                EmailUtility::email_on_request($notifyUser, 'profile_picture_view_request_email');
            }
            if ($notifyUser->phone && addon_activation('otp_system') && get_sms_template('profile_picture_view_request', 'status') == 1) {
                SmsUtility::sms_on_request($notifyUser, 'profile_picture_view_request');
            }
        }
        return $this->success_message('profile image view request sent successfully');
    }

    public function accept_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $view_profile_picture = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);

        if ($view_profile_picture) {
            $view_profile_picture->status = 1;
            $view_profile_picture->save();
            $notify_user = User::where('id', $view_profile_picture->requested_by)->first();

            // Express Interest Store Notification for member
            try {
                $notify_type = 'accept_profile_picture_view_request';
                $id = unique_notify_id();
                $notify_by = $auth_user->id;
                $info_id = $view_profile_picture->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has accepted your profile picture view request.');
                $route = route("member_profile", $auth_user->id);

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            // View Profile Picture email send to member
            if ($notify_user->email != null && get_email_template('profile_picture_view_request_accepted_email', 'status')) {
                EmailUtility::email_on_accept_request($notify_user, 'profile_picture_view_request_accepted_email');
            }

            // View Profile Picture email SMS to member
            if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('profile_picture_view_request_accepted', 'status') == 1)) {
                SmsUtility::sms_on_accept_request($notify_user, 'profile_picture_view_request_accepted');
            }
            return $this->success_message('Interest has been accepted successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request');
        }
    }

    public function reject_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $profile_pic_view_request = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);

        if (ViewProfilePicture::destroy($request->profile_pic_view_request_id)) {

            $notify_user = User::where('id', $profile_pic_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_profile_image_view_request';
                $id = unique_notify_id();
                $notify_by = auth()->id();
                $info_id = $profile_pic_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your profile picture view request.');
                $route = route('member.listing');

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            return $this->success_message('profile image view request has been rejected successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request');
        }
    }

    public static function sendFirebaseNotification($fcmTokens = null, $notify_user, $notify_type, $message, $notify_by = null)
    {
        // send firebase notification for mobile app
        if ($notify_user->fcm_token != null) {
            $data = (object)[];
            $data->fcm_token = $notify_user->fcm_token;
            $data->title = $notify_type;
            $data->text = $message;
            $data->notify_by = $notify_by;
            FirbaseNotification::send($data);
        }
        // end of  firebase notification

        Larafirebase::withTitle(str_replace("_", " ", $notify_type))
            ->withBody($message)
            ->sendMessage($fcmTokens);
    }
}
