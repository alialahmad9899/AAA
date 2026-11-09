<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Response;
use Auth;
use Storage;
use Image;

class AizUploadController extends Controller
{

    public function __construct()
    {
        // All uploader endpoints read or mutate user-owned files.
        $this->middleware('auth');
        $this->middleware(['permission:show_uploaded_files'])->only('index');
    }

    public function index(Request $request){

        $all_uploads = Upload::query();
        $search = null;
        $sort_by = null;

        if ($request->search != null) {
            $search = $request->search;
            $all_uploads->where('file_original_name', 'like', '%'.$request->search.'%');
        }

        $sort_by = $request->sort;
        switch ($request->sort) {
            case 'newest':
                $all_uploads->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $all_uploads->orderBy('created_at', 'asc');
                break;
            case 'smallest':
                $all_uploads->orderBy('file_size', 'asc');
                break;
            case 'largest':
                $all_uploads->orderBy('file_size', 'desc');
                break;
            default:
                $all_uploads->orderBy('created_at', 'desc');
                break;
        }

        $all_uploads = $all_uploads->paginate(70)->appends(request()->query());

        return view('admin.uploaded_files.index', compact('all_uploads', 'search', 'sort_by') );
    }

    public function create(){
        return view('admin.uploaded_files.create');
    }


