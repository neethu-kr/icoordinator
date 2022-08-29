<?php

namespace iCoordinator\Console\Command\SystemFunctions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemovePortalCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        $portalService = $container->get('PortalService');
        $portalId = $input->getArgument('portal_id');

        $portalService->permanentRemovePortal($portalId);
    }

    protected function configure()
    {
        $this
            ->setName('remove-portal')
            ->addArgument('portal_id', InputArgument::REQUIRED)
            ->setDescription('Remove trashed portal.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
