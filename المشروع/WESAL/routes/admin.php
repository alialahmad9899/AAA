<?php

use App\Http\Controllers\AdditionalAttributeController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\AnnualSalaryRangeyController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CasteController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FamilyStatusController;
use App\Http\Controllers\FamilyValueController;
use App\Http\Controllers\HappyStoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ManualPaymentMethodController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\MemberBulkAddController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberLanguageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnBehalfController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackagePaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\ReportedUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SubCasteController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Admin\VipSurveyAdminController;
use App\Http\Controllers\Admin\VipServiceAdminController;
use App\Http\Controllers\Admin\SupportTicketAdminController;

/*
  |--------------------------------------------------------------------------
  | Admin Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register admin routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

Route::controller(UpdateController::class)->group(function () {
    Route::post('/update', 'step0')->name('update');
    Route::get('/update/step1', 'step1')->name('update.step1');
    Route::get('/update/step2', 'step2')->name('update.step2');
    Route::get('/update/step3', 'step3')->name('update.step3');
    Route::post('/purchase_code', 'purchase_code')->name('update.code');
});

Route::group(['prefix' => 'admin'], function () {
    Route::get('/', [HomeController::class,'admin_login'])->name('admin');
});

Route::get('/admin/login', [HomeController::class, 'admin_login'])->name('admin.login');

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/dashboard', [HomeController::class, 'admin_dashboard'])->name('admin.dashboard');

    Route::resource('profile', ProfileController::class);

    // Contact Us page
    Route::resource('/contact-us', ContactUsController::class);

    // Mobile app support tickets (admin/staff only).
    Route::controller(SupportTicketAdminController::class)->group(function () {
        Route::get('/support-tickets', 'index')->name('admin.support-tickets.index');
        Route::get('/support-tickets/{id}', 'show')->name('admin.support-tickets.show');
        Route::post('/support-tickets/{id}/reply', 'reply')->name('admin.support-tickets.reply');
    });
    Route::get('/contact-us/destroy/{id}', [ContactUsController::class, 'destroy'])->name('contact-us.delete');

    // Member Manage
    Route::resource('members', MemberController::class);
    Route::controller(MemberController::class)->group(function () {
        Route::get('/members/member_list/{id}', 'index')->name('members.index');
        Route::post('/members/block', 'block')->name('members.block');
        Route::post('/members/blocking_reason', 'blocking_reason')->name('members.blocking_reason');
        Route::get('/members/login/{id}', 'login')->name('members.login');

        Route::get('/deleted_members', 'deleted_members')->name('deleted_members');
        Route::get('/members/destroy/{id}', 'destroy')->name('members.destroy');
        Route::get('/restore_deleted_member/{id}', 'restore_deleted_member')->name('restore_deleted_member');
        Route::get('/members/permanently_delete/{id}', 'member_permanemtly_delete')->name('members.permanently_delete');

        Route::get('/member/unapproved-profile-pictures', 'unapproved_profile_pictures')->name('unapproved_profile_pictures');
        Route::post('/member/approve_profile_image', 'approve_profile_image')->name('approve_profile_image');

        Route::get('/member/show-verification-info/{id}', 'show_verification_info')->name('member.show_verification_info');
        Route::get('/member/approve-verification/{id}', 'approve_verification')->name('member.approve_verification');
        Route::get('/member/reject-verification/{id}', 'reject_verification')->name('member.reject_verification');

        // Member's package manage
        Route::post('/members/package_info', 'package_info')->name('members.package_info');
        Route::post('/members/get_package', 'get_package')->name('members.get_package');
        Route::post('/members/package_do_update/{id}', 'package_do_update')->name('members.package_do_update');
        Route::post('/members/package_cancel/{id}', 'package_cancel')->name('members.package_cancel');
        Route::post('/members/wallet-balance-update', 'member_wallet_balance_update')->name('member.wallet_balance_update');
        
        // مسار إرسال الإشعار المخصص
        Route::post('/members/send-notification', 'send_custom_notification')->name('members.send_notification');
        
        // مسار الإرسال الجماعي
        Route::post('/members/bulk-notification', 'send_bulk_notification')->name('members.bulk_notification');

        Route::get('/member-list/{status}', 'filterbyStatus')->name('filterbyStatus');
    });

    Route::controller(ReportedUserController::class)->group(function () {
        Route::get('/reported-members/{id}', 'reported_members')->name('reported_members');
        Route::get('/reported/destroy/{id}', 'destroy')->name('report_destrot.destroy');
    });

    // Bulk member
    Route::controller(MemberBulkAddController::class)->group(function () {
        Route::get('/member-bulk-add/index', 'index')->name('member_bulk_add.index');
        Route::get('/download/on-behalf', 'pdf_download_on_behalf')->name('pdf.on_behalf');
        Route::get('/download/package', 'pdf_download_package')->name('pdf.package');
        Route::post('/bulk-member-upload', 'bulk_upload')->name('bulk_member_upload');
    });

    // Premium Packages
    Route::resource('/packages', PackageController::class);
    Route::controller(PackageController::class)->group(function () {
        Route::post('/packages/update_status', 'update_status')->name('packages.update_status');
        Route::get('/packages/destroy/{id}', 'destroy')->name('packages.destroy');
    });

    // Package Payments
    Route::resource('package-payments', PackagePaymentController::class);
    Route::controller(PackagePaymentController::class)->group(function () {
        Route::post('/manual-payment-accept/{id}', 'manual_payment_accept')->name('manual_payment_accept');
        Route::post('/manual-payment-reject/{id}', 'manual_payment_reject')->name('manual_payment_reject');
        Route::get('/package-payment-invoice/{id}', 'package_payment_invoice_admin')->name('package_payment.invoice_admin');
    });

    // 🌟 مسارات إدارة طلبات باقة VIP في لوحة التحكم
    Route::controller(VipSurveyAdminController::class)->group(function () {
        Route::get('/vip-surveys', 'index')->name('vip-surveys.index');
        Route::get('/vip-surveys/{id}', 'show')->name('vip-surveys.show');
        Route::post('/vip-surveys/{id}/status', 'updateStatus')->name('vip-surveys.update_status');
    });

    // 👑 مسارات إدارة طلبات خدمات VIP الإضافية
    Route::controller(VipServiceAdminController::class)->group(function () {
        Route::get('/vip-services/orders', 'index')->name('admin.vip-services.index');
        Route::get('/vip-services/orders/{id}', 'show')->name('admin.vip-services.show');
        Route::post('/vip-services/orders/{id}/status', 'updateStatus')->name('admin.vip-services.update_status');
        Route::post('/vip-services/orders/{id}/payment-status', 'updatePaymentStatus')->name('admin.vip-services.update_payment_status');
    });

    // Wallet
    Route::controller(WalletController::class)->group(function () {
        Route::get('/wallet-transaction-history', 'wallet_transaction_history_admin')->name('wallet_transaction_history_admin');
        Route::get('/manual-wallet-recharge-requests', 'manual_wallet_recharge_requests')->name('manual_wallet_recharge_requests');
        Route::get('/wallet-payment-details/{id}', 'show')->name('wallet_payment_details');
        Route::get('/wallet-manual-payment-accept/{id}', 'wallet_manual_payment_accept')->name('wallet_manual_payment_accept');
    });

    Route::resource('/happy-story', HappyStoryController::class);
    Route::post('/happy-story/update-story-status',[HappyStoryController::class, 'approval_status'])->name('happy_story_approval.status');

    // Blog Section
    Route::resource('blog-category', BlogCategoryController::class);
    Route::get('/blog-category/destroy/{id}', [BlogCategoryController::class, 'destroy'])->name('blog-category.destroy');

    Route::resource('blog', BlogController::class);
    Route::controller(BlogController::class)->group(function () {
        Route::get('/blog/destroy/{id}', 'destroy')->name('blog.destroy');
        Route::post('/blog/change-status', 'change_status')->name('blog.change-status');
    });

    // Member profile attributes
    // Religions
    Route::resource('/religions', ReligionController::class);
    Route::controller(ReligionController::class)->group(function () {
        Route::get('/religions/destroy/{id}', 'destroy')->name('religions.destroy');
        Route::post('/religion/bulk_destroy', 'religion_bulk_delete')->name('religion.bulk_delete');
    });

    // Caste
    Route::resource('/castes', CasteController::class);
    Route::controller(CasteController::class)->group(function () {
        Route::get('/castes/destroy/{id}', 'destroy')->name('castes.destroy');
        Route::post('/caste/bulk_destroy', 'caste_bulk_delete')->name('caste.bulk_delete');
    });

    // SubCaste
    Route::resource('/sub-castes', SubCasteController::class);
    Route::controller(SubCasteController::class)->group(function () {
        Route::get('/sub-castes/destroy/{id}', 'destroy')->name('sub-castes.destroy');
        Route::post('/sub-caste/bulk_destroy', 'sub_caste_bulk_delete')->name('sub-castes.bulk_delete');
    });

    // Member Language
    Route::resource('member-languages', MemberLanguageController::class);
    Route::get('/member-language/destroy/{id}', [MemberLanguageController::class, 'destroy'])->name('member-languages.destroy');

    // Country
    Route::resource('/countries', CountryController::class);
    Route::controller(CountryController::class)->group(function () {
        Route::post('/countries/status', 'updateStatus')->name('countries.status');
        Route::get('/countries/destroy/{id}', 'destroy')->name('countries.destroy');
    });

    // State
    Route::resource('/states', StateController::class);
    Route::get('/states/destroy/{id}', [StateController::class,'destroy'])->name('states.destroy');

    // City
    Route::resource('/cities', CityController::class);
    Route::get('/cities/destroy/{id}', [CityController::class, 'destroy'])->name('cities.destroy');

    // Family Status
    Route::resource('/family-status', FamilyStatusController::class);
    Route::get('/family-status/destroy/{id}', [FamilyStatusController::class, 'destroy'])->name('family-status.destroy');

    // Family Value
    Route::resource('/family-values', FamilyValueController::class);
    Route::get('/family-values/destroy/{id}', [FamilyValueController::class, 'destroy'])->name('family-values.destroy');

    // On Behalf
    Route::resource('/on-behalf', OnBehalfController::class);
    Route::get('/on-behalf/destroy/{id}', [OnBehalfController::class, 'destroy'])->name('on-behalf.destroy');

    Route::resource('marital-statuses', MaritalStatusController::class);
    Route::get('/marital-statuses/destroy/{id}', [MaritalStatusController::class, 'destroy'])->name('marital-statuses.destroy');

    // Annual Salary Range
    Route::resource('/annual-salaries', AnnualSalaryRangeyController::class);
    Route::get('/annual-salaries/destroy/{id}', [AnnualSalaryRangeyController::class, 'destroy'])->name('annual-salaries.destroy');

    // Email Templates
    Route::resource('/email-templates', EmailTemplateController::class);
    Route::post('/email-templates/update', [EmailTemplateController::class, 'update'])->name('email-templates.update');

    // Marketing
    Route::controller(NewsletterController::class)->group(function () {
        Route::get('/newsletter', 'index')->name('newsletters.index');
        Route::post('/newsletter/send', 'send')->name('newsletters.send');
        Route::post('/newsletter/test/smtp', 'testEmail')->name('test.smtp');
    });

    // Language
    Route::resource('/languages', LanguageController::class);
    Route::controller(LanguageController::class)->group(function () {
        Route::post('/languages/update_rtl_status', 'update_rtl_status')->name('languages.update_rtl_status');
        Route::post('/languages/key_value_store', 'key_value_store')->name('languages.key_value_store');
        Route::get('/languages/destroy/{id}', 'destroy')->name('languages.destroy');
    });

    // Setting
    Route::resource('/settings', SettingController::class);
    Route::controller(SettingController::class)->group(function () {
        Route::post('/settings/update', 'update')->name('settings.update');
        Route::post('/settings/activation/update', 'updateActivationSettings')->name('settings.activation.update');

        // Firebase Push Notification Setting
        Route::get('/settings/firebase/fcm', 'fcm_settings')->name('settings.fcm');
        Route::post('/settings/firebase/fcm', 'fcm_settings_update')->name('settings.fcm.update');

        Route::get('/general-settings', 'general_settings')->name('general_settings');
        Route::get('/smtp-settings', 'smtp_settings')->name('smtp_settings');

        Route::get('/payment-methods-settings', 'payment_method_settings')->name('payment_method_settings');
        Route::post('/payment_method_update', 'payment_method_update')->name('payment_method.update');

        Route::get('/third-party-settings', 'third_party_settings')->name('third_party_settings');
        Route::post('/third-party-settings/update', 'third_party_settings_update')->name('third_party_settings.update');

        Route::get('/social-media-login-settings', 'social_media_login_settings')->name('social_media_login');

        Route::get('/member-profile-sections', 'member_profile_sections_configuration')->name('member_profile_sections_configuration');

        // env Update
        Route::post('/env_key_update', 'env_key_update')->name('env_key_update.update');

        Route::get('/verification/form', 'member_verification_form')->name('member_verification_form.index');
        Route::post('/verification/form/update', 'member_verification_form_update')->name('member_verification_form.update');

        Route::get('/system/update', 'system_update')->name('system_update');
        Route::get('/system/server-status', 'system_server')->name('system_server');
    });

    Route::resource('/additional-attributes', AdditionalAttributeController::class);
    Route::post('/additional-attributes/update', [AdditionalAttributeController::class, 'update'])->name('additional-attributes.update');

    // Currency settings
    Route::resource('currencies', CurrencyController::class);
    Route::controller(CurrencyController::class)->group(function () {
        Route::post('/currency/update_currency_activation_status', 'update_currency_activation_status')->name('currency.update_currency_activation_status');
        Route::get('/currency/destroy/{id}', 'destroy')->name('currency.destroy');
    });

    // Website settings
    Route::group(['prefix' => 'website'], function () {
        Route::controller(SettingController::class)->group(function () {
            Route::get('/header_settings', 'website_header_settings')->name('website.header_settings');
            Route::get('/footer_settings', 'website_footer_settings')->name('website.footer_settings');
            Route::get('/appearances', 'website_appearances')->name('website.appearances');
        });

        Route::resource('custom-pages', PageController::class);
        Route::controller(PageController::class)->group(function () {
            Route::get('/custom-pages/edit/{id}', 'edit')->name('custom-pages.edit');
            Route::get('/custom-pages/destroy/{id}', 'destroy')->name('custom-pages.destroy');
        });
    });

    Route::resource('staffs', StaffController::class);
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staffs.destroy');

    Route::resource('roles', RoleController::class);
    Route::get('/roles/destroy/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Permission add
    Route::post('/roles/add_permission', [RoleController::class, 'add_permission'])->name('roles.permission');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');

    Route::resource('addons', AddonController::class);
    Route::post('/addons/activation', [AddonController::class, 'activation'])->name('addons.activation');

    // Uploaded files
    Route::resource('/uploaded-files', AizUploadController::class);
    Route::controller(AizUploadController::class)->group(function() {
        Route::any('/uploaded-files/file-info', 'file_info')->name('uploaded-files.info');
        Route::get('/uploaded-files/destroy/{id}', 'destroy')->name('uploaded-files.destroy');
        Route::post('/bulk-uploaded-files-delete', 'bulk_uploaded_files_delete')->name('bulk-uploaded-files-delete');
    });

    Route::get('/cache-cache', [HomeController::class, 'clearCache'])->name('cache.clear');

    // Manual Payment
    Route::resource('manual_payment_methods', ManualPaymentMethodController::class);
    Route::get('/manual_payment_methods/destroy/{id}', [ManualPaymentMethodController::class, 'destroy'])->name('manual_payment_methods.destroy');
});