<?php

namespace Rdlv\WordPress\Sywo\Console;

use Exception;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use WP_Site;

class Environment
{
    /** @var InputInterface */
    private $input;

    /** @var SymfonyStyle */
    private $output;

    public function __construct()
    {
    }

    public function run(KernelInterface $kernel): int
    {
        try {
            if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
                throw new Exception('Warning: The console should be invoked via the CLI version of PHP, not the ' . PHP_SAPI . ' SAPI' . PHP_EOL);
            }

            $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);

            $definition = $application->getDefinition();
            if (!$definition->hasOption('url') && !$definition->hasOption('u') && !$definition->hasShortcut('u')) {
                $definition->addOption(new InputOption('--url', '-u', InputOption::VALUE_REQUIRED, 'The website url.'));
            }
            return $application->run($this->input, $this->output);
        } catch (Throwable $e) {
            $this->output->error($e->getMessage());
            return 1;
        }
    }

    /**
     * @param InputInterface|null $input
     * @param OutputStyle|null $output
     * @return void
     * @throws Exception
     */
    public function load(InputInterface $input = null, OutputStyle $output = null): self
    {
        $this->input = $input ?: new ArgvInput();
        $this->output = new SymfonyStyle($this->input, $output ?: new ConsoleOutput());

        $url = $this->input->getParameterOption('--url') ?: 'http://localhost';
        $parts = parse_url($url);
        $https = $parts['scheme'] === 'https';

        if ($https) {
            $_SERVER['HTTPS'] = 'on';
        }
        $_SERVER['SERVER_PROTOCOL'] = $https ? 'HTTP/2.0' : 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = $parts['host'];
        $_SERVER['REQUEST_URI'] = $parts['path'] ?? '/';

        if ($_SERVER['HTTP_HOST'] === 'localhost') {
            /** WP_Hook[] $wp_filter */
            global $wp_filter;
            $wp_filter = [
                'pre_get_site_by_path' => [
                    10 => [
                        [
                            'accepted_args' => 5,
                            /**
                             * This hook is documented in ms-load.php
                             *
                             * @param null|false|WP_Site $site Site value to return by path. Default null
                             *                                     to continue retrieving the site.
                             * @param string $domain The requested domain.
                             * @param string $path The requested path, in full.
                             * @param int|null $segments The suggested number of paths to consult.
                             *                                     Default null, meaning the entire path was to be consulted.
                             * @param string[] $paths The paths to search for, based on $path and $segments.
                             */
                            'function'      => function ($site, $domain, $path) {
                                if ($domain === 'localhost') {
                                    global $current_site;
                                    $result = get_sites(
                                        [
                                            'number'                 => 1,
                                            'update_site_meta_cache' => false,
                                            'domain'                 => $current_site->domain,
                                            'path'                   => '/',
                                        ]
                                    );
                                    /** @var WP_Site $site */
                                    $site = array_shift($result);
                                    $this->output->warning(sprintf('Warning: Using default blog %s',
                                                                   $site->domain));
                                }
                                return $site;
                            },
                        ],
                    ],
                ],
            ];
        }

        // load WordPress wp-config.php
        $path = $_SERVER['PWD'];
        while ($path !== '/' && !file_exists($path . '/wp-config.php')) {
            $path = dirname($path);
        }
        if (!file_exists($path . '/wp-config.php')) {
            throw new Exception('Can not found wp-config.php in parent directories.');
        }

        if ($this->input->hasParameterOption('--env')) {
            define('WP_ENVIRONMENT_TYPE', $this->input->getParameterOption('--env'));
        }

        require_once $path . '/wp-config.php';

        return $this;
    }
}