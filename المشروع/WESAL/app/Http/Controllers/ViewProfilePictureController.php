<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use App\Models\ViewProfilePicture;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use App\Utility\SubscriptionUtility;
use Auth;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;

class ViewProfilePictureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $my_profile_pic_view_requests = DB::table('view_profile_pictures')
            ->orderBy('id', 'desc')
            ->where('user_id', Auth::user()->id)
            ->join('users', 'view_profile_pictures.user_id', '=', 'users.id')
            ->select('view_profile_pictures.id')
            ->distinct()
            ->paginate(10);
        return view('frontend.member.my_profile_pic_view_requests', compact('my_profile_pic_view_requests'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:users,id']]);
        $auth_user = Auth::user();
        if ((int) $request->id === (int) $auth_user->id) {
            return 0;
        }

        $created = SubscriptionUtility::withQuota(
            (int) $auth_user->id,
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
            return 0;
        }

        $notify_user = User::where('id', $request->id)->first();
        if (!$notify_user) {
            return 1;
        }

        try {
            $notify_type = 'profile_picture_view';
            $id = unique_notify_id();
            $notify_by = $auth_user->id;
            $info_id = $created->id;
            $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' wants to see your profile picture.');
            $route = 'profile-picture-view-request.index';

            if (get_setting('firebase_push_notification') == 1) {
                $fcmTokens = User::where('id', $request->id)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by);
            }

            Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
        } catch (\Throwable $exception) {
            \Log::warning('Profile picture view notification failed.', ['message' => $exception->getMessage()]);
        }

        if ($notify_user->email != null && get_email_template('profile_picture_view_request_email', 'status')) {
            EmailUtility::email_on_request($notify_user, 'profile_picture_view_request_email');
        }
        if ($notify_user->phone != null && addon_activation('otp_system') && get_sms_template('profile_picture_view_request', 'status') == 1) {
            SmsUtility::sms_on_request($notify_user, 'profile_picture_view_request');
        }

        return 1;
    }

    public function accept_request(Request $request)
    {
        $auth_user = Auth::user();
        $view_profile_picture = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);
        $view_profile_picture->status = 1;
        if ($view_profile_picture->save()) {

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
                    $fcmTokens = User::where('id', $view_profile_picture->requested_by)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by);
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

            flash(translate('Interest has been accepted successfully.'))->success();
            return redirect()->route('profile-picture-view-request.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function reject_request(Request $request)
    {
        $auth_user = Auth::user();
        $profile_pic_view_request = ViewProfilePicture::findOrFail($request->profile_pic_view_request_id);

        if (ViewProfilePicture::destroy($request->profile_pic_view_request_id)) {

            $notify_user = User::where('id', $profile_pic_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_profile_image_view_request';
                $id = unique_notify_id();
                $notify_by = Auth::user()->id;
                $info_id = $profile_pic_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your profile picture view request.');
                $route = route('member.listing');

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    $fcmTokens = User::where('id', $profile_pic_view_request->requested_by)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            flash(translate('Profile picture view request has been rejected successfully.'))->success();
            return redirect()->route('profile-picture-view-request.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
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
