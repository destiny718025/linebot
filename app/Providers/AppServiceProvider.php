<?php

namespace App\Providers;

use App\Http\Services\LineBotService;
use App\Http\Services\ReptileService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLineBot();
        $this->registerLineBotService();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function registerLineBot()
    {
        $this->app->singleton('LineBot', function () {
            $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(config('linebot.channel.access_token'));
            return new \LINE\LINEBot($httpClient, ['channelSecret' => config('linebot.channel.secret')]);
        });
    }

    public function registerLineBotService()
    {
        $this->app->singleton(LineBotService::class, function () {
            $reptileService = new ReptileService();
            return new LineBotService(config('linebot.channel.line_user_id'), $reptileService);
        });
    }
}
