<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\MovieCategory;
use App\Repositories\MediaRepository;
use App\Repositories\MovieCategoryRepository;
use App\Repositories\MovieRepository;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovieController extends Controller
{
    protected $movieRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-film';
    protected MediaRepository $mediaRepository;

    public function __construct(
        MovieRepository $movieRepository,
        MovieCategoryRepository $categoryRepository,
        MediaRepository $mediaRepository
    ) {
        $this->movieRepository = $movieRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mediaRepository = $mediaRepository;
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

        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data, $createdMediaIds, $storedPaths);
            $this->handleUploadVideo($request, $data, null, $createdMediaIds, $storedPaths);

            $movie = $this->movieRepository->create($data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
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
            'movie' => $movie->load('categories', 'imageMedia', 'videoMedia'),
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data, $createdMediaIds, $storedPaths);
            $this->handleUploadVideo($request, $data, $uid, $createdMediaIds, $storedPaths);

            $this->movieRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
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
            'movie' => $movie->load('categories', 'imageMedia', 'videoMedia'),
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

    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = $this->buildImportTemplateSpreadsheet();
        $fileName = 'movies-import-template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
                ]);

                $result = $this->prepareMovieImport($request->file('file'));

                return response()->json([
                    'status' => true,
                    'message' => 'File import berhasil diproses.',
                    'data' => $result,
                ]);
            }

            $request->validate([
                'token' => 'required|string',
                'offset' => 'nullable|integer|min:0',
                'limit' => 'nullable|integer|min:1|max:25',
            ]);

            $result = $this->processMovieImportBatch(
                $request->input('token'),
                (int) $request->input('offset', 0),
                (int) $request->input('limit', 5)
            );

            return response()->json([
                'status' => true,
                'message' => 'Import movie selesai diproses.',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->debugError($e);

            return response()->json([
                'status' => false,
                'message' => trans('common.error.500'),
            ], 500);
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $movie = $uid ? $this->movieRepository->findUid($uid) : null;
        $movieId = $movie->id ?? null;

        $rules = [
            'title' => 'required|max:200|unique:movies,title' . ($movieId ? ',' . $movieId : ''),
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'video' => 'nullable|file|mimes:mp4,mov,mkv,webm,avi|max:1024000', // ~1GB
            'uploaded_video_filename' => 'nullable|string',
            'video_media_id' => 'nullable|integer|exists:medias,id',
            'duration' => 'nullable|integer|min:0',
            'release_date' => 'required|date',
            'rating' => 'required|max:10',
            'is_active' => 'required|boolean',
            'is_favorit' => 'required|boolean',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:movies_categories,id',
        ];

        // Create: wajib pilih salah satu (upload baru atau pilih media) untuk image
        if (!$uid) {
            $rules['image'] = 'required_without:image_media_id|' . $rules['image'];
            $rules['image_media_id'] = 'required_without:image|integer|exists:medias,id';
        }

        $validated = $request->validate($rules);

        // Pastikan video hanya wajib saat create atau film belum punya video sama sekali
        $needsVideo = !$uid || !$movie?->video_id;
        if (
            $needsVideo &&
            !$request->file('video') &&
            !$request->filled('uploaded_video_filename') &&
            !$request->filled('video_media_id')
        ) {
            throw ValidationException::withMessages([
                'video' => 'Silakan unggah video atau pilih dari media yang sudah ada.',
            ]);
        }

        // ensure duration available either provided or to be detected; handled in upload
        return $validated;
    }

    private function handleUploadImages(Request $request, array &$data, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $existing = $request->route('movie') ? $this->movieRepository->findUid($request->route('movie')) : null;
        $selectedMediaId = $request->input('image_media_id');

        if ($file && $file->isValid()) {
            $media = $this->storeImageFile($request, $file);
            $data['image_id'] = $media->id;
            $createdMediaIds[] = $media->id;
            $storedPaths[] = $media->storage_path;
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'image') {
                throw ValidationException::withMessages([
                    'image' => 'Media gambar tidak ditemukan atau bukan gambar.',
                ]);
            }
            $data['image_id'] = $media->id;
        } elseif ($existing) {
            $data['image_id'] = $existing->image_id;
        }
    }

    private function handleUploadVideo(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('video');
        $existing = $uid ? $this->movieRepository->findUid($uid) : null;
        $duration = isset($data['duration']) && $data['duration'] !== '' ? (int) $data['duration'] : null;
        $selectedMediaId = $request->input('video_media_id');

        if ($file && $file->isValid()) {
            $stored = $this->storeVideoFile($file, $duration);
            $data['video_id'] = $stored['media_id'];
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'] ?? null;
            // duration expected from frontend; keep provided value
        } elseif ($request->filled('uploaded_video_filename')) {
            $mediaId = $request->input('video_media_id');
            $media = $mediaId ? $this->mediaRepository->find($mediaId) : null;
            if (!$media) {
                throw ValidationException::withMessages([
                    'video' => 'File video hasil upload chunk tidak ditemukan. Silakan upload ulang.',
                ]);
            }
            $data['video_id'] = $media->id;
            if ($duration !== null) {
                $media->duration = $duration;
                $media->save();
            }
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'video') {
                throw ValidationException::withMessages([
                    'video' => 'Media video tidak ditemukan atau bukan video.',
                ]);
            }
            $data['video_id'] = $media->id;
            if ($duration !== null) {
                $media->duration = $duration;
                $media->save();
            } elseif ($media->duration !== null) {
                $data['duration'] = $media->duration;
            }
        } elseif ($existing) {
            $data['video_id'] = $existing->video_id;
            $data['duration'] = $data['duration'] ?? $existing->duration;
        }

        // Pastikan duration tidak null (kolom non-null)
        if (!isset($data['duration']) || $data['duration'] === null || $data['duration'] === '') {
            $data['duration'] = 0;
        }

    }

    private function storeVideoFile(UploadedFile $file, ?int $duration = null): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File video tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'videos', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan video.');
        }

        $media = $this->mediaRepository->createFromUpload('video', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'duration' => $duration,
        ]);

        return [
            'media_id' => $media->id,
            'absolute_path' => $helper->mediaAbsolutePath($relativePath),
            'relative_path' => $relativePath,
        ];
    }

    private function storeImageFile(Request $request, UploadedFile $file): Media
    {
        if (!$file->isValid()) {
            throw new \Exception('File gambar tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'images', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan gambar.');
        }

        $dimensions = $helper->getImageDimensionsFromPath($relativePath, $file);

        return $this->mediaRepository->createFromUpload('image', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);
    }

    private function buildImportTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movies');
        $headers = ['title', 'description', 'categories', 'image_file', 'video_file', 'release_date', 'rating', 'is_active', 'is_favorit'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            [
                'Inception',
                'A mind-bending sci-fi thriller.',
                'Sci-Fi, Thriller',
                'inception-cover.jpg',
                'inception.mp4',
                '2010-07-16',
                'PG-13',
                1,
                0,
            ],
        ], null, 'A2');

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        foreach (['H', 'I'] as $column) {
            for ($row = 2; $row <= 200; $row++) {
                $validation = $sheet->getCell($column . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"1,0"');
            }
        }

        for ($row = 2; $row <= 200; $row++) {
            $validation = $sheet->getCell('G' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"G,PG,PG-13,R,NC-17"');
        }

        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Info');
        $infoSheet->fromArray([
            ['Kolom', 'Keterangan'],
            ['title', 'Wajib. Judul movie harus diisi dan unik.'],
            ['description', 'Wajib. Deskripsi movie harus diisi.'],
            ['categories', 'Wajib. Boleh lebih dari satu kategori, pisahkan dengan koma. Kategori baru akan dibuat otomatis jika belum ada.'],
            ['image_file', 'Wajib. Nama file gambar di folder MEDIA_STORAGE_PATH/upload-video.'],
            ['video_file', 'Wajib. Nama file video di folder MEDIA_STORAGE_PATH/upload-video.'],
            ['release_date', 'Wajib. Format tanggal YYYY-MM-DD.'],
            ['rating', 'Wajib. Pilihan: G, PG, PG-13, R, NC-17.'],
            ['is_active', 'Wajib. Isi 1 atau 0.'],
            ['is_favorit', 'Opsional. Isi 1 atau 0. Default 0.'],
        ], null, 'A1');
        $infoSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $infoSheet->getColumnDimension('A')->setWidth(18);
        $infoSheet->getColumnDimension('B')->setWidth(110);
        $infoSheet->getStyle('B:B')->getAlignment()->setWrapText(true);

        return $spreadsheet;
    }

    private function prepareMovieImport(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath() ?: $file->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File Excel kosong atau tidak memiliki data.',
            ]);
        }

        $headerRow = array_shift($rows);
        $headerMap = [];
        foreach ($headerRow as $column => $header) {
            $normalized = $this->normalizeImportHeader((string) $header);
            if ($normalized !== '') {
                $headerMap[$column] = $normalized;
            }
        }

        $requiredHeaders = ['title', 'description', 'categories', 'image_file', 'video_file', 'release_date', 'rating', 'is_active'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headerMap, true)) {
                throw ValidationException::withMessages([
                    'file' => "Template import tidak valid. Kolom {$requiredHeader} tidak ditemukan.",
                ]);
            }
        }

        $preparedRows = [];
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = [];

            foreach ($headerMap as $column => $key) {
                $payload[$key] = isset($row[$column]) ? trim((string) $row[$column]) : null;
            }

            if ($this->isImportRowEmpty($payload)) {
                continue;
            }

            $preparedRows[] = [
                'row_number' => $rowNumber,
                'payload' => $payload,
            ];
        }

        $token = 'movie-import:' . auth()->id() . ':' . Str::uuid();

        Cache::put($token, [
            'rows' => $preparedRows,
            'used_source_files' => [],
            'imported' => 0,
            'issues' => [],
            'infos' => [],
        ], now()->addHour());

        return [
            'token' => $token,
            'total_rows' => count($preparedRows),
            'imported' => 0,
            'processed' => 0,
            'issues' => [],
            'infos' => [],
            'completed' => count($preparedRows) === 0,
            'next_offset' => 0,
            'batch_size' => 5,
        ];
    }

    private function processMovieImportBatch(string $token, int $offset, int $limit): array
    {
        $state = Cache::get($token);

        if (!$state || !str_starts_with($token, 'movie-import:' . auth()->id() . ':')) {
            throw ValidationException::withMessages([
                'file' => 'Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file Excel.',
            ]);
        }

        $rows = $state['rows'] ?? [];
        $slice = array_slice($rows, $offset, $limit);
        $usedSourceFiles = $state['used_source_files'] ?? [];
        $issues = $state['issues'] ?? [];
        $infos = $state['infos'] ?? [];
        $imported = (int) ($state['imported'] ?? 0);

        foreach ($slice as $item) {
            $rowNumber = (int) ($item['row_number'] ?? 0);
            $payload = (array) ($item['payload'] ?? []);
            $createdMediaIds = [];
            $storedPaths = [];
            $sourcePathsToDelete = [];
            $sourceFileKeysToCommit = [];
            $rowInfos = [];

            try {
                DB::beginTransaction();

                $moviePayload = $this->buildMoviePayloadFromImportRow(
                    $payload,
                    $rowNumber,
                    $usedSourceFiles,
                    $sourceFileKeysToCommit,
                    $createdMediaIds,
                    $storedPaths,
                    $sourcePathsToDelete,
                    $rowInfos
                );
                $this->movieRepository->create($moviePayload);

                DB::commit();

                foreach ($sourceFileKeysToCommit as $sourceFileKey) {
                    $usedSourceFiles[$sourceFileKey] = true;
                }

                foreach ($sourcePathsToDelete as $sourcePath) {
                    if (is_file($sourcePath)) {
                        @unlink($sourcePath);
                    }
                }

                foreach ($rowInfos as $info) {
                    $infos[] = $info;
                }

                $imported++;
            } catch (\Throwable $e) {
                DB::rollBack();
                app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
                $issues[] = [
                    'row' => $rowNumber,
                    'message' => $e instanceof ValidationException
                        ? collect($e->errors())->flatten()->implode(' ')
                        : $e->getMessage(),
                ];
            }
        }

        $processed = min($offset + count($slice), count($rows));
        $completed = $processed >= count($rows);

        $state['used_source_files'] = $usedSourceFiles;
        $state['issues'] = $issues;
        $state['infos'] = $infos;
        $state['imported'] = $imported;

        if ($completed) {
            Cache::forget($token);
        } else {
            Cache::put($token, $state, now()->addHour());
        }

        return [
            'token' => $token,
            'total_rows' => count($rows),
            'processed' => $processed,
            'imported' => $imported,
            'issues' => $issues,
            'infos' => $infos,
            'completed' => $completed,
            'next_offset' => $processed,
            'batch_size' => $limit,
        ];
    }

    private function buildMoviePayloadFromImportRow(
        array $row,
        int $rowNumber,
        array &$usedSourceFiles,
        array &$sourceFileKeysToCommit,
        array &$createdMediaIds,
        array &$storedPaths,
        array &$sourcePathsToDelete,
        array &$rowInfos
    ): array {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => "Baris {$rowNumber}: title wajib diisi.",
            ]);
        }

        $movieExists = $this->movieRepository->query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->exists();

        if ($movieExists) {
            throw ValidationException::withMessages([
                'title' => "Baris {$rowNumber}: title \"{$title}\" sudah ada.",
            ]);
        }

        $description = trim((string) ($row['description'] ?? ''));
        if ($description === '') {
            throw ValidationException::withMessages([
                'description' => "Baris {$rowNumber}: description wajib diisi.",
            ]);
        }

        $categoryIds = $this->findOrCreateMovieCategoryIds((string) ($row['categories'] ?? ''), $rowNumber);

        $releaseDateRaw = trim((string) ($row['release_date'] ?? ''));
        if ($releaseDateRaw === '') {
            throw ValidationException::withMessages([
                'release_date' => "Baris {$rowNumber}: release_date wajib diisi.",
            ]);
        }

        try {
            $releaseDate = \Carbon\Carbon::parse($releaseDateRaw)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'release_date' => "Baris {$rowNumber}: release_date harus format tanggal yang valid (YYYY-MM-DD).",
            ]);
        }

        $rating = strtoupper(trim((string) ($row['rating'] ?? '')));
        $allowedRatings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
        if (!in_array($rating, $allowedRatings, true)) {
            throw ValidationException::withMessages([
                'rating' => "Baris {$rowNumber}: rating harus salah satu dari G, PG, PG-13, R, NC-17.",
            ]);
        }

        $videoFileName = trim((string) ($row['video_file'] ?? ''));
        if ($videoFileName === '') {
            throw ValidationException::withMessages([
                'video_file' => "Baris {$rowNumber}: video_file wajib diisi.",
            ]);
        }

        $imageFileName = trim((string) ($row['image_file'] ?? ''));
        if ($imageFileName === '') {
            throw ValidationException::withMessages([
                'image_file' => "Baris {$rowNumber}: image_file wajib diisi.",
            ]);
        }

        $videoMedia = $this->importMediaFromSourceFileName(
            $videoFileName,
            'video',
            $rowNumber,
            $usedSourceFiles,
            $sourceFileKeysToCommit,
            $createdMediaIds,
            $storedPaths,
            $sourcePathsToDelete,
            $rowInfos
        );

        $imageMedia = $this->importMediaFromSourceFileName(
            $imageFileName,
            'image',
            $rowNumber,
            $usedSourceFiles,
            $sourceFileKeysToCommit,
            $createdMediaIds,
            $storedPaths,
            $sourcePathsToDelete,
            $rowInfos
        );

        return [
            'title' => $title,
            'description' => $description,
            'category_ids' => $categoryIds,
            'image_id' => $imageMedia['media_id'],
            'video_id' => $videoMedia['media_id'],
            'duration' => $videoMedia['duration'] ?? 0,
            'release_date' => $releaseDate,
            'rating' => $rating,
            'is_active' => $this->normalizeImportBoolean($row['is_active'] ?? null, true, $rowNumber, 'is_active'),
            'is_favorit' => $this->normalizeImportBoolean($row['is_favorit'] ?? null, false, $rowNumber, 'is_favorit'),
        ];
    }

    private function importMediaFromSourceFileName(
        string $fileName,
        string $expectedType,
        int $rowNumber,
        array &$usedSourceFiles,
        array &$sourceFileKeysToCommit,
        array &$createdMediaIds,
        array &$storedPaths,
        array &$sourcePathsToDelete,
        array &$rowInfos
    ): array {
        $sourcePath = $this->findSourceMediaFilePath($fileName);
        if (!$sourcePath) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} tidak ditemukan di folder MEDIA_STORAGE_PATH/upload-video.",
            ]);
        }

        $normalizedKey = mb_strtolower($expectedType . ':' . basename($sourcePath));
        if (isset($usedSourceFiles[$normalizedKey]) || in_array($normalizedKey, $sourceFileKeysToCommit, true)) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} dipakai lebih dari satu kali dalam import yang sama.",
            ]);
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $resolvedType = $this->resolveMediaTypeByExtension($extension);
        if ($resolvedType !== $expectedType) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} bukan file {$expectedType} yang valid.",
            ]);
        }

        $result = $this->syncLocalMediaFile($sourcePath, basename($sourcePath), $expectedType, $extension);
        if (!in_array($result['status'], ['synced', 'existing'], true)) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: {$result['message']}",
            ]);
        }

        $sourceFileKeysToCommit[] = $normalizedKey;
        if ($result['status'] === 'synced') {
            $createdMediaIds[] = $result['media_id'];
            $storedPaths[] = $result['relative_path'];
        }
        $sourcePathsToDelete[] = $sourcePath;

        if (!empty($result['info'])) {
            $rowInfos[] = [
                'row' => $rowNumber,
                'message' => $result['info'],
            ];
        }

        return $result;
    }

    private function findSourceMediaFilePath(string $fileName): ?string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return null;
        }

        $sourcePath = $this->movieImportUploadAbsolutePath($fileName);
        return is_file($sourcePath) ? $sourcePath : null;
    }

    private function syncLocalMediaFile(string $sourcePath, string $originalName, string $type, string $extension): array
    {
        $allowedExt = $this->allowedExtensionsByType($type);
        if (!in_array($extension, $allowedExt, true)) {
            return [
                'status' => 'skipped',
                'message' => 'Extension not allowed.',
            ];
        }

        $maxBytes = (int) config('media_upload.limits_bytes.' . $type, 0);
        if ($maxBytes > 0 && is_file($sourcePath) && filesize($sourcePath) > $maxBytes) {
            $limitMb = (int) config('media_upload.limits_mb.' . $type, 0);
            return [
                'status' => 'skipped',
                'message' => "Size exceeds limit ({$limitMb} MB).",
            ];
        }

        $existingMedia = $this->mediaRepository->query()
            ->where('original_filename', $originalName)
            ->where('type', $type)
            ->first();

        if ($existingMedia) {
            return [
                'status' => 'existing',
                'message' => 'Media already exists and will be reused.',
                'info' => "{$originalName} sudah ada, menggunakan media_id {$existingMedia->id}.",
                'relative_path' => $existingMedia->storage_path,
                'media_id' => $existingMedia->id,
                'duration' => $type === 'video' ? $existingMedia->duration : null,
            ];
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $destDir = $type === 'video' ? 'videos' : 'images';
        $relativePath = $this->generateUniqueMediaRelativePath($destDir, $baseName, $extension);
        $destPath = $this->mediaAbsolutePath($relativePath);
        $this->ensureDirectoryExistsSafely(dirname($destPath));

        try {
            File::copy($sourcePath, $destPath);

            $mime = $this->detectMimeType($destPath, $extension);
            $dimensions = $type === 'image' ? $this->getImageDimensionsFromAbsolutePath($destPath) : [];
            $duration = $type === 'video' ? $this->probeDuration($destPath) : null;

            $media = $this->mediaRepository->createFromUpload($type, $relativePath, [
                'extension' => $extension,
                'mime' => $mime,
                'size' => filesize($destPath) ?: null,
                'name' => $baseName,
                'original' => $originalName,
                'duration' => $duration,
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ]);

            return [
                'status' => 'synced',
                'message' => 'Synced successfully.',
                'relative_path' => $relativePath,
                'media_id' => $media->id,
                'duration' => $duration,
                'info' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function findOrCreateMovieCategoryIds(string $categories, int $rowNumber): array
    {
        $parts = collect(preg_split('/[,;]+/', $categories))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            throw ValidationException::withMessages([
                'categories' => "Baris {$rowNumber}: categories wajib diisi.",
            ]);
        }

        return $parts->map(function ($name) {
            $category = MovieCategory::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($category) {
                return $category->id;
            }

            return $this->categoryRepository->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => null,
                'sort_order' => 0,
                'is_active' => 1,
            ])->id;
        })->unique()->values()->all();
    }

    private function normalizeImportHeader(string $header): string
    {
        return Str::of($header)->trim()->lower()->replace([' ', '-'], '_')->value();
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeImportBoolean(mixed $value, bool $default, int $rowNumber, string $field): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }

        $truthy = ['1', 'true', 'yes', 'ya'];
        $falsy = ['0', 'false', 'no', 'tidak'];

        if (in_array(mb_strtolower($value), $truthy, true)) {
            return true;
        }

        if (in_array(mb_strtolower($value), $falsy, true)) {
            return false;
        }

        throw ValidationException::withMessages([
            $field => "Baris {$rowNumber}: {$field} harus bernilai 1 atau 0.",
        ]);
    }

    private function mediaAbsolutePath(string $relativePath): string
    {
        $root = config('filesystems.disks.media.root');
        return rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
    }

    private function movieImportUploadAbsolutePath(string $fileName): string
    {
        return $this->movieImportUploadRoot() . DIRECTORY_SEPARATOR . ltrim($fileName, "/\\");
    }

    private function movieImportUploadRoot(): string
    {
        return rtrim((string) config('filesystems.disks.media.root'), "/\\") . DIRECTORY_SEPARATOR . 'upload-video';
    }

    private function generateUniqueMediaRelativePath(string $destDir, string $baseName, string $extension): string
    {
        $safeBase = Str::slug($baseName);
        $safeBase = $safeBase !== '' ? $safeBase : 'media-file';
        $extension = ltrim(strtolower($extension), '.');
        $counter = 0;

        do {
            $suffix = $counter > 0 ? '-' . ($counter + 1) : '';
            $relativePath = $destDir . '/' . $safeBase . $suffix . '.' . $extension;
            $existsInStorage = is_file($this->mediaAbsolutePath($relativePath));
            $existsInDb = $this->mediaRepository->query()
                ->where('storage_path', $relativePath)
                ->exists();
            $counter++;
        } while ($existsInStorage || $existsInDb);

        return $relativePath;
    }

    private function ensureDirectoryExistsSafely(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        File::ensureDirectoryExists($path, 0755, true);
    }

    private function getImageDimensionsFromAbsolutePath(string $path): array
    {
        $size = @getimagesize($path);

        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    private function guessMimeFromExtension(string $ext): ?string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'avi' => 'video/x-msvideo',
            default => null,
        };
    }

    private function detectMimeType(string $path, string $extension): ?string
    {
        if (is_file($path)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if ($mime) {
                return $mime;
            }
        }

        return $this->guessMimeFromExtension($extension);
    }

    private function probeDuration(string $path): ?int
    {
        if (!is_file($path)) {
            return null;
        }

        $ffprobe = trim((string) env('FFPROBE_PATH', 'ffprobe'));
        $ffprobe = $ffprobe !== '' ? $ffprobe : 'ffprobe';
        $cmd = '"' . $ffprobe . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' .
            escapeshellarg($path);

        $output = @shell_exec($cmd);
        if (!$output) {
            return null;
        }

        $seconds = (float) trim($output);
        if (!is_finite($seconds) || $seconds <= 0) {
            return null;
        }

        return (int) round($seconds);
    }

    private function allowedExtensionsByType(string $type): array
    {
        return match ($type) {
            'video' => ['mp4', 'mov', 'mkv', 'webm', 'avi'],
            'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            default => [],
        };
    }

    private function resolveMediaTypeByExtension(string $extension): ?string
    {
        $extension = strtolower($extension);

        if (in_array($extension, $this->allowedExtensionsByType('video'), true)) {
            return 'video';
        }

        if (in_array($extension, $this->allowedExtensionsByType('image'), true)) {
            return 'image';
        }

        return null;
    }
}
