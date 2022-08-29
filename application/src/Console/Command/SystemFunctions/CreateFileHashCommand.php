<?php

namespace iCoordinator\Console\Command\SystemFunctions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateFileHashCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        $fileService = $container->get('FileService');
        $fileService->createFileHash();
    }

    protected function configure()
    {
        $this
            ->setName('create-file-hash')
            ->setDescription('Create file hash for existing file entries.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