    public function show_uploader(Request $request){
        return view('uploader.aiz-uploader');
    }
    public function upload(Request $request){
        $type = array(
            "jpg"=>"image",
            "jpeg"=>"image",
            "png"=>"image",
            "webp"=>"image",
            "gif"=>"image",
            "mp4"=>"video",
            "mpg"=>"video",
            "mpeg"=>"video",
            "webm"=>"video",
            "ogg"=>"video",
            "avi"=>"video",
            "mov"=>"video",
            "flv"=>"video",
            "mkv"=>"video",
            "wmv"=>"video",
            "wma"=>"audio",
            "aac"=>"audio",
            "wav"=>"audio",
            "mp3"=>"audio",
            "zip"=>"archive",
            "rar"=>"archive",
            "7z"=>"archive",
            "doc"=>"document",
            "txt"=>"document",
            "docx"=>"document",
            "pdf"=>"document",
            "csv"=>"document",
            "xml"=>"document",
            "ods"=>"document",
            "xlr"=>"document",
            "xls"=>"document",
            "xlsx"=>"document"
        );

        if (!$request->hasFile('aiz_file')) {
            return response()->json(['result' => false, 'message' => translate('A file is required.')], 422);
        }

        $request->validate([
            'aiz_file' => 'required|file|max:51200',
        ]);

        $incomingFile = $request->file('aiz_file');
        $incomingExtension = strtolower($incomingFile->getClientOriginalExtension());
        if (!isset($type[$incomingExtension])
            || !$this->isUploadMimeAllowed($incomingFile, $type[$incomingExtension], $incomingExtension)) {
            return response()->json([
                'result' => false,
                'message' => translate('This file type is not allowed.'),
            ], 422);
        }

        if($request->hasFile('aiz_file')){
            $upload = new Upload;
            $extension = $incomingExtension;

            if(isset($type[$extension])){
                $upload->file_original_name = null;
                $arr = explode('.', $request->file('aiz_file')->getClientOriginalName());
                for($i=0; $i < count($arr)-1; $i++){
                    if($i == 0){
                        $upload->file_original_name .= $arr[$i];
                    }
                    else{
                        $upload->file_original_name .= ".".$arr[$i];
                    }
                }

                $path = $request->file('aiz_file')->store('uploads/all', 'local');
                $size = $request->file('aiz_file')->getSize();

                if($type[$extension] == 'image' && get_setting('disable_image_optimization') != 1){
                    try {
                        $img = Image::make($request->file('aiz_file')->getRealPath())->encode();
                        $height = $img->height();
                        $width = $img->width();
                        if($width > $height && $width > 2000){
                            $img->resize(2000, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }elseif ($height > 1500) {
                            $img->resize(null, 800, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }
                        $img->save(base_path('public/').$path);
                        clearstatcache();
                        $size = $img->filesize();

                    } catch (\Exception $e) {
                        //dd($e);
                    }
                }

                if (env('FILESYSTEM_DRIVER') == 's3') {
                    Storage::disk('s3')->put($path, file_get_contents(base_path('public/').$path));
                    unlink(base_path('public/').$path);
                }

                $upload->extension = $extension;
                $upload->file_name = $path;
                $upload->user_id = Auth::user()->id;
                $upload->type = $type[$upload->extension];
                $upload->file_size = $size;
                $upload->save();
            }
            return '{}';
        }
    }

    public function get_uploaded_files(Request $request)
    {
        $uploads = Upload::where('user_id', Auth::user()->id);
        if ($request->search != null) {
            $uploads->where('file_original_name', 'like', '%'.$request->search.'%');
        }
        if ($request->sort != null) {
            switch ($request->sort) {
                case 'newest':
                    $uploads->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $uploads->orderBy('created_at', 'asc');
                    break;
                case 'smallest':
                    $uploads->orderBy('file_size', 'asc');
                    break;
                case 'largest':
                    $uploads->orderBy('file_size', 'desc');
                    break;
                default:
                    $uploads->orderBy('created_at', 'desc');
                    break;
            }
        }
        return $uploads->paginate(60)->appends(request()->query());
    }

    public function destroy($id)
    {
        $upload = Upload::findOrFail($id);
        abort_unless($this->canAccessUpload($upload), 404);

        $disk = env('FILESYSTEM_DRIVER') === 's3' ? 's3' : 'local';
        Storage::disk($disk)->delete($upload->file_name);
        $upload->delete();

        flash(translate('File deleted successfully'))->success();
        return back();
    }

    public function bulk_uploaded_files_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $file_id) {
                $this->destroy($file_id);
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function get_preview_files(Request $request)
    {
        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) $request->input('ids', ''))
        ))));

        if ($ids === []) {
            return response()->json([]);
        }

        $query = Upload::whereIn('id', array_slice($ids, 0, 100));
        if (!$this->canManageAllUploads()) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    //Download project attachment
    public function attachment_download($id)
    {
        $projectAttachment = Upload::findOrFail($id);
        abort_unless($this->canAccessUpload($projectAttachment), 404);

        $filename = basename($projectAttachment->file_original_name ?: $projectAttachment->file_name);
        $disk = env('FILESYSTEM_DRIVER') === 's3' ? 's3' : 'local';

        if ($disk === 's3') {
            return Storage::disk('s3')->download($projectAttachment->file_name, $filename, [
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $filePath = Storage::disk('local')->path($projectAttachment->file_name);
        abort_unless(is_file($filePath), 404);

        return Response::download($filePath, $filename, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

        //Download project attachment
    public function file_info(Request $request)
    {
        $file = Upload::findOrFail($request['id']);
        return view('admin.uploaded_files.info',compact('file'));
    }

    private function canManageAllUploads(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->user_type === 'admin'
            || (method_exists($user, 'can') && $user->can('show_uploaded_files'))
        );
    }

    private function canAccessUpload(Upload $upload): bool
    {
        return Auth::check()
            && ((int) $upload->user_id === (int) Auth::id() || $this->canManageAllUploads());
    }

    private function isUploadMimeAllowed($file, string $type, string $extension): bool
    {
        $mime = strtolower((string) $file->getMimeType());

        if ($type === 'image') {
            return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
        }
        if ($type === 'video') {
            return str_starts_with($mime, 'video/');
        }
        if ($type === 'audio') {
            return str_starts_with($mime, 'audio/');
        }
        if ($type === 'archive') {
            return in_array($mime, [
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/vnd.rar',
                'application/x-7z-compressed',
                'application/octet-stream',
            ], true);
        }

        $documentMimes = [
            'txt' => ['text/plain'],
            'doc' => ['application/msword', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'pdf' => ['application/pdf'],
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'xml' => ['application/xml', 'text/xml', 'text/plain'],
            'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];

        return in_array($mime, $documentMimes[$extension] ?? [], true);
    }

    private function canManageAllUploads(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->user_type === 'admin'
            || (method_exists($user, 'can') && $user->can('show_uploaded_files'))
        );
    }

    private function canAccessUpload(Upload $upload): bool
    {
        return Auth::check()
            && ((int) $upload->user_id === (int) Auth::id() || $this->canManageAllUploads());
    }

    private function isUploadMimeAllowed($file, string $type, string $extension): bool
    {
        $mime = strtolower((string) $file->getMimeType());

        if ($type === 'image') {
            return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
        }
        if ($type === 'video') {
            return str_starts_with($mime, 'video/');
        }
        if ($type === 'audio') {
            return str_starts_with($mime, 'audio/');
        }
        if ($type === 'archive') {
            return in_array($mime, [
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/vnd.rar',
                'application/x-7z-compressed',
                'application/octet-stream',
            ], true);
        }

        $documentMimes = [
            'txt' => ['text/plain'],
            'doc' => ['application/msword', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'pdf' => ['application/pdf'],
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
            'xml' => ['application/xml', 'text/xml', 'text/plain'],
            'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];

        return in_array($mime, $documentMimes[$extension] ?? [], true);
    }

    public function migrate_database(Request $request)
    {
        // Retired legacy activation endpoint contained a path that could drop
        // every production database table after an external response.
        abort(404);
    }

}
