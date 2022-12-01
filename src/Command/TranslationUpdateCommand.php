<?php

namespace Rdlv\WordPress\Sywo\Command;

use Symfony\Bundle\FrameworkBundle\Command\TranslationUpdateCommand as SfTranslationUpdateCommand;
use Exception;
use Rdlv\WordPress\Sywo\WpCliLogger;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use WP_CLI;
use WP_CLI\I18n\MakePotCommand;

class TranslationUpdateCommand extends Command
{
    protected static $defaultName = 'sywo:translations:update';

    /** @var SfTranslationUpdateCommand */
    private $translationUpdateCommand;

    private $defaultTransPath;
    private $transPaths;

    /** @var [] */
    private $headers = null;

    public function __construct(
        SfTranslationUpdateCommand $translationUpdateCommand,
        string $defaultTransPath = null,
        array $transPaths = []
    ) {
        parent::__construct();

        $this->translationUpdateCommand = $translationUpdateCommand;
        $this->defaultTransPath = $defaultTransPath;
        $this->transPaths = $transPaths;
    }

    protected function configure()
    {
        $this->setDefinition(
            [
                new InputArgument('bundle', InputArgument::OPTIONAL,
                                  'The bundle name or directory where to load the messages'),
                new InputOption('domain', 'd', InputOption::VALUE_REQUIRED, 'Specify the domain to extract'),
                new InputOption('path', 'p', InputOption::VALUE_REQUIRED, 'Domain path (languages)'),
            ]
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        if (!$this->getDomain($input)) {
            $output->error('Domain not found in plugin meta, use --domain option');
            return 1;
        }

        try {
            $output->writeln('Extract from Twig templates');
            $this->extractFromSymfony($input, $output);
            $output->writeln('Extract from WordPress');
            $this->extractFromWordPress($input, $output);
            $output->writeln('Update translations from POT');
            $this->updateTranslations($input, $output);
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param string $header
     * @return mixed|null
     */
    private function getHeader(string $header)
    {
        if ($this->headers === null) {
            $this->headers = [];

            foreach (Finder::create()->depth('== 0')->files()->name('*.php')->in($this->getProjectDir()) as $file) {
                $headers = get_plugin_data($file);
                if (!empty($headers['Name'])) {
                    $this->headers = $headers;
                    break;
                }
            }
        }
        return $this->headers[$header] ?? null;
    }

    private function getProjectDir(): string
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->getApplication()->getKernel();
        return $kernel->getProjectDir();
    }

    private function getBundleTransPath(): string
    {
        $transPaths = $this->transPaths;
        if ($this->defaultTransPath) {
            $transPaths[] = $this->defaultTransPath;
        }

        $bundleTransPath = false;
        foreach ($transPaths as $path) {
            if (is_dir($path)) {
                $bundleTransPath = $path;
            }
        }

        if (!$bundleTransPath) {
            $bundleTransPath = end($transPaths);
        }

        return $bundleTransPath;
    }

    /**
     * @param $domain
     * @param string $locale
     * @param SymfonyStyle $output
     * @return void
     * @throws Exception
     */
    protected function extractFromSymfony(InputInterface $input, OutputInterface $output): void
    {
        $domain = $this->getDomain($input);
        $locale = '__';
        $command = clone $this->translationUpdateCommand;
        $args = [
            '--force' => true,
            '--prefix' => '',
            '--format' => 'po',
            'locale' => $locale,
        ];
        if ($domain) {
            $args['--domain'] = $domain;
        }
        if ($bundle = $input->getArgument('bundle')) {
            $args['bundle'] = $bundle;
        }
        $subCommandInput = new ArrayInput($args, $command->getDefinition());
        $subCommandInput->setInteractive(false);

        $command->setApplication($this->getApplication());
        $command->run($subCommandInput, $output);

        $transPath = $this->getBundleTransPath();
        $po = sprintf('%s/%s+intl-icu.%s.po', $transPath, $domain, $locale);
        if (file_exists($po)) {
            $pot = sprintf('%s/%s.twig.pot', $transPath, $domain);
            rename($po, $pot);
        }
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle $output
     * @return void
     * @throws Exception
     */
    protected function extractFromWordPress(InputInterface $input, SymfonyStyle $output): void
    {
        if (!class_exists('WP_CLI')) {
            throw new Exception('WP_CLI is not loaded');
        }

        $domain = $this->getDomain($input);
        $path = $this->getPath($input);

        $potPath = sprintf('%s/%s/%s.pot', $this->getProjectDir(), $path, $domain);
        $args = [
            $this->getProjectDir(),
            $potPath,
        ];
        $assoc_args = [
            'merge' => sprintf('%s/%s/%s.twig.pot', $this->getProjectDir(), $path, $domain),
            'skip-js' => true,
            'domain' => $domain,
        ];

        $this->loadwpCli($output);

        $makePotCommand = new MakePotCommand();
        $makePotCommand($args, $assoc_args);

        $regex = '/^"(' . implode('|', [
                'Report-Msgid-Bugs-To',
                'Last-Translator',
                'Language-Team',
                'POT-Creation-Date',
                'PO-Revision-Date',
            ]) . '): /';
        file_put_contents($potPath, implode('', array_filter(file($potPath), function ($line) use ($regex) {
            return !preg_match($regex, $line);
        })));
    }

    private function updateTranslations(InputInterface $input, SymfonyStyle $output)
    {
        $commands = [
            'msgmerge -V' => 'msgmerge command not available for PO file update.',
            'msgfmt -V' => 'msgfmt command not available for MO file generation.',
        ];
        foreach ($commands as $command => $message) {
            exec($command, $out, $result);
            if ($result !== 0) {
                throw new Exception($message);
            }
        }

        $domain = $this->getDomain($input);
        $path = $this->getProjectDir() . '/' . $this->getPath($input);

        $finder = new Finder();
        $finder->files()->in($path)->name(sprintf('%s.*.po', $domain))->name(sprintf('%s+intl-icu.*.po', $domain));

        // update po file
        $output->writeln('Update PO files');
        unset($out);

        $pot = sprintf('%s/%s.pot', $path, $domain);
        foreach ($finder as $po) {
            exec(sprintf('msgmerge --previous --no-location -U "%1$s" "%2$s"', $po, $pot), $out);
            $output->writeln($out);
        }

        // generate mo file
        $output->writeln('Generate MO files');
        unset($out);

        foreach ($finder as $po) {
            exec(sprintf('msgfmt --use-fuzzy "%1$s" -o "%2$s"', $po, preg_replace('/(\.po)$/', '.mo', $po)), $out);
            $output->writeln($out);
        }
    }

    private function loadWpCli(OutputInterface $output)
    {
        $wpCliRoot = dirname(dirname((new ReflectionClass(WP_CLI::class))->getFileName()));
        if (!defined('WP_CLI_VERSION')) {
            define('WP_CLI_VERSION', trim(file_get_contents($wpCliRoot . '/VERSION')));
        }
        WP_CLI::set_logger(new WpCliLogger($output));
        require_once $wpCliRoot . '/php/utils.php';
    }

    /**
     * @param InputInterface $input
     * @return mixed|null
     */
    protected function getDomain(InputInterface $input)
    {
        return $input->getOption('domain') ?: $this->getHeader('TextDomain');
    }

    /**
     * @param InputInterface $input
     * @return mixed|null
     */
    protected function getPath(InputInterface $input)
    {
        return trim($input->getOption('path') ?: $this->getHeader('DomainPath') ?: 'translations', '/');
    }
}