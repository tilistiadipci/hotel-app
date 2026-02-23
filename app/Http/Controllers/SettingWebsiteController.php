<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;

class SettingWebsiteController extends Controller
{
    protected $userRepository;
    protected $page = 'website';

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

        return view('pages.website.index', [
            'user' => $user->load('profile'),
            'profile' => true,
            'page' => 'account'
        ]);
    }
}
