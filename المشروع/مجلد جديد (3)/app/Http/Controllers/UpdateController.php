<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Translation;
use App\Models\Upload;
use Illuminate\Http\Request;
use DB;
use Artisan;
use ZipArchive;

class UpdateController extends Controller
{
    public function step0(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }

        $current_version = get_setting('current_version');
        if (version_compare($current_version, '4.6', '<')) {
            flash(translate('Could not update. Please check the compatible version'))->error();
            return back();
        }


        if ($request->has('update_zip')) {
            if (class_exists('ZipArchive')) {
                // Create update directory.
                $dir = 'updates';
                if (!is_dir($dir))
                    mkdir($dir, 0777, true);

                $path = Upload::findOrFail($request->update_zip)->file_name;

                //Unzip uploaded update file and remove zip file.
                $zip = new ZipArchive;
                $res = $zip->open(base_path('public/' . $path));

                if ($res === true) {
                    $res = $zip->extractTo(base_path());
                    $zip->close();
                } else {
                    flash(translate('Could not open the updates zip file.'))->error();
                    return back();
                }

                if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
                    return redirect()->route('update.step2');
                }
                return redirect()->route('update.step1');
            } else {
                flash(translate('Please enable ZipArchive extension.'))->error();
            }
        } else {
            return view('update.step0');
        }
    }

    public function step1()
    {
        return view('update.step1');
    }

    public function purchase_code(Request $request)
    {
        if (\App\Utility\MemberUtility::create_initial_member($request->purchase_code) == false) {
            flash("Sorry! The purchase code you have provided is not valid.")->error();
            return back();
        }
        // if ($request->system_key == null) {
        //     flash("Sorry! The System Key required")->error();
        //     return back();
        // }

        $businessSetting = Setting::where('type', 'purchase_code')->first();
        if ($businessSetting) {
            $businessSetting->value = $request->purchase_code;
            $businessSetting->save();
        } else {
            $business_settings = new Setting;
            $business_settings->type = 'purchase_code';
            $business_settings->value = $request->purchase_code;
            $business_settings->save();
        }

        //$this->writeEnvironmentFile('SYSTEM_KEY', $request->system_key);

        return redirect()->route('update.step2');
    }

    public function step2()
    {
        $versions = [
            '3.3' => 'v33.sql',
            '3.4' => 'v34.sql',
            '3.5' => 'v35.sql',
            '3.6' => 'v36.sql',
            '3.7' => 'v37.sql',
            '4.0' => 'v40.sql',
            '4.1' => 'v41.sql',
            '4.2' => 'v42.sql',
            '4.3' => 'v43.sql',
            '4.4' => 'v44.sql',
            '4.5' => 'v45.sql',
            '4.6' => 'v46.sql',
            '4.7' => 'v47.sql',
            '4.8' => 'v48.sql',
            '4.9' => 'v49.sql',
            '5.0' => 'v50.sql',
            '5.1' => 'v51.sql',
            '5.2' => 'v52.sql',
            '5.3' => 'v53.sql',
            '5.4' => 'v54.sql',
            '5.5' => 'v55.sql',
            '5.5.1' => 'v551.sql',
        ];

        $keys = array_keys($versions);
        $current_version = (get_setting('current_version') != null) ? get_setting('current_version') : '4.6';

        // Validate current version
        if (!in_array($current_version, $keys)) {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            copy(
                base_path('app/Providers/RouteServiceProvider.txt'),
                base_path('app/Providers/RouteServiceProvider.php')
            );

            flash(translate('Could not update. Please check the compatible version'))->error();
            return redirect('/');
        }

        $initial_index = array_search($current_version, $keys) + 1;

        for ($i = $initial_index; $i < count($keys); $i++) {
            $sql_path = base_path('sqlupdates/' . $versions[$keys[$i]]);
            DB::unprepared(file_get_contents($sql_path));
        }

        // Done — go to step 3
        return redirect()->route('update.step3');
    }

    public function step3()
    {
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier      = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);

        return view('update.done');
    }

    public function convertTrasnalations()
    {
        foreach (Translation::all() as $translation) {
            $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($translation->lang_key)));
            $translation->lang_key = $lang_key;
            $translation->save();
        }
    }
}
