<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use App\Models\ViewGalleryImage;
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

class ViewGalleryImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $my_gallery_image_view_requests = DB::table('view_gallery_images')
            ->orderBy('id', 'desc')
            ->where('user_id', Auth::user()->id)
            ->join('users', 'view_gallery_images.user_id', '=', 'users.id')
            ->select('view_gallery_images.id')
            ->distinct()
            ->paginate(10);
        return view('frontend.member.my_gallery_image_view_requests', compact('my_gallery_image_view_requests'));
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
        $authUser = Auth::user();
        if ((int) $request->id === (int) $authUser->id) {
            flash(translate('You cannot request your own gallery images.'))->warning();
            return back();
        }

        $created = SubscriptionUtility::withQuota(
            (int) $authUser->id,
            'remaining_gallery_image_view',
            function ($sender) use ($request) {
                $existing = ViewGalleryImage::where('user_id', $request->id)
                    ->where('requested_by', $sender->id)
                    ->first();
                if ($existing) {
                    return null;
                }
                $record = new ViewGalleryImage();
                $record->user_id = (int) $request->id;
                $record->requested_by = (int) $sender->id;
                $record->save();
                return $record;
            }
        );

        if (!$created) {
            flash(translate('You do not have an active package or remaining gallery-view request quota, or the request already exists.'))->error();
            return back();
        }

        $notifyUser = User::find($request->id);
        if ($notifyUser) {
            try {
                $notifyType = 'gallery_image_view';
                $message = trim(($authUser->first_name ?? '') . ' ' . ($authUser->last_name ?? '')) . ' ' . translate(' wants to see your gallery images.');
                $route = 'gallery-image-view-request.index';
                if (get_setting('firebase_push_notification') == 1) {
                    self::sendFirebaseNotification($notifyUser->fcm_token, $notifyUser, $notifyType, $message, $authUser->id);
                }
                Notification::send($notifyUser, new DbStoreNotification($notifyType, unique_notify_id(), $authUser->id, $created->id, $message, $route));
            } catch (\Throwable $exception) {
                \Log::warning('Gallery view notification failed.', ['message' => $exception->getMessage()]);
            }
            if ($notifyUser->email && get_email_template('gallery_image_view_request_email', 'status')) {
                EmailUtility::email_on_request($notifyUser, 'gallery_image_view_request_email');
            }
            if ($notifyUser->phone && addon_activation('otp_system') && get_sms_template('gallery_image_view_request', 'status') == 1) {
                SmsUtility::sms_on_request($notifyUser, 'gallery_image_view_request');
            }
        }

        flash(translate('Gallery image view request sent successfully.'))->success();
        return back();
    }

    public function accept_request(Request $request)
    {
        $auth_user = Auth::user();
        $view_gallery_image = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);
        //   dd($view_gallery_image);
        $view_gallery_image->status = 1;
        if ($view_gallery_image->save()) {

            $notify_user = User::where('id', $view_gallery_image->requested_by)->first();

            // Express Interest Store Notification for member
            try {
                $notify_type = 'accept_gallery_image_view_request';
                $id = unique_notify_id();
                $notify_by = $auth_user->id;
                $info_id = $view_gallery_image->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has accepted your gallery image view request.');
                $route = route("member_profile", $auth_user->id);

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    $fcmTokens = User::where('id', $view_gallery_image->requested_by)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            // View Profile Picture email send to member
            if ($notify_user->email != null && get_email_template('gallery_image_view_request_accepted_email', 'status')) {
                EmailUtility::email_on_accept_request($notify_user, 'gallery_image_view_request_accepted_email');
            }

            // View Profile Picture email SMS to member
            if ($notify_user->phone != null && addon_activation('otp_system') && (get_sms_template('gallery_image_view_request_accepted', 'status') == 1)) {
                SmsUtility::sms_on_accept_request($notify_user, 'gallery_image_view_request_accepted');
            }

            flash(translate('Interest has been accepted successfully.'))->success();
            return redirect()->route('gallery-image-view-request.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function reject_request(Request $request)
    {
        $auth_user = Auth::user();
        $gallery_view_request = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);

        if (ViewGalleryImage::destroy($request->gallery_image_view_request_id)) {

            $notify_user = User::where('id', $gallery_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_gallery_image_view_request';
                $id = unique_notify_id();
                $notify_by = Auth::user()->id;
                $info_id = $gallery_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your gallery image view request.');
                $route = route("member_profile", $auth_user->id);

                // fcm 
                if (get_setting('firebase_push_notification') == 1) {
                    $fcmTokens = User::where('id', $gallery_view_request->requested_by)->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                    self::sendFirebaseNotification($fcmTokens, $notify_user, $notify_type, $message, $notify_by);
                }
                // end of fcm

                Notification::send($notify_user, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // dd($e);
            }

            flash(translate('gallery image view request has been rejected successfully.'))->success();
            return redirect()->route('gallery-image-view-request.index');
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
