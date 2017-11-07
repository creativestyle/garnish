<?php

namespace Creativestyle\Garnish\Console;

use Creativestyle\Garnish\Config\AppConfig;
use Creativestyle\Garnish\Storage\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpConfigCommand extends Command
{
    /**
     * @var AppConfig
     */
    private $config;

    /**
     * @param AppConfig $config
     */
    public function __construct(AppConfig $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:dump')
            ->setDescription('Dumps current effective config')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Config file used <info>%s</info>.', $this->config->getConfigFilename()));
        $output->writeln(json_encode($this->config->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}