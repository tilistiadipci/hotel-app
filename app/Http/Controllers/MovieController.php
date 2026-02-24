<?php

namespace App\Http\Controllers;

use App\Repositories\MovieCategoryRepository;
use App\Repositories\MovieRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    protected $movieRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-film';

    public function __construct(
        MovieRepository $movieRepository,
        MovieCategoryRepository $categoryRepository
    ) {
        $this->movieRepository = $movieRepository;
        $this->categoryRepository = $categoryRepository;
        $this->page = 'movies';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->movieRepository->getDatatable();
        }

        return view('pages.movies.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.movies.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data);
            $this->handleUploadVideo($request, $data);

            $movie = $this->movieRepository->create($data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uid)
    {
        $movie = $this->movieRepository->findUid($uid);
        if (!$movie) {
            return redirect()->route('error.404');
        }

        return view('pages.movies.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'movie' => $movie->load('categories'),
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data);
            $this->handleUploadVideo($request, $data, $uid);

            $this->movieRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->movieRepository->delete($uid);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500')
            ]);
        }
    }

    public function show(Request $request, string $uid)
    {
        $movie = $this->movieRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$movie) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.movies.info', [
                    'page' => $this->page,
                    'movie' => $movie->load('categories'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$movie) {
            return redirect()->route('error.404');
        }

        return view('pages.movies.show', [
            'page' => $this->page,
            'movie' => $movie->load('categories'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->movieRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $movieId = null;
        if ($uid) {
            $movieId = optional($this->movieRepository->findUid($uid))->id;
        }

        $rules = [
            'title' => 'required|max:200|unique:movies,title' . ($movieId ? ',' . $movieId : ''),
            'description' => 'nullable|string',
            'thumbnail' => ($uid ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg|max:1024',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'video' => ($uid ? 'nullable' : 'required') . '|file|mimes:mp4,mov,mkv,webm,avi|max:1024000', // ~1GB
            'duration' => 'nullable|integer|min:1',
            'release_date' => 'nullable|date',
            'rating' => 'nullable|max:10',
            'is_active' => 'required|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:movies_categories,id',
        ];

        $validated = $request->validate($rules);

        // ensure duration available either provided or to be detected; handled in upload
        return $validated;
    }

    private function handleUploadImages(Request $request, array &$data): void
    {
        if ($request->hasFile('thumbnail')) {
            app(HelperController::class)->storeImage($request, 'thumbnail', 'movies', 'thumbnail');
            $data['thumbnail'] = $request->input('thumbnail');
        }

        if ($request->hasFile('banner_image')) {
            app(HelperController::class)->storeImage($request, 'banner_image', 'movies/banners', 'banner_image');
            $data['banner_image'] = $request->input('banner_image');
        }
    }

    private function handleUploadVideo(Request $request, array &$data, ?string $uid = null): void
    {
        $file = $request->file('video');
        $existing = $uid ? $this->movieRepository->findUid($uid) : null;

        if ($file && $file->isValid()) {
            $stored = $this->storeVideoFile($file);
            $data['url_stream'] = $stored['public_path'];
            $data['duration'] = $data['duration'] ?? $this->detectVideoDuration($stored['absolute_path']);
        } elseif ($existing) {
            $data['url_stream'] = $existing->url_stream;
            $data['duration'] = $data['duration'] ?? $existing->duration;
        }

        if (empty($data['duration'])) {
            throw ValidationException::withMessages([
                'duration' => 'Durasi video tidak terdeteksi. Isi durasi (detik) atau unggah ulang video.',
            ]);
        }
    }

    private function storeVideoFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File video tidak valid.');
        }

        $fileName = now()->format('YmdHis') . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $disk = Storage::disk('public');
        $relativePath = 'videos/' . $fileName;

        if ($file->getRealPath()) {
            $stored = $file->storeAs('videos', $fileName, 'public');
        } else {
            $contents = @file_get_contents($file->getPathname());
            if ($contents === false) {
                throw new \Exception('Gagal membaca file video yang diunggah.');
            }
            $disk->put($relativePath, $contents);
            $stored = $relativePath;
        }

        return [
            'public_path' => 'storage/' . $stored,
            'absolute_path' => storage_path('app/public/' . $stored),
        ];
    }

    private function detectVideoDuration(string $absolutePath): ?int
    {
        if (!file_exists($absolutePath)) {
            return null;
        }

        $cmd = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($absolutePath);
        $output = @shell_exec($cmd);
        if ($output !== null) {
            $seconds = (int) round((float) trim($output));
            if ($seconds > 0) {
                return $seconds;
            }
        }

        return null;
    }
}
