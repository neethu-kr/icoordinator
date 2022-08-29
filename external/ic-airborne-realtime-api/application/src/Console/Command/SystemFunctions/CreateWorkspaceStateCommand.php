<?php

namespace iCoordinator\Console\Command\SystemFunctions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateWorkspaceStateCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        $stateService = $container->get('StateService');
        $authToken = $input->getArgument('auth_token');
        $workspace = $input->getArgument('workspace');
        $clientState = $input->getArgument('client_state');
        $slimState = $input->getArgument('slim_state');
        $stateService->createWorkspaceState($authToken, $workspace, $clientState, $slimState);
    }

    protected function configure()
    {
        $this
            ->setName('create-workspace-state')
            ->addArgument('auth_token', InputArgument::REQUIRED)
            ->addArgument('workspace', InputArgument::REQUIRED)
            ->addArgument('client_state', InputArgument::REQUIRED)
            ->addArgument('slim_state', InputArgument::REQUIRED)
            ->setDescription('Create state.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
