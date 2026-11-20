<?php

namespace App\Http\Controllers;

use Symfony\Component\Console\Input\Input;
use Illuminate\Validation\Rule;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Validator;
use Redirect;
use Cache;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $rules = array();
    private $messages = array();

    public function __construct()
    {
        $this->middleware(['permission:show_languages'])->only('index');
        $this->middleware(['permission:edit_languages'])->only('edit');
        $this->middleware(['permission:translate_languages'])->only('show');
        $this->middleware(['permission:change_default_language'])->only('show');
        $this->middleware(['permission:delete_languages'])->only('destroy');

        $this->rules = [
            'name' => ['required','unique:languages,name','max:100'],
        ];

        $this->messages = [
            'name.required'     => translate('Name is required'),
            'name.unique'       => translate('Name must be unique'),
            'name.max'          => translate('Name must less than :max characters'),
        ];
    }


    public function index()
    {
        $per_page   = 5;
        $languages  = Language::paginate($per_page);
        return view('admin.settings.languages.index', compact('languages','per_page'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = $this->rules;
        $messages = $this->messages;
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            flash(translate('Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $language       = new Language;
        $language->name = $request->name;
        $language->code = $request->code;
        if($language->save()){

            flash(translate('Language has been inserted successfully'))->success();
            return redirect()->route('languages.index');
        }
        else{
            flash(translate('Something went wrong'))->error();
            return back();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $sort_search  = null;
        $language     = Language::findOrFail(decrypt($id));
        $lang_keys    = Translation::where('lang', env('DEFAULT_LANGUAGE', 'en'));
        if ($request->has('search')){
            $sort_search  = $request->search;
            $lang_keys    = $lang_keys->where('lang_key', 'like', '%'.$sort_search.'%');
        }
        $lang_keys = $lang_keys->paginate(50);
        return view('admin.settings.languages.translate', compact('language','lang_keys','sort_search'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     public function edit($id)
     {
        $language = Language::findOrFail($id);
        return view('admin.settings.languages.edit', compact('language'));
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
         $language       = Language::findOrFail($id);
         $language->name = $request->name;
         $language->code = $request->code;
         if($language->save()){
             flash(translate('Language has been updated successfully'))->success();
             return redirect()->route('languages.index');
         }
         else{
             flash(translate('Something went wrong'))->error();
             return back();
         }
     }

    public function update_rtl_status(Request $request)
    {
        $language = Language::findOrFail($request->id);
        $language->rtl = $request->status;
        if($language->save()){
            flash(translate('RTL status updated successfully'))->success();
            return 1;
        }
        return 0;
    }


    public function key_value_store(Request $request)
    {
        $language = Language::findOrFail($request->id);
        foreach ($request->values as $key => $value) {
            $translation_def = Translation::where('lang_key', $key)->where('lang', $language->code)->latest()->first();
            if($translation_def == null){
                $translation_def = new Translation;
                $translation_def->lang = $language->code;
                $translation_def->lang_key = $key;
                $translation_def->lang_value = $value;
                $translation_def->save();
            }
            else {
                $translation_def->lang_value = $value;
                $translation_def->save();
            }
        }
        Cache::forget('translations-'.$language->code);
        flash(translate('Translations updated for ').$language->name)->success();
        return back();
    }

    public function changeLanguage(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['ar', 'en'])],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $locale = $validated['locale'];
        $request->session()->put('website_locale', $locale);
        $request->session()->put('locale', $locale);
        // Save immediately so the following redirect request sees the choice.
        $request->session()->save();
        App::setLocale($locale);

        // Only redirect to a local path. The switcher sends the current
        // request URI so the visitor stays on the same page after switching.
        $target = (string) ($validated['redirect_to'] ?? $request->headers->get('referer', '/'));
        $path = parse_url($target, PHP_URL_PATH);
        $query = parse_url($target, PHP_URL_QUERY);

        if (!is_string($path) || $path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
            $path = '/';
            $query = null;
        }

        $redirectTo = $path . (is_string($query) && $query !== '' ? '?' . $query : '');

        $languageCookie = cookie(
            'wesal_website_locale',
            $locale,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        return redirect()->to($redirectTo)->withCookie($languageCookie);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $language = Language::findOrFail($id);
        if (env('DEFAULT_LANGUAGE') == $language->code) {
            flash(translate('Default language can not be deleted'))->error();
        }
        else {
            if($language->code == session()->get('locale')){
                session()->put('locale', env('DEFAULT_LANGUAGE'));
            }
            Language::destroy($id);
            flash(translate('Language has been deleted successfully'))->success();
        }
        return redirect()->route('languages.index');
    }
}
