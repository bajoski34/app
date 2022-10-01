<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    private Client $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = new Client();
    }

    /**
     * @retyrn Crawler
     */
    public function getCrawlerInstance(string $url): Crawler
    {
        return $this->client->request('GET', $url);
    }
}