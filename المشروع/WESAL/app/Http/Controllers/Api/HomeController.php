<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Utility\SubscriptionUtility;
use App\Models\Blog;
use App\Models\Package;
use App\Models\ContactUs;
use App\Models\HappyStory;
use App\Models\IgnoredUser;
use App\Models\ProfileMatch;
use Illuminate\Http\Request;
use App\Http\Resources\BlogResource;
use App\Http\Resources\MemberResource;
use App\Http\Requests\ContactUsRequest;
use App\Http\Resources\PackageResource;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\GalleryImageResource;
use App\Notifications\EmailNotification;
use App\Http\Resources\HappyStoryResource;
use App\Http\Resources\HowItWorksResource;
use Illuminate\Support\Facades\Notification;
use App\Http\Resources\MatchedProfileResource;
use App\Models\Setting;
use ArrayIterator;
use MultipleIterator;
use Laravel\Sanctum\PersonalAccessToken;

class HomeController extends Controller
{

    public function home_slider()
    {
        // Slider images
        $slider_images = array();
        $sliders = get_setting('show_homepage_slider') == 'on' && get_setting('home_slider_images') != null ?
            json_decode(get_setting('home_slider_images'), true) : [];
        foreach ($sliders as $key => $slider) {
            $slider_data = array(
                'image' => get_setting('home_slider_images_small') != null
                    ? uploaded_asset(json_decode(get_setting('home_slider_images_small'), true)[$key])
                    : uploaded_asset($slider)
            );
            $slider_images[] = $slider_data;
        }
        return $this->response_data($slider_images);
    }
    public function home_banner()
    {
        // banner
        $banner = array();
        $banner_imags = get_setting('show_home_banner1_section') == 'on' && get_setting('home_banner1_images') != null ?
            json_decode(get_setting('home_banner1_images')) : [];
        foreach ($banner_imags as $key => $value) {
            $banner_data = array(
                'link'  => json_decode(get_setting('home_banner1_links'), true)[$key],
                'image' => uploaded_asset($value)
            );
            $banner[] = $banner_data;
        }
        $data['banner'] = $banner;
        return $this->response_data($banner);
    }
    public function home_how_it_works()
    {
        // How It Works
        $how_it_works = array();
        if (get_setting('show_how_it_works_section') == 'on' && get_setting('how_it_works_steps_titles') != null) {
            $how_it_works_title = get_setting('how_it_works_title') ?? '';
            $how_it_works_sub_title = get_setting('how_it_works_sub_title') ?? '';
            $how_it_works_steps_titles = array();
            $how_it_works_steps_titles = json_decode(get_setting('how_it_works_steps_titles'));

            foreach ($how_it_works_steps_titles as $key => $how_it_works_steps_title) {
                $how_it_works_data = array(
                    'step'     => $key + 1,
                    'title'    => $how_it_works_steps_title,
                    'subtitle' => json_decode(get_setting('how_it_works_steps_sub_titles'), true)[$key],
                    'icon'     => uploaded_asset(json_decode(get_setting('how_it_works_steps_icons'), true)[$key]),
                );
                $how_it_works[] = $how_it_works_data;
            }

            return HowItWorksResource::collection($how_it_works)->additional([
                'how_it_works_title' => $how_it_works_title,
                'how_it_works_sub_title' => $how_it_works_sub_title
            ]);
        }
        return $this->failure_data($how_it_works);
    }
    public function home_trusted_by_millions()
    {
        // trusted by millions
        $trusted_by_millions = array();
        $homepage_best_features = get_setting('show_trusted_by_millions_section') == 'on' ?
            json_decode(get_setting('homepage_best_features')) : [];
        foreach ($homepage_best_features as $key => $homepage_best_feature) {
            $homepage_best_feature_data = array(
                'title' => $homepage_best_feature,
                'icon'  => uploaded_asset(json_decode(get_setting('homepage_best_features_icons'), true)[$key]),
            );
            $trusted_by_millions[] = $homepage_best_feature_data;
        }
        return $this->response_data($trusted_by_millions);
    }
    public function home_happy_stories()
    {
        // Happy Stories
        $stories = HappyStory::where('approved', '1')
            ->latest()
            ->limit(get_setting('max_happy_story_show_homepage'))
            ->get();
        $happy_stories = get_setting('show_happy_story_section') == 'on' ? (HappyStoryResource::collection($stories)) : [];
        return $this->response_data($happy_stories);
    }
    public function home_packages()
    {
        // packages
        $packages = get_setting('show_homapege_package_section') == 'on' ? (PackageResource::collection(Package::where('active', '1')->get())) : [];
        return $this->response_data($packages);
    }
    public function home_reviews()
    {
        // reviews
        $reviews = array();
        $homepage_reviews = get_setting('show_homepage_review_section') == 'on' && get_setting('homepage_reviews') != null ?
            json_decode(get_setting('homepage_reviews')) : [];
        if (count($homepage_reviews) > 0) {
            $reviews['bg_image'] = uploaded_asset(get_setting('homepage_review_section_background_image'));
            $reviews['items'] = array();
            foreach ($homepage_reviews as $key => $review) {
                $review_data = array(
                    'image'  => uploaded_asset(json_decode(get_setting('homepage_reviewers_images'), true)[$key]) ?? static_asset('assets/img/placeholder.jpg'),
                    'review' => $review
                );
                $reviews['items'][] = $review_data;
            }
        }

        return  $reviews ? $this->response_data($reviews) : $this->failure_data(null);
    }
    public function home_blogs()
    {
        // blogs
        $blogs = get_setting('show_blog_section') == 'on' ?
            (BlogResource::collection(Blog::latest()->active()->limit(get_setting('max_blog_show_homepage'))->get())) : [];
        return $this->response_data($blogs);
    }
    public function home_premium_members()
    {
        $token = PersonalAccessToken::findToken(request()->bearerToken());
        $user = null;
        if ($token) {
            $user = $token->tokenable;
        }
        // new members & premium members
        $members = User::where('user_type', 'member')
                        ->where('blocked', 0)
            ->where('deactivated', 0);

        if ($token && $user->user_type == 'member') {
            $members = $members->where('id', '!=', $user->id)
                ->whereIn("id", function ($query) use ($user) {
                    $query->select('user_id')
                        ->from('members')
                        ->where('gender', '!=', $user->member->gender);
                });

            $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $members = $members->whereNotIn('id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $members = $members->whereNotIn('id', $ignored_by_ids);
            }
        }

        $premium_members = SubscriptionUtility::constrainCurrentPaidMembers(clone $members)->inRandomOrder()->limit(10)->get();
        $premium_members = MemberResource::collection($premium_members);

        return get_setting('show_premium_member_section') == 'on' ? $this->response_data($premium_members) : $this->response_data([]);
    }
    public function home_new_members()
    {
        $token = PersonalAccessToken::findToken(request()->bearerToken());
        $user = null;
        if ($token) {
            $user = $token->tokenable;
        }
        // new members & premium members
        $members = User::where('user_type', 'member')
                        ->where('blocked', 0)
            ->where('deactivated', 0);

        if ($user && $user->user_type == 'member') {
            $members = $members->where('id', '!=', $user->id)
                ->whereIn("id", function ($query) use ($user) {
                    $query->select('user_id')
                        ->from('members')
                        ->where('gender', '!=', $user->member->gender);
                });

            $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $members = $members->whereNotIn('id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $members = $members->whereNotIn('id', $ignored_by_ids);
            }
        }

        $new_members = $members->orderBy('id', 'desc')->limit(get_setting('max_new_member_show_homepage'))->get()->shuffle();
        $new_members = MemberResource::collection($new_members);

        return get_setting('show_new_member_section') == 'on' ? $this->response_data($new_members) : $this->response_data([]);
    }

