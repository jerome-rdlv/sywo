<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\Console\Output\OutputInterface;

class WpCliLogger
{
    /** @var OutputInterface */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function __call($name, $arguments)
    {
        $method = in_array($name, ['error', 'warning']) ? $name : 'writeln';
        $this->output->$method($arguments[0]);
    }
}