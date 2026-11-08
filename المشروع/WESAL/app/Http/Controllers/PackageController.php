<?php

namespace App\Http\Controllers;

use App\Models\ManualPaymentMethod;
use App\Models\Member;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Services\PackagePresentation;
use Redirect;
use Validator;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_packages'])->only('index');
        $this->middleware(['permission:add_package'])->only(['create', 'store']);
        $this->middleware(['permission:edit_package'])->only(['edit', 'update', 'update_status']);
        $this->middleware(['permission:delete_package'])->only('destroy');

        $this->package_rules = [
            'name'               => ['required', 'max:255'],
            'price'              => ['required'],
            'express_interest'   => ['required'],
            'photo_gallery'      => ['required'],
            'contact'            => ['required'],
            'profile_viewers_view'=> ['required'],
            'profile_image_view' => ['required'],
            'gallery_image_view' => ['required'],
            'validity'           => ['required', 'integer', 'min:1', 'max:3650'],
            'message_profile_quota' => ['required'],
        ];

        $this->package_messages = [
            'name.required'              => translate('Name is required'),
            'name.max'                   => translate('Max 255 characters'),
            'price.required'             => translate('Package Price is required'),
            'express_interest.required'  => translate('No. of Express Interest is required'),
            'photo_gallery.required'     => translate('No. of Photo gallery is required'),
            'contact.required'           => translate('No. of Contact View is required'),
            'contact.profile_viewers_view'=> translate('No. of Profile Viewer view is required'),
            'profile_image_view.required' => translate('No. of Profile Image View is required'),
            'gallery_image_view.required' => translate('No. of Gallery Image View View is required'),
            'validity.required'          => translate('Package Validity day is required'),
            'validity.integer'           => translate('Package validity must be a whole number of days'),
            'validity.min'               => translate('Package validity must be at least one day'),
            'validity.max'               => translate('Package validity cannot exceed 3650 days'),
            'message_profile_quota.required' => translate('Message Profile Quota is required'),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // The app display order lives in the central presentation catalogue.
        $allPackages = Package::all()->sort(function ($left, $right) {
            $leftOrder = PackagePresentation::get((int) $left->id)['sort_order'];
            $rightOrder = PackagePresentation::get((int) $right->id)['sort_order'];
            return $leftOrder <=> $rightOrder ?: ((int) $left->id <=> (int) $right->id);
        })->values();

        foreach ($allPackages as $package) {
            $presentation = PackagePresentation::get((int) $package->id);
            $package->display_order = $presentation['sort_order'];
            $package->display_description = $presentation['description_ar'];
        }

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $packages = new LengthAwarePaginator(
            $allPackages->forPage($currentPage, $perPage)->values(),
            $allPackages->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('admin.premium_packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.premium_packages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = array_merge($this->package_rules, [
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:1500'],
            'description_en' => ['nullable', 'string', 'max:1500'],
            'features_ar' => ['nullable', 'string', 'max:5000'],
            'features_en' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $messages = $this->package_messages;
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $package                    = new Package;
        $package->name              = $request->name;
        $package->price             = filter_min_value($request->price);
        $package->express_interest  = filter_min_value($request->express_interest);
        $package->photo_gallery     = filter_min_value($request->photo_gallery);
        $package->contact           = filter_min_value($request->contact);
        $package->profile_viewers_view= filter_min_value($request->profile_viewers_view);
        $package->profile_image_view = filter_min_value($request->profile_image_view);
        $package->gallery_image_view = filter_min_value($request->gallery_image_view);
        if ($request->auto_profile_match != null) {
            $package->auto_profile_match = 1;
        } else {
            $package->auto_profile_match = 0;
        }
        $package->validity           = filter_min_value($request->validity);
        $package->message_profile_quota = $request->message_profile_quota;
        $package->image              = $request->package_image;

        try {
            DB::transaction(function () use ($package, $request) {
                $package->save();
                PackagePresentation::save((int) $package->id, [
                    'name_en' => $request->input('name_en'),
                    'description_ar' => $request->input('description_ar'),
                    'description_en' => $request->input('description_en'),
                    'features_ar' => $request->input('features_ar'),
                    'features_en' => $request->input('features_en'),
                    'sort_order' => $request->input('sort_order'),
                ]);
            });

            flash(translate('New Package has been added successfully'))->success();
            return redirect()->route('packages.index');
        } catch (\Throwable $exception) {
            report($exception);
            flash(translate('Sorry! Something went wrong.'))->error();
            return back()->withInput();
        }
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $package = Package::findOrFail(decrypt($id));
        $presentation = PackagePresentation::get((int) $package->id);
        return view('admin.premium_packages.edit', compact('package', 'presentation'));
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
        $rules = array_merge($this->package_rules, [
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:1500'],
            'description_en' => ['nullable', 'string', 'max:1500'],
            'features_ar' => ['nullable', 'string', 'max:5000'],
            'features_en' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);
        $messages = $this->package_messages;
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Sorry! Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $package                    = Package::findOrFail($id);
        $package->name              = $request->name;
        $package->price             = filter_min_value($request->price);
        $package->express_interest  = filter_min_value($request->express_interest);
        $package->photo_gallery     = filter_min_value($request->photo_gallery);
        $package->contact           = filter_min_value($request->contact);
        $package->profile_viewers_view= filter_min_value($request->profile_viewers_view);
        $package->profile_image_view = filter_min_value($request->profile_image_view);
        $package->gallery_image_view = filter_min_value($request->gallery_image_view);
        if ($request->auto_profile_match != null) {
            $package->auto_profile_match = 1;
        } else {
            $package->auto_profile_match = 0;
        }
        $package->validity           = filter_min_value($request->validity);
        $package->message_profile_quota = $request->message_profile_quota;
        $package->image              = $request->package_image;
        
        try {
            DB::transaction(function () use ($package, $request) {
                $package->save();
                PackagePresentation::save((int) $package->id, [
                    'name_en' => $request->input('name_en'),
                    'description_ar' => $request->input('description_ar'),
                    'description_en' => $request->input('description_en'),
                    'sort_order' => $request->input('sort_order'),
                ]);
            });

            flash(translate('Package info has been updated successfully'))->success();
            return redirect()->route('packages.index');
        } catch (\Throwable $exception) {
            report($exception);
            flash(translate('Sorry! Something went wrong.'))->error();
            return back()->withInput();
        }
    }

    // Update Package Activate status
    public function update_status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:packages,id'],
            'status' => ['required', 'in:0,1'],
        ]);

        $package = Package::findOrFail((int) $validated['id']);
        $package->active = (int) $validated['status'];

        if (!$package->save()) {
            return response()->json([
                'result' => false,
                'message' => 'تعذر حفظ حالة الباقة.',
            ], 500);
        }

        return response()->json([
            'result' => true,
            'id' => (int) $package->id,
            'active' => (int) $package->active,
            'message' => $package->active
                ? 'تم إظهار الباقة في التطبيق.'
                : 'تم إخفاء الباقة من التطبيق.',
        ])->header('Cache-Control', 'no-store, private, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Package::destroy($id)) {
            PackagePresentation::forget((int) $id);
            flash(translate('Package info has been deleted successfully'))->success();
            return redirect()->route('packages.index');
        } else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }

    public function select_package()
    {
        $packages = Package::query()->where('active', 1)->where('price', '>', 0)->where('validity', '>', 0)->get();
        return view('frontend.package.packages', compact('packages'));
    }

    public function package_payemnt_methods($id)
    {
        $manual_payments = ManualPaymentMethod::all();
        $package = Package::query()->where('active', 1)->where('price', '>', 0)->where('validity', '>', 0)->find(decrypt($id));
        return view('frontend.package.select_payemnt_method', compact('package', 'manual_payments'));
    }

}