<?php

namespace Rdlv\WordPress\Sywo;

interface ShortcodeListenerInterface
{
    public function load(string $shortcode, array $attributes, $content);

    public function output(string $shortcode, array $attributes, $content);

    public function updated(string $shortcode, array $ids);
}