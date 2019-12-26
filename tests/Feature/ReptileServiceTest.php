<?php

namespace Tests\Feature;

use App\Http\Services\LineBotService;
use App\Http\Services\ReptileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReptileServiceTest extends TestCase
{
    private $reptileService;
    private $lineBotService;

    public function setUp(): void
    {
        parent::setUp();
        $this->reptileService = app(ReptileService::class);
        $this->lineBotService = app(LineBotService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetOriginalData()
    {
        $this->markTestSkipped('OK!');
        $crawler = $this->reptileService->getOriginalData('https://weather.com/zh-TW/weather/today/l/84937a912ce28623ee3e6b52266e46d2db0cd230da32e145281a14f3112d6098');

        $this->assertNotEmpty($crawler->html());
    }

    public function testGetWeather()
    {
        $this->markTestSkipped('OK!');
        $crawler = $this->reptileService->getOriginalData('https://weather.com/zh-TW/weather/today/l/84937a912ce28623ee3e6b52266e46d2db0cd230da32e145281a14f3112d6098');
        $target = $this->reptileService->getWeather($crawler);

        $this->assertArrayHasKey('city', $target);
        $this->assertArrayHasKey('temperature', $target);
        $this->assertArrayHasKey('phrase', $target);
        $this->assertArrayHasKey('hilo', $target);
    }

    public function testGetComic()
    {
        $crawler = $this->reptileService->getOriginalData('http://99770.hhxxee.com/comic/29433/');
        $target = collect($this->reptileService->getComic($crawler));

        $target->map(function ($items, $key) {
            $this->assertArrayHasKey('date', $items);
            $this->assertArrayHasKey('directUri', $items);
            $this->assertArrayHasKey('imagePath', $items);
            $this->assertArrayHasKey('label', $items);
        });
    }
}
