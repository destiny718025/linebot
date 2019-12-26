<?php

namespace Tests\Feature;

use App\Http\Services\LineBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LineBotServiceTest extends TestCase
{
    private $lineBotService;

    public function setUp(): void
    {
        parent::setUp();
        $this->lineBotService = app(LineBotService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testPushMessage()
    {
//        $this->markTestSkipped('OK!');
        $response = $this->lineBotService->pushMessage('Hello, World!!');

        $this->assertEquals(200, $response->getHTTPStatus());
    }

    public function testPushMessageWithObjectDeprecated()
    {
        $data = [
            [
                'imagePath' => 'https://media-mbst-pub-ue1.s3.amazonaws.com/creatr-uploaded-images/2019-11/c9d3a350-013b-11ea-becf-2af63e89b13d',
                'directUri' => 'https://github.com/destiny718025',
                'label' => '測試'
            ],
            [
                'imagePath' => 'https://media-mbst-pub-ue1.s3.amazonaws.com/creatr-uploaded-images/2019-11/c9d3a350-013b-11ea-becf-2af63e89b13d',
                'directUri' => 'https://github.com/destiny718025',
                'label' => '123465'
            ]
        ];

        $target = $this->lineBotService->buildTemplateMessageBuilder($data);
        $response = $this->lineBotService->pushMessage($target);

        $this->assertEquals(200, $response->getHTTPStatus());
    }
}
