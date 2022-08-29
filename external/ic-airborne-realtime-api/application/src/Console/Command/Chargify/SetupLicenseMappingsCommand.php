<?php

namespace iCoordinator\Console\Command\Chargify;

use Doctrine\ORM\EntityManager;
use iCoordinator\Entity\License;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupLicenseMappingsCommand extends Command
{

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getHelperSet()->get('container')->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get('entityManager');

        $mappingsConfig = include implode(DIRECTORY_SEPARATOR, [
            $container->get('settings')['applicationPath'],
            'data',
            'chargify',
            'mapping.php'
        ]);

        $chargifyMapperRepository = $entityManager->getRepository(License\ChargifyMapper::ENTITY_NAME);

        foreach ($mappingsConfig['licenses'] as $licenseData) {
            $license = $entityManager->find(License::ENTITY_NAME, $licenseData['id']);
            if (!$license) {
                $license = new License();
            }

            $license->setUsersLimit($licenseData['users_limit'])
                ->setWorkspacesLimit($licenseData['workspaces_limit'])
                ->setStorageLimit($licenseData['storage_limit']);


            foreach ($licenseData['mappers'] as $licenseMapperData) {
                $mapper = $chargifyMapperRepository->findOneBy([
                    'license' => $license,
                    'chargify_website_id' => $licenseMapperData['chargify_website_id']
                ]);
                if (!$mapper) {
                    $mapper = new License\ChargifyMapper();
                    $mapper->setChargifyWebsiteId($licenseMapperData['chargify_website_id']);
                    $license->addChargifyMapper($mapper);
                }

                $mapper->setChargifyProductHandle($licenseMapperData['chargify_product_handle'])
                    ->setChargifyUsersComponentIds($licenseMapperData['chargify_users_component_ids'])
                    ->setChargifyWorkspacesComponentIds($licenseMapperData['chargify_workspaces_component_ids'])
                    ->setChargifyStorageComponentIds($licenseMapperData['chargify_storage_component_ids']);

                if (!$mapper->getId()) {
                    $entityManager->persist($mapper);
                }
            }

            if (!$license->getId()) {
                $license->setId($licenseData['id']);
                $entityManager->persist($license);
            }
        }

        $entityManager->flush();
    }

    protected function configure()
    {
        $this
            ->setName('setup-mappings')
            ->setDescription('Sets up mapping to chargify products and components')
            ->setHelp(
                <<<EOT
EOT
            );

        parent::configure();
    }
}
