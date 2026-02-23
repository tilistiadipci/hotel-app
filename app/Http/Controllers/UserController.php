<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\DepartementRepository;

class UserController extends Controller
{
    protected $userRepository;
    protected $roleRepository;
    private $page;
    private $icon = 'fa fa-users';

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->page = 'users';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->userRepository->getDatatable();
        }

        return view('pages.users.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'roles' => $this->roleRepository->getRoles(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.users.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'roles' => $this->roleRepository->getRoles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);

        try {
            DB::beginTransaction();
            $this->handleUploadFile($request);

            $this->userRepository->create($request->all());

            DB::commit();
            return redirect()->route('users.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollback();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $this->userRepository->findUid($id);

        if ($request->ajax()) {

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.users.info', [
                    'page' => $this->page,
                    'user' => $user->load(['profile', 'role']),
                    'detail' => 'info',
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$user) {
            return redirect()->route('error.404');
        }

        return view('pages.users.show', [
            'page' => $this->page,
            'user' => $user->load(['profile', 'role']),
            'detail' => 'info',
        ]);
    }

    /**
     * Show the form for detail the specified resource.
     */
    public function detail(Request $request, $id, $part = 'info')
    {
        $user = $this->userRepository->findUid($id);

        if (!$user) {
            return redirect()->route('error.404');
        }

        return view('pages.users.show', [
            'page' => $this->page,
            'user' => $user->load(['profile', 'role']),
            'detail' => $part,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uid)
    {
        // check if admin or not
        if ($this->userRepository->checkUserNotAdmin($uid)) {
            return view('pages.users.edit', [
                'page' => $this->page,
                'icon' => $this->icon,
                'user' => $this->userRepository->whereWith(['profile'], ['uuid' => $uid])->first(),
                'roles' => $this->roleRepository->getRoles(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->validateRequest($request, $id);

        try {
            DB::beginTransaction();
            $this->handleUploadFile($request);

            $this->userRepository->update($id, $request->all());

            DB::commit();

            // kalau update profile
            if ($id == auth()->user()->id) {
                return true;
            }

            return redirect()->route('users.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollback();
            $this->debugError($e);

            if ($id == auth()->user()->id) {
                return false;
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->userRepository->delete($id);

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

    public function bulkDelete(Request $request)
    {
        try {
            $this->userRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }

    }

    private function handleUploadFile(Request $request) : void
    {
        if ($request->hasFile('img')) {
            app(HelperController::class)->storeImage($request, 'img', 'users');
        }
    }

    private function validateRequest(Request $request, $id = null)
    {
        // base rules
        $rules = [
            'name' => 'required|max:200',
            'username' => 'required',
            'gender' => 'required',
            'email' => 'required|unique:users,email,' . $id,
            'phone' => 'required|min:6|max:20',
            'role_id' => 'required',
        ];

        // file rule: allow empty, guard dimensions to avoid ValueError when tmp path missing
        $rules['img'] = 'nullable|image|mimes:jpeg,png,jpg|max:512';
        if ($request->hasFile('img')) {
            $file = $request->file('img');
            if ($file->isValid() && $file->getRealPath()) {
                $rules['img'] .= '|dimensions:min_width=10,min_height=10,max_width=300,max_height=300';
            }
        }

        $messages = [
            'username.required' => trans('common.error.required', ['attribute'=> 'Username']),
            'name.required' => trans('common.error.required', ['attribute' => trans('common.name')]),
            'gender.required' => trans('common.error.required', ['attribute'=> trans('common.gender')]),
            'email.required' => trans('common.error.required', ['attribute'=> trans('common.email')]),
            'email.unique' => trans('common.error.unique', ['attribute'=> trans('common.email')]),
            'phone.required'=> trans('common.error.required', ['attribute'=> trans('common.phone')]),
            'phone.min' => trans('common.error.min', ['attribute'=> trans('common.phone')]),
            'role_id.required' => trans('common.error.required', ['attribute'=> 'Role']),
            'img.dimensions'=> trans('common.error.image'),
            'img.image' => trans('common.error.image'),
            'img.mimes' => trans('common.error.image'),
        ];

        if ($id) {
            $rules['username'] = 'required|unique:users,username,' . $id;
            $rules['email'] = 'required|unique:users,email,' . $id;

            $messages['username.unique'] = trans('common.error.unique', ['attribute'=> 'Username']);
            $messages['email.unique'] = trans('common.error.unique', ['attribute'=> trans('common.email')]);
        }

        $request->validate($rules, $messages);
    }
}
