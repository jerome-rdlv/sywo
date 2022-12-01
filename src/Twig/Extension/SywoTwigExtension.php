<?php

namespace Rdlv\WordPress\Sywo\Twig\Extension;

use Rdlv\WordPress\Sywo\Hooks;
use Symfony\Component\Translation\Formatter\IntlFormatter;
use Symfony\Component\Translation\Formatter\IntlFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SywoTwigExtension extends AbstractExtension
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var IntlFormatter */
    private $intlFormatter;

    public function __construct(
        TranslatorInterface $translator,
        IntlFormatterInterface $intlFormatter = null,
        Hooks $hooks
    ) {
        $this->translator = $translator;
        $this->intlFormatter = $intlFormatter ?: (class_exists('\Symfony\Component\Translation\Formatter\IntlFormatter') ? new IntlFormatter() : null);
        $this->hooks = $hooks;
    }

    public function getFilters()
    {
        $filters = [
            new TwigFilter('wp_date', [$this, 'formatDate']),

        ];
        if ($this->translator && $this->intlFormatter) {
            $filters[] = new TwigFilter('format_intl', [$this, 'formatIntl']);
        }
        return $filters;
    }

    public function formatDate(\DateTime $date, string $format): string
    {
        return wp_date($format, $date->getTimestamp());
    }

    public function formatIntl(string $pattern, array $parameters): string
    {
        return $this->intlFormatter->formatIntl($pattern, $this->translator->getLocale(), $parameters);
    }
}