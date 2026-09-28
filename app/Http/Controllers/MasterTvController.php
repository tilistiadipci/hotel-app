<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\MasterTvRepository;

class MasterTvController extends Controller
{
    protected MasterTvRepository $masterTvRepository;
    private string $page = 'master tv';
    private string $icon = 'fa fa-tv';

    public function __construct(MasterTvRepository $masterTvRepository)
    {
        $this->masterTvRepository = $masterTvRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->masterTvRepository->getDatatable();
        }

        return view('pages.master_tvs.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.master_tvs.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validateRequest($request);
            $this->masterTvRepository->create($data);

            return redirect()->route('master-tvs.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $uid)
    {
        $masterTv = $this->masterTvRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$masterTv) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.master_tvs.info', [
                    'masterTv' => $masterTv->load('players'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$masterTv) {
            return redirect()->route('error.404');
        }

        return redirect()->route('master-tvs.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uid)
    {
        $masterTv = $this->masterTvRepository->findUid($uid);
        if (!$masterTv) {
            return redirect()->route('error.404');
        }

        return view('pages.master_tvs.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'masterTv' => $masterTv,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $data = $this->validateRequest($request, $id);
            $this->masterTvRepository->updateByUid($id, $data);

            return redirect()->route('master-tvs.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uid)
    {
        try {
            $this->masterTvRepository->delete($uid);

            return redirect()->route('master-tvs.index')->with('success', trans('common.success.delete'));
        } catch (\Exception $e) {
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->masterTvRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $rules = [
            'name' => 'required|string|max:150',
            'brand' => 'required|string|max:100',
            'size' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }
}
