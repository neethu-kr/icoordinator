<?php

namespace iCoordinator\Console\Command\SystemFunctions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FinishUploadCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        $fileService = $container->get('FileService');
        $fileId = $input->getArgument('file_id');
        $uploadId = $input->getArgument('upload_id');
        $userId = $input->getArgument('user_id');
        $clientVersion = $input->getArgument('client_version');
        $etag = $input->getArgument('etag');
        $oldHash = $input->getArgument('old_hash');
        $oldContentModifiedAt = $input->getArgument('old_content_modified_at');

        $fileService->finishUpload($fileId, $uploadId, $userId, $clientVersion, $etag, $oldHash, $oldContentModifiedAt);
    }

    protected function configure()
    {
        $this
            ->setName('finish-upload')
            ->addArgument('file_id', InputArgument::REQUIRED)
            ->addArgument('upload_id', InputArgument::REQUIRED)
            ->addArgument('user_id', InputArgument::REQUIRED)
            ->addArgument('client_version', InputArgument::REQUIRED)
            ->addArgument('etag', InputArgument::OPTIONAL)
            ->addArgument('old_hash', InputArgument::OPTIONAL)
            ->addArgument('old_content_modified_at', InputArgument::OPTIONAL)
            ->setDescription('Finish file upload.')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
