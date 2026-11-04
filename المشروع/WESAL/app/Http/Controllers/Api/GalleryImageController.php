<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\GalleryImageRequest;
use App\Http\Resources\GalleryImageResource;
use App\Models\GalleryImage;
use App\Models\Member;
use App\Models\User;
use App\Models\ViewGalleryImage;
use App\Utility\SubscriptionUtility;
use App\Notifications\DbStoreNotification;
use App\Services\FirbaseNotification;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use DB;
use Illuminate\Http\Request;
use Kutia\Larafirebase\Facades\Larafirebase;
use Notification;


class GalleryImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $gallery_image_id = GalleryImage::where('user_id', request()->user()->id)->latest()->get();
        return GalleryImageResource::collection($gallery_image_id)->additional([
            'result' => true
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(['gallery_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']]);
        $created = SubscriptionUtility::withQuota(
            (int) auth()->id(),
            'remaining_photo_gallery',
            function ($user, $member) use ($request) {
                $photo = upload_api_file($request->file('gallery_image'));
                if (!$photo) {
                    return null;
                }
                return GalleryImage::create([
                    'user_id' => $user->id,
                    'image' => $photo,
                ]);
            }
        );

        if (!$created) {
            return $this->failure_message('You do not have an active package or remaining gallery-photo quota.');
        }
        return $this->success_message('Gallery image uploaded successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (GalleryImage::destroy($id)) {
            return $this->success_message('Image deleted successfully.');
        }
        return $this->failure_message('Sorry! Something went wrong.');
    }

    public function image_view_request()
    {
        $my_gallery_image_view_requests = DB::table('view_gallery_images')
            ->orderBy('id', 'desc')
            ->where('user_id', auth()->id())
            ->join('users', 'view_gallery_images.user_id', '=', 'users.id')
            ->select('view_gallery_images.id')
            ->distinct()
            ->paginate(10);
        return GalleryImageRequest::collection($my_gallery_image_view_requests);
        // return $this->response_data($my_gallery_image_view_requests);
    }
    public function store_image_view_request(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:users,id']]);
        $authUser = auth()->user();
        if ((int) $request->id === (int) $authUser->id) {
            return $this->failure_message('You cannot request your own gallery images.');
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
            return $this->failure_message('You do not have an active package or remaining gallery-view request quota, or the request already exists.');
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
                \Log::warning('API gallery view notification failed.', ['message' => $exception->getMessage()]);
            }
            if ($notifyUser->email && get_email_template('gallery_image_view_request_email', 'status')) {
                EmailUtility::email_on_request($notifyUser, 'gallery_image_view_request_email');
            }
            if ($notifyUser->phone && addon_activation('otp_system') && get_sms_template('gallery_image_view_request', 'status') == 1) {
                SmsUtility::sms_on_request($notifyUser, 'gallery_image_view_request');
            }
        }
        return $this->success_message('gallery image view request sent successfully');
    }
    public function accept_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $view_gallery_image = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);
        //   dd($view_gallery_image);
        $view_gallery_image->status = 1;
        $view_gallery_image->save();
        if ($view_gallery_image) {

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
                    self::sendFirebaseNotification($notify_user->fcm_token, $notify_user, $notify_type, $message, $notify_by);
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

            return $this->success_message('Interest has been accepted successfully.');
        } else {
            return $this->failure_message('Sorry! Did not find any request.');
        }
    }
    public function reject_image_view_request(Request $request)
    {
        $auth_user = auth()->user();
        $gallery_view_request = ViewGalleryImage::findOrFail($request->gallery_image_view_request_id);

        if (ViewGalleryImage::destroy($request->gallery_image_view_request_id)) {

            $notify_user = User::where('id', $gallery_view_request->requested_by)->first();
            try {
                $notify_type = 'reject_gallery_image_view_request';
                $id = unique_notify_id();
                $notify_by = auth()->id();
                $info_id = $gallery_view_request->id;
                $message = $auth_user->first_name . ' ' . $auth_user->last_name . ' ' . translate(' has rejected your gallery image view request.');
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

            return $this->success_message('gallery image view request has been rejected successfully.');
        } else {
            return $this->failure_message('Sorry! Something went wrong.');
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
