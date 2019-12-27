<?php


namespace App\Services;

use App\models\TodoList;
use App\Repositories\TodoListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ButtonComponentBuilder;
use LINE\LINEBot\Response;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;

class LineBotService
{
    private $lineUserId;
    private $lineBot;
    private $reptileService;
    private $todoListRepository;

    public $errorMsg;

    public function __construct(string $lineUserId, ReptileService $reptileService, TodoListRepository $todoListRepository)
    {
        $this->lineUserId = $lineUserId;
        $this->lineBot = app('LineBot');
        $this->reptileService = $reptileService;
        $this->todoListRepository = $todoListRepository;
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
                        $this->todoListRepository->createTodoList([
                            'user_id' => $line_user_id,
                            'event' => $text,
                            'enable' => 'N'
                        ]);
                        break;
                    default :
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder($line_user_id));
                        break;
                }
            } else if ($event instanceof PostbackEvent) {
                $message = '';
                $dataArr = [];
                $data = $event->getPostbackData();
                $tmp = explode('&', $data);
                foreach ($tmp as $item) {
                    $tmpArr = explode('=', $item);
                    $dataArr[$tmpArr[0]] = $tmpArr[1];
                }
                switch ($dataArr['action']) {
                    case 'createTodoList' :
                        Redis::hset($line_user_id, 'action', 'create');
                        Redis::expire($line_user_id, 120);
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder('請輸入待辦事項'));
                        break;
                    case 'showTodoList' :
                        $todoList = $this->todoListRepository->showTodoListByUser_Id($line_user_id);
                        if($todoList === null) {
                            $message = new TextMessageBuilder('無待辦事項，請先新增!');
                        }
                        $message = $this->buildTodoListFlexMessage($todoList);
                        $this->lineBot->replyMessage($event->getReplyToken(), $message);
                        break;
                    case 'completeTodoList' :
                        $updateData = ['enable' => 'Y'];
                        $this->todoListRepository->updateTodoListById($updateData, $line_user_id, $dataArr['event_id']);
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder('完成!!'));
                        break;
                    case 'deleteTodoList' :
                        $this->todoListRepository->deleteTodoListById($line_user_id, $dataArr['event_id']);
                        $this->lineBot->replyMessage($event->getReplyToken(), new TextMessageBuilder('已刪除!!'));
                        break;
                    default :
                        break;
                }
                $this->errorMsg = $message;
                return false;
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

    public function buildTodoListMeanTemplateMessageBuilder(string $notificationText = '新通知來囉!'): MessageBuilder
    {
        $createTodoList = new PostbackTemplateActionBuilder('新增待辦事項','action=createTodoList');
        $showTodoList = new PostbackTemplateActionBuilder('顯示待辦事項','action=showTodoList');

        return new TemplateMessageBuilder(
            $notificationText,
            new ButtonTemplateBuilder('1', '2', '', [$createTodoList, $showTodoList])
        );
    }

    public function buildTodoListFlexMessage(Collection $payload, string $notificationText = '新通知來囉!')
    {
        $ContainerBuilder = $payload->map(function ($items) {
            $bodyTextComponentBuilder[] = new TextComponentBuilder($items['event'], null, null, 'xl', 'center', null, null, null, 'bold');
            $bodyComponentBuilder = new BoxComponentBuilder(
                'vertical',
                $bodyTextComponentBuilder
            );

            if($items['enable'] === 'N') {
                $statusTodoList = new PostbackTemplateActionBuilder('完成','action=completeTodoList&event_id='.$items['id']);
                $style = 'primary';
            } else {
                $statusTodoList = new PostbackTemplateActionBuilder('已完成','action=completedTodoList&event_id='.$items['id']);
                $style = 'secondary';
            }
            $deleteTodoList = new PostbackTemplateActionBuilder('刪除','action=deleteTodoList&event_id='.$items['id']);

            $footerButtonComponentBuilder[] = new ButtonComponentBuilder($statusTodoList, null, null, 'sm', $style, null, null);
            $footerButtonComponentBuilder[] = new ButtonComponentBuilder($deleteTodoList, null, null, 'sm', 'primary', null, null);
            $footerComponentBuilder = new BoxComponentBuilder(
                'horizontal',
                $footerButtonComponentBuilder
            );

            return new BubbleContainerBuilder(null, null, null, $bodyComponentBuilder, $footerComponentBuilder, null, 'mega');
        })->toArray();

        return new FlexMessageBuilder(
            $notificationText,
            new CarouselContainerBuilder($ContainerBuilder)
        );
    }
}
