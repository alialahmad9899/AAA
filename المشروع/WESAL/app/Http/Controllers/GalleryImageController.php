<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GalleryImage;
use App\Models\Member;
use App\Utility\MemberUtility;
use App\Utility\SubscriptionUtility;
use Illuminate\Support\Facades\DB;
use Auth;
use Validator;

class GalleryImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $gallery_images = GalleryImage::where('user_id',Auth::user()->id)->latest()->paginate(10);
        return view('frontend.member.gallery_image.index', compact('gallery_images'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        $isFemale = MemberUtility::is_female($user);
        $member = Member::where('user_id', $user->id)->first();
        $quota = (int) ($member->remaining_photo_gallery ?? 0);
        if ($isFemale || (SubscriptionUtility::hasActivePaidPackage($user) && ($quota === -1 || $quota > 0))) {
            return view('frontend.member.gallery_image.create');
        }
        flash(translate('Your active package or gallery-photo quota is not available. Please update your package.'))->error();
        return redirect()->route('packages');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'gallery_image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $user = Auth::user();
        try {
            $created = SubscriptionUtility::withQuota(
                (int) $user->id,
                'remaining_photo_gallery',
                function ($lockedUser, $member) use ($request) {
                    $file = $request->file('gallery_image');
                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
                    $destination = public_path('uploads/all');
                    if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
                        throw new \RuntimeException('Unable to create gallery upload directory.');
                    }
                    $file->move($destination, $filename);

                    $upload = new \App\Models\Upload();
                    $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $upload->file_name = 'uploads/all/' . $filename;
                    $upload->user_id = $lockedUser->id;
                    $upload->extension = $extension;
                    $upload->type = 'image';
                    $upload->file_size = filesize($destination . DIRECTORY_SEPARATOR . $filename);
                    $upload->save();

                    $galleryImage = new GalleryImage();
                    $galleryImage->user_id = $lockedUser->id;
                    $galleryImage->image = $upload->id;
                    $galleryImage->save();
                    return $galleryImage;
                }
            );

            if (!$created) {
                flash(translate('Your active package or remaining gallery-photo quota is not available.'))->error();
                return back();
            }
            flash(translate('Gallery image uploaded successfully.'))->success();
            return redirect()->route('gallery-image.index');
        } catch (\Throwable $exception) {
            \Log::error('Gallery image upload failed.', ['user_id' => $user->id, 'message' => $exception->getMessage()]);
            flash(translate('Unable to upload the gallery image. Please try again.'))->error();
            return back();
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
        if(GalleryImage::destroy($id)){
            flash(translate('Image deleted successfully'))->success();
            return redirect()->route('gallery-image.index');
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }
}