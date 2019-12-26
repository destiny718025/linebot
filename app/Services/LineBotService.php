<?php


namespace App\Services;

use App\models\TodoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\Response;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;

class LineBotService
{
    private $lineUserId;
    private $lineBot;
    private $reptileService;

    public $errorMsg;

    public function __construct(string $lineUserId, ReptileService $reptileService)
    {
        $this->lineUserId = $lineUserId;
        $this->lineBot = app('LineBot');
        $this->reptileService = $reptileService;
    }

    public function webhook(Request $request)
    {
        $signature = $request->header(\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE);
        $body = $request->getContent();

        try {
            $events = $this->lineBot->parseEventRequest($body, $signature);
        } catch (\Exception $e) {
            $this->errorMsg = $e->getMessage();
            return false;
        }

        foreach ($events as $event) {
            $line_user_id = $event->getUserId();

            if ($event instanceof MessageEvent) {
                $text = $event->getText();

                $action = Redis::hget($line_user_id, 'action');

                switch ($action) {
                    case 'create' :
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder('新增成功!!'));
                        Redis::hdel($line_user_id, 'action');
                        TodoList::create([
                            'user_id' => $line_user_id,
                            'event' => $text,
                            'enable' => 'N'
                        ]);
                        break;
                    default :
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($line_user_id));
                        break;
                }

//                switch ($text) {
//                    case '記帳清單' :
//                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($line_user_id));
//                        break;
//                    case '天氣' :
//                        $originalData = $this->reptileService->getOriginalData(config('linebot.url.weather'));
//                        $list = collect($this->reptileService->getWeather($originalData));
//                        $list->map(function ($item, $key) use ($event) {
//                            switch ($key) {
//                                case 'city':
//                                    $item = '地區 : '. $item;
//                                    break;
//                                case 'temperature':
//                                    $item = '溫度 : '. $item;
//                                    break;
//                                case 'phrase':
//                                    $item = '氣候 : '. $item;
//                                    break;
//                                case 'hilo':
//                                    $item = '內容 : '. $item;
//                                    break;
//                                default:
//                                    $item = 'error!!!';
//                                    break;
//                            }
//
//                            $this->pushMessage($item);
////                            $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($item));
//                        });
//                        break;
//                    default :
//                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($text));
//                        break;
//                }
            } else if ($event instanceof PostbackEvent) {
                $data = $event->getPostbackData();
                switch ($data) {
                    case 'showTodo' :
                        $message = $this->buildTodoListTemplateMessageBuilder();
                        $this->lineBot->replyMessage($event->getReplyToken(), $message);
                        break;
                    case 'createTodoList' :
                        Redis::hset($line_user_id, 'action', 'create');
                        Redis::expire($line_user_id, 120);
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder('請輸入待辦事件'));
                        break;
                    case 'showTodoList' :
                        $todoList = TodoList::select('event','enable')->where('user_id',$line_user_id)->get();

                        break;
                    default :
                        break;
                }
                $this->errorMsg = $data;
                return false;
            }
        }
        $this->errorMsg = '123';
        return false;

        return true;
    }

    public function webhook1()
    {
//        $a = Redis::hset('1','2', '3');
//        $a = Redis::hset('10','20', '30');
//        Redis::hdel('1', '2');
//        TodoList::create([
//            'user_id' => '456',
//            'event' => '87'
//        ]);
        dd(132);
    }

    /**
     * @param MessageBuilder|string $content
     * @return Response
     */
    public function pushMessage($content): Response
    {
        if (is_string($content)) {
            $content = new TextMessageBuilder($content);
        }
        return $this->lineBot->pushMessage($this->lineUserId, $content);
    }

    public function buildTemplateMessageBuilder(array $data, string $notificationText = '新通知來囉!'): MessageBuilder
    {
        $imageCarouselColumnTemplatebuilders = array_map(function ($d) {
            return $this->buildImageCarouselColumnTemplateBuilder($d['imagePath'], $d['directUri'], $d['label']);
        }, $data);

        return new TemplateMessageBuilder(
            $notificationText,
            new ImageCarouselTemplateBuilder($imageCarouselColumnTemplatebuilders)
        );
    }

    protected function buildImageCarouselColumnTemplateBuilder(string $imagePath, string $directUri, string $label): TemplateBuilder
    {
        return new ImageCarouselColumnTemplateBuilder(
            $imagePath,
            new UriTemplateActionBuilder($label, $directUri)
        );
    }

    public function buildTodoListTemplateMessageBuilder(string $notificationText = '新通知來囉!')
    {
        $createTodoList = new PostbackTemplateActionBuilder('新增待辦事項','createTodoList');
        $showTodoList = new PostbackTemplateActionBuilder('顯示待辦事項','showTodoList');

        return new TemplateMessageBuilder(
            $notificationText,
            new ButtonTemplateBuilder('1', '2', '', [$createTodoList, $showTodoList])
        );
    }
}
