<?php

namespace App\MessageHandler;

use App\Entity\News;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class NewsNotificationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private Filesystem $fs;
    private string $path;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->fs = new Filesystem();
        $this->path = Path::canonicalize(__DIR__ . '/../../public/uploads/');
    }
    
    public function __invoke(\App\Message\NewsNotification $newsNotification)
    {
        // get the content from the message
        $content = $newsNotification->getContent();

        //check if the news already exists
        $news = $this->entityManager->getRepository(News::class)->findOneBy(['title' => $content['title']]);
        if($news) {
            //update the news
            $date_added = $news->getDateAdded();
            $date_updated = $news->getDateUpdated();

            if($content['description'] !== $news->getShortDescription()) {
                $news->setDescription($content['description']);
                $date_updated = new \DateTimeImmutable();
                $news->setDateUpdated($date_updated);
            }

            echo "News already exists. Last updated on ".$date_updated->format("d-m-Y:H:i:s").PHP_EOL;
            return;

        }

        //download the image
        $image = file_get_contents($content['picture']); // TODO: s3 Cloud storage

        //validate the image mime
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($image);
        if(!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'])){
            throw new \Exception('Invalid image mime type');
        }

        $originalFileName = pathinfo($content['picture'], PATHINFO_BASENAME);
        if(!$this->fs->exists('public/uploads/news')) {
            $this->fs->mkdir('public/uploads/news');
        }

        //save the image
        $filename = md5(uniqid()) ."-$originalFileName". '.' . pathinfo($originalFileName, PATHINFO_EXTENSION);
        file_put_contents($this->path."/news/" . $filename, $image);

        $news = new News();
        $news->setTitle($content['title']);
        $news->setUrl($content['source']);
        $news->setPicture($filename);
        $news->setDateAdded(new \DateTimeImmutable());
        $news->setDateUpdated(new \DateTimeImmutable());
        $news->setShortDescription($content['description']);

        $this->entityManager->persist($news);
        $this->entityManager->flush();
        print_r($content);
    }
}