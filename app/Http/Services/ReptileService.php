<?php


namespace App\Http\Services;


use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ReptileService
{
    private $client;

    public function __construct()
    {
        $this->client = app(Client::class);
    }

    public function getOriginalData(string $path): Crawler
    {
        $content = $this->client->get($path)->getBody()->getContents();
        $crawler = app(Crawler::class);
        $crawler->addHtmlContent($content);

        return $crawler;
    }

    public function getWeather(Crawler $crawler)
    {
        $target = $crawler->filterXPath('//div[contains(@class, "today_nowcard")]')
            ->each(function ($node) {
                $city = $this->getTextFromNode($node, '//h1[contains(@class, "today_nowcard-location")]');
                $temperature = $this->getTextFromNode($node, '//div[contains(@class, "today_nowcard-temp")]');
                $phrase = $this->getTextFromNode($node, '//div[contains(@class, "today_nowcard-phrase")]');
                $hilo = $this->getTextFromNode($node, '//div[contains(@class, "today_nowcard-hilo")]');

                $Response = array(
                    'city' => isset($city[0]) ? $city[0] : null,
                    'temperature' => isset($temperature[0]) ? $temperature[0] : null,
                    'phrase' => isset($phrase[0]) ? $phrase[0] : null,
                    'hilo' => isset($hilo[0]) ? $hilo[0] : null
                );

                return in_array(null, array_values($Response)) ? null : $Response;
            });

        return $target[0];
    }

    public function getComic(Crawler $crawler)
    {
//        $imagePath = $crawler->filterXPath('//div[contains(@class, "cDefaultImg")]/img')->attr('src');
        $imagePath = 'https://media-mbst-pub-ue1.s3.amazonaws.com/creatr-uploaded-images/2019-11/c9d3a350-013b-11ea-becf-2af63e89b13d';
        $target = $crawler->filterXPath('//a[contains(@title, "updated")]')
            ->each(function ($node) use ($imagePath) {
                $date = $node->attr('title');
                $date = str_replace('updated ','',$date);
                $directUri = $node->attr('href');
                $label = $node->text();

                $Response = array(
                    'date' => isset($date) ? $date : null,
                    'directUri' => isset($directUri) ? $directUri : null,
                    'imagePath' => isset($imagePath) ? $imagePath : null,
                    'label' => isset($label) ? $label : null
                );

                return in_array(null, array_values($Response)) ? null : $Response;
            });

        return $target;
    }

    public function getTextFromNode(Crawler $node, String $xpath)
    {
        return $node->filterXPath($xpath)
            ->each(function ($node) {
                return $node->text();
            });
    }
}
