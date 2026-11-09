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
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            abort_unless($request->user() && $request->user()->user_type === 'admin', 403);
            return $next($request);
        });
    }

    public function step0(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('This action is disabled in demo mode'))->error();
            return back();
        }

        $currentVersion = get_setting('current_version');
        if (version_compare($currentVersion, '4.6', '<')) {
            flash(translate('Could not update. Please check the compatible version'))->error();
            return back();
        }

        if (!$request->has('update_zip')) {
            return view('update.step0');
        }

        $validated = $request->validate([
            'update_zip' => 'required|integer|exists:uploads,id',
        ]);

        if (!class_exists(ZipArchive::class)) {
            flash(translate('Please enable ZipArchive extension.'))->error();
            return back();
        }

        // The update archive must have been uploaded by this authenticated admin.
        $upload = Upload::whereKey($validated['update_zip'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $publicRoot = realpath(public_path());
        $archivePath = realpath(base_path('public/' . ltrim($upload->file_name, '/\\')));
        if (!$publicRoot || !$archivePath
            || !str_starts_with($archivePath, $publicRoot . DIRECTORY_SEPARATOR)
            || !is_file($archivePath)) {
            abort(404);
        }

        $zip = new ZipArchive();
        $result = $zip->open($archivePath);
        if ($result !== true) {
            flash(translate('Could not open the updates zip file.'))->error();
            return back();
        }

        // Reject absolute paths, traversal entries, and Unix symlinks before extraction.
        $safeArchive = true;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($index));
            $segments = explode('/', $entry);

            if ($entry === ''
                || str_starts_with($entry, '/')
                || preg_match('/^[A-Za-z]:/', $entry)
                || in_array('..', $segments, true)
                || str_contains($entry, "\0")) {
                $safeArchive = false;
                break;
            }

            $opsys = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($index, $opsys, $attributes)
                && $opsys === 3) {
                $unixMode = ($attributes >> 16) & 0170000;
                if ($unixMode === 0120000) {
                    $safeArchive = false;
                    break;
                }
            }
        }

        if (!$safeArchive) {
            $zip->close();
            flash(translate('The update archive contains an unsafe file path.'))->error();
            return back();
        }

        $extracted = $zip->extractTo(base_path());
        $zip->close();

        if (!$extracted) {
            flash(translate('Could not extract the updates zip file.'))->error();
            return back();
        }

        if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
            return $this->step2();
        }

        return redirect()->route('update.step1');
    }

    public function step1()
    {
        return view('update.step1');
    }

    public function purchase_code(Request $request)
    {
        $validated = $request->validate([
            'purchase_code' => 'required|string|max:255',
        ]);

        if (\App\Utility\MemberUtility::create_initial_member($validated['purchase_code']) === false) {
            flash("Sorry! The purchase code you have provided is not valid.")->error();
            return back();
        }

        $businessSetting = Setting::where('type', 'purchase_code')->first();
        if ($businessSetting) {
            $businessSetting->value = $validated['purchase_code'];
            $businessSetting->save();
        } else {
            $businessSetting = new Setting();
            $businessSetting->type = 'purchase_code';
            $businessSetting->value = $validated['purchase_code'];
            $businessSetting->save();
        }

        // Continue through the protected POST workflow in the same request.
        return $this->step2();
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
        $currentVersion = get_setting('current_version') ?: '4.6';

        if (!in_array($currentVersion, $keys, true)) {
            flash(translate('Could not update. Please check the compatible version'))->error();
            return redirect()->route('admin.dashboard');
        }

        $initialIndex = array_search($currentVersion, $keys, true) + 1;
        for ($index = $initialIndex; $index < count($keys); $index++) {
            $sqlPath = base_path('sqlupdates/' . $versions[$keys[$index]]);
            if (!is_file($sqlPath) || !is_readable($sqlPath)) {
                \Log::error('System update SQL file is missing or unreadable.', [
                    'file' => basename($sqlPath),
                ]);
                flash(translate('An update file is missing. Please contact support.'))->error();
                return redirect()->route('admin.dashboard');
            }

            DB::unprepared(file_get_contents($sqlPath));
        }

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        // Do not overwrite the customized RouteServiceProvider from a legacy .txt copy.
        return redirect()->route('update.step3');
    }

    public function step3()
    {
        // Read-only completion screen. All update actions run in the protected POST above.
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
