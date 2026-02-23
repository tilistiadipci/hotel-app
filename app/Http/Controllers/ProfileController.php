<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected $userRepository;
    protected $page = 'profile';

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $id = auth()->user()->id;
        $user = $this->userRepository->find($id);

        if (!$user) {
            return redirect()->route('pages.errors.404');
        }

        return view('pages.profiles.index', [
            'user' => $user->load('profile'),
            'profile' => true,
            'page' => 'account'
        ]);
    }

    public function changeProfile()
    {
        $id = auth()->user()->id;

        return view('pages.profiles.change-profile', [
            'user' => $this->userRepository->find($id)->load('profile'),
            'profile' => true,
            'page' => 'account'
        ]);
    }

    public function update(Request $request)
    {
        $id = auth()->user()->id;

        try {
            $this->validateRequest($request, $id);

            $this->handleUploadFile($request);

            $user = $this->userRepository->updateProfile($request->all(), $id);

            if (!$user) {
                return redirect()->back()->with('error', trans('common.error.unique', ['attribute' => 'email']));
            }

            return redirect()->back()->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            return $this->debugError($e);
        }
    }

    private function handleUploadFile(Request $request) : void
    {
        if ($request->hasFile('img')) {
            app(HelperController::class)->storeImage($request, 'img', 'users');
        }
    }

    private function validateRequest(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|min:6|max:20',
        ], [
            'name.required' => trans('common.error.required', ['attribute' => trans('common.name')]),
            'email.required' => trans('common.error.required', ['attribute' => trans('common.email')]),
            'email.email' => trans('common.error.email'),
            'email.unique' => trans('common.error.unique', ['attribute' => trans('common.email')]),
            'phone.required' => trans('common.error.required', ['attribute' => trans('common.phone')]),
            'phone.min' => trans('common.error.min', ['attribute' => trans('common.phone'), 'min' => 6]),
            'phone.max' => trans('common.error.max', ['attribute' => trans('common.phone')])
        ]);
    }
}
