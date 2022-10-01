<?php

namespace App\Command;

use \Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\ScraperService;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\NewsNotification;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;

class ParseNewsCommand extends Command
{
    protected static $defaultName = 'app:start-parsing-news';
    private string $url_address;
    private ScraperService $scraperService;
    private MessageBusInterface $bus;

    public function __construct(ScraperService $scraperService, MessageBusInterface $bus)
    {
        $this->scraperService = $scraperService;
        $this->bus = $bus;
        $this->url_address = "https://highload.today/category/novosti/";
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to scrape a new site full of information fx: https://highload.today/category/novosti/');
        $this
            ->addArgument('url', InputArgument::OPTIONAL, 'The url of the site.');
        $this
            ->addOption('url', 'u', InputArgument::OPTIONAL, 'The url of the site.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getArgument('url')?? $input->getOption('url');

        if(!empty($url)) {
            if(!$this->isUrlValid($url)){
                $output->writeln('The url is not valid.');
                return Command::FAILURE;
            }
            $this->url_address = $url;
        }

        $output->writeln('Started Parsing News from '.$this->url_address);

        try {
            $crawler = $this->scraperService->getCrawlerInstance($this->url_address);

            if($crawler->count() > 0)
            {
                $items = $crawler->filter('.lenta-item')->reduce(function (Crawler $node, $i) {
                    return $i != 0;
                });;

                foreach ($items as $item) {
                    $crawler = new Crawler($item);
                    $title = $crawler->filter('h2')->text();
                    $description = $crawler->filter('p')->last()->text();
                    $picture = $crawler->filter('.lenta-image img')->eq(1)->attr('src');
                    $source = $crawler->filter('a')->eq(1)->attr('href');

                    $result = [
                        'Title: '.$title,
                        'Description: '.$description,
                        'Picture: '.$picture,
                        'Source: '.$source,
                        '============================',
                    ];
                    $attributes = [];
                    $this->bus->dispatch(new NewsNotification([
                        'title' => $title,
                        'description' => $description,
                        'picture' => $picture,
                        'source' => $source,
                    ]), [new AmqpStamp('news')]);
                    $output->writeln($result);


                }

                $output->writeln('Finished Scraping News from '.$this->url_address);

            }else{
                $output->writeln('Error: Nothing to parse.');
                return Command::FAILURE;
            }

//            echo $crawler->outerHtml(); //whole html
        } catch (TransportExceptionInterface $e) {
            $output->writeln('Error: '.$e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function isUrlValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}