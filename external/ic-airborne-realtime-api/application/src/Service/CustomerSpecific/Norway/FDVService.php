<?php

namespace iCoordinator\Service\CustomerSpecific\Norway;

use Carbon\Carbon;
use Doctrine\ORM\Tools\Pagination\Paginator;
use iCoordinator\Entity\CustomerSpecific\Norway\FDVEntry;
use iCoordinator\Entity\CustomerSpecific\Norway\FDVLicense;
use iCoordinator\Entity\Portal;
use iCoordinator\Entity\User;
use iCoordinator\Entity\Workspace;
use iCoordinator\Service\AbstractService;
use iCoordinator\Service\Exception\ValidationFailedException;
use Laminas\Hydrator\ClassMethodsHydrator;
use Slim\Http\Stream;

class FDVService extends AbstractService
{

    /**
     * @var PortalService
     */
    private $portalService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param $data
     * @param $workspace
     * @param $createdBy
     * @return FDVLogEntry
     * @throws ValidationFailedException
     * @throws \Doctrine\ORM\ORMException
     */
    public function addEntry($data, $workspace, $createdBy)
    {
        if (is_numeric($workspace)) {
            $workspaceId = $workspace;
            /** @var Workspace $workspace */
            $workspace = $this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId);
        }

        if (is_numeric($createdBy)) {
            $createdById = $createdBy;
            /** @var User $createdBy */
            $createdBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $createdById);
        }

        $fdvEntry = new FDVEntry();

        $fdvEntry->setWorkspace($workspace);
        $fdvEntry->setPortal($workspace->getPortal());
        $fdvEntry->setCreatedBy($createdBy);

        if (isset($data['installdato'])) {
            $fdvEntry->setInstallDato(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['installdato']))));
            unset($data['installdato']);
        }

        if (isset($data['garanti'])) {
            $fdvEntry->setGaranti(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['garanti']))));
            unset($data['garanti']);
        }

        $hydrator = new ClassMethodsHydrator();
        $hydrator->hydrate($data, $fdvEntry);
        $this->getEntityManager()->persist($fdvEntry);
        $this->getEntityManager()->flush();

        return $fdvEntry;
    }

    /**
     * @param $fdvEntry
     * @param array $data
     * @return FDVEntry
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateEntry($fdvEntry, array $data, $updatedBy)
    {
        if (is_numeric($fdvEntry)) {
            $fdvId = $fdvEntry;
            $fdvEntry = $this->getEntityManager()->getReference(FDVEntry::ENTITY_NAME, $fdvId);
        }
        if ($updatedBy !== null && is_numeric($updatedBy)) {
            $userId = $updatedBy;
            /** @var User $$updatedBy */
            $updatedBy = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }
        if (isset($data['installdato'])) {
            $fdvEntry->setInstallDato(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['installdato']))));
            unset($data['installdato']);
        }

        if (isset($data['garanti'])) {
            $fdvEntry->setGaranti(Carbon::parse(gmdate('Y-m-d H:i:s', strtotime($data['garanti']))));
            unset($data['garanti']);
        }

        if (isset($data['workspace'])) {
            if (is_numeric($data['workspace'])) {
                $workspaceId = $data['workspace'];
                $fdvEntry->setWorkspace($this->getEntityManager()->getReference(Workspace::ENTITY_NAME, $workspaceId));
                unset($data['workspace']);
            }
        }
        $hydrator = new ClassMethodsHydrator();
        unset($data["id"]);
        try {
            $hydrator->hydrate($data, $fdvEntry);
            $this->getEntityManager()->merge($fdvEntry);
            $this->getEntityManager()->flush();
        } catch (EntityNotFoundException $e) {
            throw new NotFoundException();
        }

        return $fdvEntry;
    }

    public function getPortalEntries($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('fdv'))
            ->from(FDVEntry::ENTITY_NAME, 'fdv')
            ->orderBy('fdv.id', 'ASC');

        if ($portal != null) {
            $qb->andWhere('fdv.portal = :portal')
                ->setParameter('portal', $portal->getId());
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);

        return $paginator;
    }

    public function getPortalLicense($portal)
    {
        if (is_numeric($portal)) {
            $portalId = $portal;
            /** @var Portal $portal */
            $portal = $this->getEntityManager()->getReference(Portal::ENTITY_NAME, $portalId);
        }

        $license = $this->getEntityManager()->getRepository(FDVLicense::ENTITY_NAME)->findOneBy(array(
            'portal' => $portal
        ));

        return $license;
    }

    public function removeEntriesForWorkspace($workspace)
    {
        $em = $this->getEntityManager();
        $pdo = $em->getConnection();
        $sql = 'DELETE FROM fdv_entries where workspace_id='.$workspace->getId();

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    public function getExportData($portal)
    {

        $tmpFile = tmpFile();
        $exportStream = new Stream($tmpFile);
        $entries = $this->getPortalEntries($portal)->getIterator()->getArrayCopy();
        $csv =
            "Arbeidsområde\t" .
            "Selskapsnr\t" .
            "Selskapsnavn\t" .
            "Gnr/Bnr\t" .
            "Eiendom navn\t" .
            "Bygningsnr\t" .
            "Bygning\t" .
            "Bygningsdel\t" .
            "System navn\t" .
            "Systemtype nr\t" .
            "Komponent nr\t" .
            "Komponent navn\t" .
            "Komponenttype nr\t" .
            "Komponentkategori nr\t" .
            "Fabrikat\t" .
            "Typebetegnelse\t" .
            "Systemleverandør\t" .
            "Install.dato\t" .
            "Notat\t" .
            "Garanti\t" .
            "Antal service pr. år\t" .
            "TFM\r";
        foreach ($entries as $entry) {
            $csv .= $entry->getCSV() . "\r";
        }
        $csv = chr(255) . chr(254) . mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
        $exportStream->write($csv);
        return $exportStream;
    }

    /**
     * @return PortalService
     */
    public function getPortalService()
    {
        if (!$this->portalService) {
            $this->portalService = $this->getContainer()->get('PortalService');
        }
        return $this->portalService;
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        if (!$this->userService) {
            $this->userService = $this->getContainer()->get('UserService');
        }
        return $this->userService;
    }
}
