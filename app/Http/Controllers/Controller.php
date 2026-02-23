<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function debugError(\Exception $e)
    {
        Log::info($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (config('app.debug')) {
            dd($e);
        }

    }

    public function debugErrorResJson(\Exception $e)
    {
        Log::info($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (config('app.debug')) {
            dd($e);
        }

        return response()->json([
            'message' => trans('common.error.500'),
        ], 500);
    }
}
