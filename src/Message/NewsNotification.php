<?php

namespace App\Message;

class NewsNotification
{
    private array $content;

    public function __construct(array $content)
    {
        $this->content = $content;
    }

    public function getContent(): array
    {
        return $this->content;
    }
}