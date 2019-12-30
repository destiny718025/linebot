<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use Illuminate\Http\Request;

class LineController extends Controller
{
    protected $lineBot;
    protected $lineBotService;

    public function __construct(LineBotService $lineBotService)
    {
        $this->lineBot = app('LineBot');
        $this->lineBotService = $lineBotService;
    }

    public function webhook(Request $request)
    {
        if($this->lineBotService->webhook($request)) {
            return response()->json([
                'status' => true
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $this->lineBotService->errorMsg
            ]);
        }
    }

    public function webhook1(Request $request)
    {
        if($this->lineBotService->webhook1($request)) {
            return response()->json([
                'status' => true
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $this->lineBotService->errorMsg
            ]);
        }
    }
}