    public function home()
    {
        // Slider images
        $slider_images = array();
        $sliders = get_setting('show_homepage_slider') == 'on' && get_setting('home_slider_images') != null ?
            json_decode(get_setting('home_slider_images'), true) : [];
        foreach ($sliders as $key => $slider) {
            $slider_data = array(
                'image' => uploaded_asset($slider)
            );
            $slider_images[] = $slider_data;
        }
        $data['slider_images'] = $slider_images;

        // new members & premium members
        $members = User::where('user_type', 'member')
                        ->where('blocked', 0)
            ->where('deactivated', 0);
            
        if (auth()->user() && auth()->user()->user_type == 'member') {
            $members = $members->where('id', '!=', auth()->user()->id)
                ->whereIn("id", function ($query) {
                    $query->select('user_id')
                        ->from('members')
                        ->where('gender', '!=', auth()->user()->member->gender);
                });

            $ignored_to = IgnoredUser::where('ignored_by', auth()->user()->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $members = $members->whereNotIn('id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', auth()->user()->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $members = $members->whereNotIn('id', $ignored_by_ids);
            }
        }

        $premium_members = $members;
        $new_members = $members;

        $new_members = $new_members->orderBy('id', 'desc')->limit(get_setting('max_new_member_show_homepage'))->get()->shuffle();
        $premium_members = SubscriptionUtility::constrainCurrentPaidMembers(clone $premium_members)->inRandomOrder()->limit(10)->get();
        $data['new_members'] = MemberResource::collection($new_members);
        $data['premium_members'] = MemberResource::collection($premium_members);

        // banner
        $banner = array();
        $banner_imags = get_setting('show_home_banner1_section') == 'on' && get_setting('home_banner1_images') != null ?
            json_decode(get_setting('home_banner1_images')) : [];
        foreach ($banner_imags as $key => $value) {
            $banner_data = array(
                'link'  => json_decode(get_setting('home_banner1_links'), true)[$key],
                'photo' => uploaded_asset($value)
            );
            $banner[] = $banner_data;
        }
        $data['banner'] = $banner;

        // How It Works
        $how_it_works = array();
        $how_it_works_steps_titles = get_setting('show_how_it_works_section') == 'on' && get_setting('how_it_works_steps_titles') != null ?
            json_decode(get_setting('how_it_works_steps_titles')) : [];
        if (count($how_it_works_steps_titles) > 0) {
            $how_it_works['how_it_works_title'] = get_setting('how_it_works_title');
            $how_it_works['how_it_works_sub_title'] = get_setting('how_it_works_sub_title');
            $how_it_works['items'] = array();
            foreach ($how_it_works_steps_titles as $key => $how_it_works_steps_title) {
                $how_it_works_data = array(
                    'step'     => $key + 1,
                    'title'    => $how_it_works_steps_title,
                    'subtitle' => json_decode(get_setting('how_it_works_steps_sub_titles'), true)[$key],
                    'icon'     => uploaded_asset(json_decode(get_setting('how_it_works_steps_icons'), true)[$key]),
                );
                $how_it_works['items'][] = $how_it_works_data;
            }
        }
        $data['how_it_works'] = $how_it_works;

        // trusted by millions
        $trusted_by_millions = array();
        $homepage_best_features = get_setting('show_trusted_by_millions_section') == 'on' ?
            json_decode(get_setting('homepage_best_features')) : [];
        foreach ($homepage_best_features as $key => $homepage_best_feature) {
            $homepage_best_feature_data = array(
                'title' => $homepage_best_feature,
                'icon'  => uploaded_asset(json_decode(get_setting('homepage_best_features_icons'), true)[$key]),
            );
            $trusted_by_millions[] = $homepage_best_feature_data;
        }
        $data['trusted_by_millions'] = $trusted_by_millions;

        // Happy Stories
        $stories = HappyStory::where('approved', '1')
            ->latest()
            ->limit(get_setting('max_happy_story_show_homepage'))
            ->get();
        $happy_stories = get_setting('show_happy_story_section') == 'on' ? (HappyStoryResource::collection($stories)) : [];
        $data['happy_stories'] = $happy_stories;

        // packages
        $packages = get_setting('show_homapege_package_section') == 'on' ? (PackageResource::collection(Package::where('active', '1')->get())) : [];
        $data['packages'] = $packages;

        // reviews
        $reviews = array();
        $homepage_reviews = get_setting('show_homepage_review_section') == 'on' && get_setting('homepage_reviews') != null ?
            json_decode(get_setting('homepage_reviews')) : [];
        if (count($homepage_reviews) > 0) {
            $reviews['bg_image'] = uploaded_asset(get_setting('homepage_review_section_background_image'));
            $reviews['items'] = array();
            foreach ($homepage_reviews as $key => $review) {
                $review_data = array(
                    'image'  => uploaded_asset(json_decode(get_setting('homepage_reviewers_images'), true)[$key]),
                    'review' => $review
                );
                $reviews['items'][] = $review_data;
            }
        }
        $data['reviews'] = $reviews;

        // blogs
        $blogs = get_setting('show_homapege_package_section') == 'on' ?
            (BlogResource::collection(Blog::latest()->active()->limit(get_setting('max_blog_show_homepage'))->get())) : [];
        $data['blogs'] = $blogs;

        return $this->response_data($data);
    }

    public function home_with_login()
    {
        $members = User::query();
        $members->where('user_type', 'member')
                        ->where('blocked', 0)
            ->where('deactivated', 0);

        if (auth()->user() && auth()->user()->user_type == 'member') {
            $members->where('id', '!=', auth()->user()->id)
                ->whereIn("id", function ($query) {
                    $query->select('user_id')
                        ->from('members')
                        ->where('gender', '!=', auth()->user()->member->gender);
                });

            $ignored_to = IgnoredUser::where('ignored_by', auth()->user()->id)->pluck('user_id')->toArray();
            if (count($ignored_to) > 0) {
                $members->whereNotIn('id', $ignored_to);
            }

            $ignored_by_ids = IgnoredUser::where('user_id', auth()->user()->id)->pluck('ignored_by')->toArray();
            if (count($ignored_by_ids) > 0) {
                $members->whereNotIn('id', $ignored_by_ids);
            }
        }

        $members = $members->orderBy('id', 'desc')->limit(15)->get()->shuffle();

        return MemberResource::collection($members)->additional([
            'result' => true
        ]);
    }
    // app_info
    public function app_info()
    {
        $how_it_works_steps = json_decode(get_setting('how_it_works_steps_titles'));
        $step = 1;
        foreach ($how_it_works_steps as $key => $how_it_works_steps_title) {
            $steps[] = $step++;
            $how_it_works_steps_titles[] = $how_it_works_steps_title;
            $how_it_works_steps_sub_titles[] = json_decode(get_setting('how_it_works_steps_sub_titles'), true)[$key];
            $how_it_works_steps_icons[] = uploaded_asset(json_decode(get_setting('how_it_works_steps_icons'), true)[$key]);
        }

        #Combine multiple arrays into single array
        $keys = array("steps", "how_it_works_steps_titles", "how_it_works_steps_sub_titles", "how_it_works_steps_icons");
        $how_it_works = array();
        $mi = new MultipleIterator();
        $mi->attachIterator(new ArrayIterator($steps));
        $mi->attachIterator(new ArrayIterator($how_it_works_steps_titles));
        $mi->attachIterator(new ArrayIterator($how_it_works_steps_sub_titles));
        $mi->attachIterator(new ArrayIterator($how_it_works_steps_icons));

        foreach ($mi as $value) {
            $how_it_works[] = array_combine($keys, $value);
        }

        $data['website_name'] = get_setting('website_name');
        $data['system_logo'] = uploaded_asset(get_setting('system_logo'));

        $data['how_it_works_title'] = get_setting('how_it_works_title');
        $data['how_it_works_sub_title'] = get_setting('how_it_works_sub_title');
        $data['how_it_works'] = $how_it_works;
        return $this->response_data($data);
    }
    //Member Dashboard 
    public function member_dashboard()
    {
        $user = auth()->user();
        SubscriptionUtility::deactivateIfInvalid($user);
        $user->refresh();

        if($user->blocked == 1){
            return response()->json([
                'result' => false,
                'status' => 'blocked',
                'message' => translate('user is banned')
            ]);
        }

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

    public function addon_check()
    {
        $addons = array();
        $addons['otp_system'] = addon_activation('otp_system') ? true : false;
        $addons['referral_system'] = addon_activation('referral_system') ? true : false;
        $addons['support_tickets'] = addon_activation('support_tickets')  ? true : false;

        return $this->response_data($addons);
    }

    public function feature_check()
    {
        /*
        $features['google_login']             = get_setting('google_login_activation') == 1 ? true : false;
        $features['facebook_login']           = get_setting('facebook_login_activation') == 1 ? true : false;
        $features['twitter_login']            = get_setting('twitter_login_activation') == 1 ? true : false;
        $features['apple_login']              = get_setting('apple_login_activation') == 1 ? true : false;
        $features['force_https']              = get_setting('FORCE_HTTPS') == 1 ? true : false;
        $features['maintenance_mode']         = get_setting('maintenance_mode') == 1 ? true : false;
        $features['wallet_system']            = get_setting('wallet_system') == 1 ? true : false;
        $features['full_profile_show']        = get_setting('full_profile_show_according_to_membership') == 1 ? true : false;
        $features['profile_picture_approval'] = get_setting('profile_picture_approval_by_admin') == 1 ? true : false;
        $features['gallery_image_privacy']    = get_setting('gallery_image_privacy');
        $features['profile_picture_privacy']  = get_setting('profile_picture_privacy');
        $features['email_verification']       = get_setting('email_verification') == 1 ? true : false;
        $features['member_verification']      = get_setting('member_verification') == 1 ? true : false;
        $features['member_min_age']           = get_setting('member_min_age') ?? "";
        $features['default_currency']         = \App\Models\Currency::findOrFail(get_setting('system_default_currency'))->symbol;
        */
        $features = Setting::all();
        $temp_array = [];
        foreach ($features as $feature) {
            $new_array[$feature->type] = $feature->value;
        }

        // The mobile app does not require email verification or admin approval.
        // Keep the underlying website/admin settings untouched.
        $new_array['email_verification'] = 0;
        $new_array['member_verification'] = 0;

        return $this->response_data($new_array);
    }

    public function contact_us(ContactUsRequest $request)
    {
        try {
            ContactUs::create($request->except('g-recaptcha-response'));
            $users = User::where('user_type', 'admin')->get();
            Notification::send($users, new EmailNotification($request->subject, $request->description));
            return $this->success_message('Your query has been sent successfully');
        } catch (\Throwable $th) {
            return $this->failure_message('Something went wrong');
        }
    }

}
