<?php

namespace iCoordinator\Console\Command\Mandrill;

use iCoordinator\Factory\Service\OutboundEmail\AdapterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupTemplatesCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();
        $emailConfig = $container->get('settings')['email'];

        if ($emailConfig['adapter'] != AdapterFactory::ADAPTER_TYPE_MANDRILL) {
            $output->writeln('<error>This command is only available when using Mandrill adapter!</error>');
            return 1;
        }

        $mandrillAdapter = AdapterFactory::createAdapter($container);
        $mandrillAdapter->setupTemplates();
    }

    protected function configure()
    {
        $this
            ->setName('setup-templates')
            ->setDescription('Create templates inside Mandrill account.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
