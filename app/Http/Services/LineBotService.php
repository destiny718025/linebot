<?php


namespace App\Http\Services;

use Illuminate\Http\Request;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\Response;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;

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

                switch ($text) {
                    case '記帳清單' :
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($line_user_id));
                        break;
                    case '天氣' :
                        $originalData = $this->reptileService->getOriginalData(config('linebot.url.weather'));
                        $list = collect($this->reptileService->getWeather($originalData));
                        $list->map(function ($item, $key) use ($event) {
                            switch ($key) {
                                case 'city':
                                    $item = '地區 : '. $item;
                                    break;
                                case 'temperature':
                                    $item = '溫度 : '. $item;
                                    break;
                                case 'phrase':
                                    $item = '氣候 : '. $item;
                                    break;
                                case 'hilo':
                                    $item = '內容 : '. $item;
                                    break;
                                default:
                                    $item = 'error!!!';
                                    break;
                            }

                            $this->pushMessage($item);
//                            $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($item));
                        });
                        break;
                    default :
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($text));
                        break;
                }
            }
        }

        return true;
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
}
