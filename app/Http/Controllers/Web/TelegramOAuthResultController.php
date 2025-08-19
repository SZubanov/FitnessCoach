<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TelegramOAuthResultController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $request->get('status', 'unknown');
        $message = $request->get('message', 'Неизвестная ошибка');
        
        return view('telegram.oauth-result', compact('status', 'message'));
    }
}