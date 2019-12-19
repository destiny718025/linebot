<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineController extends Controller
{
    protected $bot;

    public function __construct()
    {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(config('linebot.channel.access_token'));
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => config('linebot.channel.secret')]);
    }

    public function webhook(Request $request)
    {
        $signature = $request->header(\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE);
        $body = $request->getContent();

        try {
            $events = $this->bot->parseEventRequest($body, $signature);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }

        foreach ($events as $event) {
            $line_user_id = $event->getUserId();

            if ($event instanceof MessageEvent) {
                $text = $event->getText();

                switch ($text) {
                    case '記帳清單' :
                        $this->bot->replyMessage($event->getReplyToken(), new TextMessageBuilder($line_user_id));
                        break;
                    default :
                        $this->bot->replyMessage($event->getReplyToken(), new TextMessageBuilder($text));
                        break;
                }
            }

//            if ($event instanceof FollowEvent) {
//                $user = new User;
//
//                $user->uid = $line_user_id;
//
//                $user->save();
//            } elseif ($event instanceof UnfollowEvent) {
//                $users = User::query()
//                    ->where('uid', $line_user_id)
//                    ->get();
//
//                Log::info(json_encode($users));
//            }
        }

        return response()->json([
            'status' => true
        ]);
    }
}
