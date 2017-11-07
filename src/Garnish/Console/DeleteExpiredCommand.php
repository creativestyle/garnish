<?php

namespace Creativestyle\Garnish\Console;

use Creativestyle\Garnish\Config\AppConfig;
use Creativestyle\Garnish\Storage\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredCommand extends Command
{
    /**
     * @var AppConfig
     */
    private $config;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param AppConfig $config
     * @param StorageInterface $storage
     */
    public function __construct(AppConfig $config, StorageInterface $storage)
    {
        parent::__construct();

        $this->config = $config;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('delete:expired')
            ->setDescription('Deletes cached pictures that are expired')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $maxLifetime = $this->config->getRequired('max_lifetime');

        if (!$maxLifetime) {
            $output->writeln('Max cache lifetime is disabled. Everything is stored indefinitely. <warning>Exiting.</warning>');
            return;
        }

        $since = new \DateTime($maxLifetime . ' ago');

        $output->writeln(sprintf('Max lifetime is set to <info>%s</info>. Removing all data created before <info>%s</info>.',
            $maxLifetime,
            $since->format('Y-m-d H:i:s')
        ));

        $removed = $this->storage->cleanup($since);

        $output->writeln(sprintf('Successfully removed <info>%d</info> files.', $removed));
    }
}