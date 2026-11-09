<?php

use App\Http\Controllers\AdditionalMemberInfoController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\VipSurveyAdminController;
use App\Http\Controllers\Admin\VipServiceAdminController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\AstrologyController;
use App\Http\Controllers\AttitudeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CasteController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExpressInterestController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\GalleryImageController;
use App\Http\Controllers\HappyStoryController;
use App\Http\Controllers\HobbyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IgnoredUserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LifestyleController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePaymentController;
use App\Http\Controllers\PartnerExpectationController;
use App\Http\Controllers\PhonepeController;
use App\Http\Controllers\PhysicalAttributeController;
use App\Http\Controllers\ProfileMatchController;
use App\Http\Controllers\ProfileViewerController;
use App\Http\Controllers\RecidencyController;
use App\Http\Controllers\ReportedUserController;
use App\Http\Controllers\ShortlistController;
use App\Http\Controllers\SpiritualBackgroundController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubCasteController;
use App\Http\Controllers\ViewContactController;
use App\Http\Controllers\ViewGalleryImageController;
use App\Http\Controllers\ViewProfilePictureController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::controller(HomeController::class)->group(function () {
    //Home Page
    Route::get('/', 'index')->name('index');
    Route::get('/', 'index')->name('home');

    // === قائمة الأعضاء والملف الشخصي مفتوحة للزوار ===
    Route::any('/member-listing', 'member_listing')->name('member.listing');
    Route::get('/member-profile/{id}', 'view_member_profile')->name('member_profile');

    // fcm
    Route::post('/fcm-token', 'updateToken')->name('fcmToken');

    Route::get('/email_change/callback', 'email_change_callback')->name('email_change.callback');
    Route::post('/password/reset/email/submit', 'reset_password_with_code')->middleware('throttle:password-reset-web')->name('password.update.code');
    Route::get('/users/login', 'login')->name('user.login');
    Route::get('/happy-stories', 'happy_stories')->name('happy_stories');
    Route::get('/story_details/{id}', 'story_details')->name('story_details');
    Route::get('/users/blocked', 'user_account_blocked')->name('user.blocked');
    Route::post('/registration/verification-code-send', 'sendRegVerificationCode')->middleware('throttle:registration-code-send')->name('verification_code_send');
    Route::post('/registration/verification-code-confirmation', 'regVerifyCodeConfirmation')->middleware('throttle:registration-code-check')->name('verify_code_confirmation');
});

// Uploader
Route::get('/refresh-csrf', function () {
    return csrf_token();
});

Route::controller(AizUploadController::class)->group(function () {
    Route::post('/aiz-uploader', 'show_uploader');
    Route::post('/aiz-uploader/upload', 'upload');
    Route::get('/aiz-uploader/get_uploaded_files', 'get_uploaded_files');
    Route::delete('/aiz-uploader/destroy/{id}', 'destroy');
    Route::post('/aiz-uploader/get_file_by_ids', 'get_preview_files');
    Route::get('/aiz-uploader/download/{id}', 'attachment_download')->name('download_attachment');
});

Auth::routes(['verify' => true]);
Route::controller(LoginController::class)->group(function () {
    Route::get('/logout', 'logout')->name('logout');
    Route::get('/social-login/redirect/{provider}', 'redirectToProvider')->name('social.login');
    Route::get('/social-login/{provider}/callback', 'handleProviderCallback')->name('social.callback');
});

Route::controller(VerificationController::class)->group(function () {
    Route::get('/email/resend', 'resend')->name('verification.resend');
    Route::get('/verification-confirmation/{code}', 'verification_confirmation')->name('email.verification.confirmation');
});

Route::post('/language', [LanguageController::class, 'changeLanguage'])->name('language.change');
Route::get('/packages', [PackageController::class, 'select_package'])->name('packages');

//Blog
Route::controller(BlogController::class)->group(function () {
    Route::get('/blog', 'all_blog')->name('blog');
    Route::get('/blog/{slug}', 'blog_details')->name('blog.details');
    Route::get('/blog/description/{slug}', 'blog_description')->name('blog.description');
});

Route::group(['middleware' => ['verified']], function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    Route::controller(MemberController::class)->group(function () {
        Route::get('/member/verification', 'verification_form')->name('member.verification');
        Route::post('/member/verification-info/store', 'verification_info_store')->name('member.verification_info.store');
    });
});

