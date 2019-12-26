<?php

namespace App\Console\Commands;

use App\Services\LineBotService;
use App\Services\ReptileService;
use Illuminate\Console\Command;

class PushWeather extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $path;
    private $reptileService;
    private $lineBotService;

    /**
     * Create a new command instance.
     *
     * @param ReptileService $reptileService
     * @param LineBotService $lineBotService
     */
    public function __construct(ReptileService $reptileService, LineBotService $lineBotService)
    {
        parent::__construct();
        $this->path = config('linebot.url.weather');
        $this->reptileService = $reptileService;
        $this->lineBotService = $lineBotService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $originalData = $this->reptileService->getOriginalData($this->path);
        $list = collect($this->reptileService->getWeather($originalData));

        $list->map(function ($item, $key) {
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

            $this->lineBotService->pushMessage($item);
        });
    }
}
