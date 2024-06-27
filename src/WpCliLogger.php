<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\Console\Output\OutputInterface;

class WpCliLogger
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function __call($name, $arguments)
    {
        $method = in_array($name, ['error', 'warning']) ? $name : 'writeln';
        $this->output->$method($arguments[0]);
    }
}