Route::group(['middleware' => ['member', 'verified']], function () {
    Route::controller(HomeController::class)->middleware('activated')->group(function () {
        Route::post('/new-user-email', 'update_email')->name('user.change.email');
        Route::post('/new-user-verification', 'new_verify')->name('user.new.verify');
        Route::post('/user/remaining_package_value', 'user_remaining_package_value')->name('user.remaining_package_value');
    });

    Route::controller(MemberController::class)->group(function () {
        Route::middleware('activated')->group(function () {
            Route::get('/members/change-password', 'change_password')->name('member.change_password');
            Route::post('/member/password-update/{id}', 'password_update')->name('member.password_update');
            Route::get('/profile-settings', 'profile_settings')->name('profile_settings');
        });

        Route::post('/member/account-activation', 'update_account_deactivation_status')->name('member.account_deactivation');
        Route::post('/member/account-delete', 'account_delete')->name('member.account_delete');
        Route::post('/member/generate-ai-bio', 'generate_ai_bio')->name('member.generate_ai_bio');
    });

    Route::middleware('activated')->group(function () {
        Route::get('/package-payment-methods/{id}', [PackageController::class, 'package_payemnt_methods'])->name('package_payment_methods');
        Route::controller(PackagePaymentController::class)->group(function () {
            Route::post('/package-payment', 'store')->name('package.payment');
            Route::get('/package-purchase-history', 'package_purchase_history')->name('package_purchase_history');
        });

        Route::resource('/gallery-image', GalleryImageController::class);
        Route::get('/gallery_image/destroy/{id}', [GalleryImageController::class, 'destroy'])->name('gallery_image.destroy');

        Route::resource('/express-interest', ExpressInterestController::class);
        Route::controller(ExpressInterestController::class)->group(function () {
            Route::get('/my-interests', 'index')->name('my_interests.index');
            Route::get('/interest/requests', 'interest_requests')->name('interest_requests');
            Route::post('/interest/accept', 'accept_interest')->name('accept_interest');
            Route::post('/interest/reject', 'reject_interest')->name('reject_interest');
        });

        Route::controller(ChatController::class)->group(function () {
            Route::get('/chat', 'index')->name('all.messages');
            Route::any('/chat/store', [ChatController::class, 'store'])->name('chat.store');
            Route::get('/single-chat/{id}', 'chat_view')->name('chat_view');
            Route::post('/chat-reply', 'chat_reply')->name('chat.reply');
            Route::get('/chat/refresh/{id}', 'chat_refresh')->name('chat_refresh');
            Route::post('/chat/old-messages', 'get_old_messages')->name('get-old-message');
            Route::post('/chat/mark-all-read', 'mark_all_read')->name('chat.mark_all_read');
            Route::post('/chat/delete-all', 'delete_all_chats')->name('chat.delete_all');
            Route::post('/chat/delete', 'chat_delete')->name('chat.delete');
        });

        Route::controller(ShortlistController::class)->group(function () {
            Route::get('/my-shortlists', 'index')->name('my_shortlists');
            Route::post('/member/add-to-shortlist', 'create')->name('member.add_to_shortlist');
            Route::post('/member/remove-from-shortlist', 'remove')->name('member.remove_from_shortlist');
        });

        Route::controller(IgnoredUserController::class)->group(function () {
            Route::get('/ignored-list', 'index')->name('my_ignored_list');
            Route::post('/member/add-to-ignore-list', 'add_to_ignore_list')->name('member.add_to_ignore_list');
            Route::post('/member/remove-from-ignored-list', 'remove_from_ignored_list')->name('member.remove_from_ignored_list');
        });

        Route::resource('/profile-picture-view-request', ViewProfilePictureController::class);
        Route::controller(ViewProfilePictureController::class)->group(function () {
            Route::post('/profile-picture-view-request/accept', 'accept_request')->name('profile_picture_view_request_accept');
            Route::post('/profile-picture-view-request/reject', 'reject_request')->name('profile_picture_view_request_reject');
        });

        Route::resource('/gallery-image-view-request', ViewGalleryImageController::class);
        Route::controller(ViewGalleryImageController::class)->group(function () {
            Route::post('/gallery-image-view-request/accept', 'accept_request')->name('gallery_image_view_request_accept');
            Route::post('/gallery-image-view-request/reject', 'reject_request')->name('gallery_image_view_request_reject');
        });

        Route::resource('reportusers', ReportedUserController::class);
        Route::resource('view_contacts', ViewContactController::class);

        Route::controller(WalletController::class)->group(function () {
            Route::get('/wallet', 'index')->name('wallet.index');
            Route::get('/wallet-recharge-methods', 'wallet_recharge_methods')->name('wallet.recharge_methods');
            Route::post('/recharge', 'recharge')->name('wallet.recharge');
        });

        Route::get('/member/notifications', [NotificationController::class, 'frontend_notify_listing'])->name('frontend.notifications');

        Route::controller(HappyStoryController::class)->group(function () {
            Route::get('/happy-story', 'create')->name('happy_story.member');
            Route::post('/happy-story/store', 'store')->name('happy_story.store');
        });

        Route::resource('profile-viewers', ProfileViewerController::class);
        Route::get('/matched-profiles', [ProfileMatchController::class, 'myMatchedProfiles'])->name('my_matched_profiles');
    });
});

