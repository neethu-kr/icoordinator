<?php

namespace iCoordinator\Console\Command\EventNotification;

use iCoordinator\Factory\Service\OutboundEmail\AdapterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEventInstantNotificationsCommand extends Command
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

        $eventNotificationService = $container->get('EventNotificationService');
        $eventNotificationService->sendEventNotifications(true);
    }

    protected function configure()
    {
        $this
            ->setName('send-event-instant-notifications')
            ->setDescription('Send instant event notifications to subscribing users.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
