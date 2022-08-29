<?php

namespace iCoordinator\Console\Command\SystemFunctions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateFoldersStateCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        $stateService = $container->get('StateService');
        $authToken = $input->getArgument('auth_token');
        $folder = $input->getArgument('folder');
        $clientState = $input->getArgument('client_state');
        $slimState = $input->getArgument('slim_state');
        $stateService->createFolderTreeState($authToken, $folder, $clientState, $slimState);
    }

    protected function configure()
    {
        $this
            ->setName('create-folders-state')
            ->addArgument('auth_token', InputArgument::REQUIRED)
            ->addArgument('folder', InputArgument::REQUIRED)
            ->addArgument('client_state', InputArgument::REQUIRED)
            ->addArgument('slim_state', InputArgument::REQUIRED)
            ->setDescription('Create folder tree state.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
