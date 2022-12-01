<?php

namespace Rdlv\WordPress\Sywo\Event;

use Symfony\Contracts\EventDispatcher\Event;

class RenderEvent extends Event
{
    public string $name;
    public array $context;

    public function __construct(string $name, array $context)
    {
        $this->name = $name;
        $this->context = $context;
    }
}