Route::group(['middleware' => ['auth', 'activated']], function () {

    Route::controller(MemberController::class)->group(function () {
        Route::post('/members/introduction_update/{id}', 'introduction_update')->name('member.introduction.update');
        Route::post('/members/basic_info_update/{id}', 'basic_info_update')->name('member.basic_info_update');
        Route::post('/members/language_info_update/{id}', 'language_info_update')->name('member.language_info_update');
        Route::get('/member/notifications/mark-all-as-read', 'mark_all_as_read')->name('notifications.mark_all_as_read');
    });

    Route::resource('/address', AddressController::class);

    Route::resource('/education', EducationController::class);
    Route::controller(EducationController::class)->group(function () {
        Route::post('/education/create', 'create')->name('education.create');
        Route::post('/education/edit', 'edit')->name('education.edit');
        Route::post('/education/update_education_present_status', 'update_education_present_status')->name('education.update_education_present_status');
        Route::post('/education/update-highest-degree', 'updateHighestDegree')->name('education.update_highest_degree');
        Route::get('/education/destroy/{id}', 'destroy')->name('education.destroy');
    });

    Route::resource('/career', CareerController::class);
    Route::controller(CareerController::class)->group(function () {
        Route::post('/career/create', 'create')->name('career.create');
        Route::post('/career/edit', 'edit')->name('career.edit');
        Route::post('/career/update_career_present_status', 'update_career_present_status')->name('career.update_career_present_status');
        Route::get('/career/destroy/{id}', 'destroy')->name('career.destroy');
    });

    Route::resource('/physical-attribute', PhysicalAttributeController::class);
    Route::resource('/hobbies', HobbyController::class);
    Route::resource('/attitudes', AttitudeController::class);
    Route::resource('/recidencies', RecidencyController::class);
    Route::resource('/lifestyles', LifestyleController::class);
    Route::resource('/astrologies', AstrologyController::class);
    Route::resource('/families', FamilyController::class);
    Route::resource('/spiritual_backgrounds', SpiritualBackgroundController::class);
    Route::resource('/partner_expectations', PartnerExpectationController::class);
    Route::post('/additional-member-info/update', [AdditionalMemberInfoController::class, 'update'])->name('additional_member_info.update');

    Route::post('/states/get_state_by_country', [StateController::class, 'get_state_by_country'])->name('states.get_state_by_country');
    Route::post('/cities/get_cities_by_state', [CityController::class, 'get_cities_by_state'])->name('cities.get_cities_by_state');
    Route::post('/castes/get_caste_by_religion', [CasteController::class, 'get_caste_by_religion'])->name('castes.get_caste_by_religion');
    Route::post('/sub-castes/get_sub_castes_by_religion', [SubCasteController::class, 'get_sub_castes_by_religion'])->name('sub_castes.get_sub_castes_by_religion');

    Route::get('/package-payment-invoice/{id}', [PackagePaymentController::class, 'package_payment_invoice'])->name('package_payment.invoice');

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notification-view/{id}', 'notification_view')->name('notification_view');
        Route::get('/notification/mark-all-as-read', 'mark_all_as_read')->name('notification.mark_all_as_read');
        Route::post('/admin/notifications/send', 'send_custom_notification')->name('admin.notifications.send');
    });

    // 👑 مسارات لوحة تحكم الأدمن لاستبيانات باقة VIP
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/vip-surveys', [VipSurveyAdminController::class, 'index'])->name('vip-surveys.index');
        Route::get('/vip-surveys/{id}', [VipSurveyAdminController::class, 'show'])->name('vip-surveys.show');
        Route::post('/vip-surveys/update-status/{id}', [VipSurveyAdminController::class, 'updateStatus'])->name('vip-surveys.update_status');

        // 💎 مسارات لوحة تحكم الأدمن لطلبات خدمات VIP الإضافية والحذف
        Route::get('/vip-services', [VipServiceAdminController::class, 'index'])->name('admin.vip-services.index');
        Route::get('/vip-services/{id}', [VipServiceAdminController::class, 'show'])->name('admin.vip-services.show');
        Route::post('/vip-services/update-status/{id}', [VipServiceAdminController::class, 'updateStatus'])->name('admin.vip-services.update_status');
        Route::delete('/vip-services/destroy/{id}', [VipServiceAdminController::class, 'destroy'])->name('admin.vip-services.destroy');
        Route::delete('/vip-services/destroy-all', [VipServiceAdminController::class, 'destroyAll'])->name('admin.vip-services.destroy-all');
    });
});

// Contact Us page
Route::controller(ContactUsController::class)->group(function () {
    Route::get('/contact-us/page', 'show_contact_us_page')->name('contact_us');
    Route::post('/contact-us', 'store')->name('contact-us.store');
});

// Payment gateway Redirect

//Paypal START
Route::get('/paypal/payment/done', 'PaypalController@getDone')->name('payment.done');
Route::get('/paypal/payment/cancel', 'PaypalController@getCancel')->name('payment.cancel');
//Paypal END

//amarpay
Route::post('/aamarpay/success', 'AamarpayController@success')->name('aamarpay.success');
Route::post('/aamarpay/fail', 'AamarpayController@fail')->name('aamarpay.fail');

// SSLCOMMERZ Start
Route::get('/sslcommerz/pay', 'SslcommerzController@index');
Route::any('/sslcommerz/success', 'SslcommerzController@success')->name('sslcommerz.success');
Route::any('/sslcommerz/fail', 'SslcommerzController@fail');
Route::any('/sslcommerz/cancel', 'SslcommerzController@cancel');
Route::post('/sslcommerz/ipn', 'SslcommerzController@ipn');

Route::get('/instamojo/payment/pay-success', 'InstamojoController@success')->name('instamojo.success');
Route::post('rozer/payment/pay-success', 'RazorpayController@payment')->name('payment.rozer');
Route::get('/paystack/payment/callback', 'PaystackController@handleGatewayCallback');

//Stipe Start
Route::middleware('auth')->controller(StripeController::class)->group(function () {
    Route::get('stripe', 'stripe');
    Route::post('/stripe/create-checkout-session', 'create_checkout_session')->name('stripe.get_token');
    Route::any('/stripe/payment/callback', 'callback')->name('stripe.callback');
    Route::get('/stripe/success', 'success')->name('stripe.success');
    Route::get('/stripe/cancel', 'cancel')->name('stripe.cancel');
});
//Stripe END

//Paytm
Route::get('/paytm/index', 'PaytmController@index');
Route::post('/paytm/callback', 'PaytmController@callback')->name('paytm.callback');

// phonepe
Route::controller(PhonepeController::class)->group(function () {
    Route::any('/phonepe/pay', 'pay')->name('phonepe.pay');
    Route::any('/phonepe/redirecturl', 'phonepe_redirecturl')->name('phonepe.redirecturl');
    Route::any('/phonepe/callbackUrl', 'phonepe_callbackUrl')->name('phonepe.callbackUrl');
});

Route::get('/match_profiles', 'ProfileMatchController@match_profiles')->name('match_profiles');

//Custom page
Route::get('/{slug}', 'PageController@show_custom_page')->name('custom-pages.show_custom_page');

Route::get('/manifest.json', function () {
    return response()->file(public_path('manifest.json'));
});