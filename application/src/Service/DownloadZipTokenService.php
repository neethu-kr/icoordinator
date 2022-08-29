<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\EntityNotFoundException;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Entity\DownloadServer;
use iCoordinator\Entity\DownloadZipToken;
use iCoordinator\Entity\DownloadZipToken\DownloadZipTokenFile;
use iCoordinator\Entity\File;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Helper\TokenHelper;
use Slim\Router;

class DownloadZipTokenService extends AbstractService
{
    const TOKEN_TTL = 30;

    /**
     * @param $file
     * @param Router $router
     * @param null $version
     * @param null $user
     * @return DownloadServer
     * @throws NotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    public function getDownloadServer($files, Router $router, $openStyle, $user = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (!count($files)) {
            throw new NotFoundException();
        }

        $token = $this->createToken($files, $user);

        $downloadServer = new DownloadServer();
        $downloadServer->setUrl(
            $router->pathFor(
                FilesRouteConfig::ROUTE_FILE_GET_ZIP_WITH_TOKEN,
                [
                    'token' => $token->getToken()
                ],
                ['open_style' => $openStyle]
            )
        )
            ->setTtl(self::TOKEN_TTL);

        return $downloadServer;
    }

    /**
     * @param $files
     * @param User|null $createdBy
     * @return DownloadToken
     * @throws NotFoundException
     * @throws \Exception
     */
    public function createToken($files, User $createdBy = null)
    {
        $token = new DownloadZipToken();
        $token->setCreatedBy($createdBy)
            ->setToken(TokenHelper::getSecureToken())
            ->setExpiresAt(Carbon::now()->addSeconds(self::TOKEN_TTL));
        foreach ($files as $fileId) {
            $file = $this->getEntityManager()->getReference(File::ENTITY_NAME, $fileId);
            $downloadZipTokenFile = new DownloadZipTokenFile();
            $downloadZipTokenFile->setFile($file);
            $token->addFile($downloadZipTokenFile);
        }
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
        return $token;
    }

    /**
     * @param $files
     * @param $created_by
     * @return DownloadZipToken|null
     * @throws NotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getToken($token, $created_by)
    {
        if (is_numeric($created_by)) {
            $userId = $created_by;
            /** @var User $deletedBy */
            $created_by = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('dzt'))
            ->from(DownloadZipToken::ENTITY_NAME, 'dzt')
            ->andWhere('dzt.token = :token')
            ->setParameter('token', $token)
            ->andWhere('dzt.created_by = :created_by')
            ->setParameter('created_by', $created_by)
            ->andWhere('dzt.expires_at >= :expires_at')
            ->setParameter(':expires_at', Carbon::now());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $token
     * @throws NotFoundException
     */
    public function deleteToken($token)
    {
        if ($token != null) {
            try {
                $this->getEntityManager()->remove($token);
                $this->getEntityManager()->flush();
            } catch (EntityNotFoundException $e) {
                throw new NotFoundException();
            }
        }
    }

    /**
     * @param $token
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function checkToken($token)
    {
        //TODO: move to separate worker job
        $qbDelete = $this->getEntityManager()->createQueryBuilder();
        $qbDelete->delete(DownloadZipToken::ENTITY_NAME, 'dzt')
            ->andWhere('dzt.expires_at < :expires_at')
            ->setParameter(':expires_at', Carbon::now());
        $qbDelete->getQuery()->execute();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('dzt'))
            ->from(DownloadZipToken::ENTITY_NAME, 'dt')
            ->andWhere('dzt.token = :token')
            ->setParameter('token', $token)
            ->andWhere('dzt.expires_at >= :expires_at')
            ->setParameter(':expires_at', Carbon::now());
        $downloadZipToken = $qb->getQuery()->getOneOrNullResult();
        if ($downloadZipToken) {
            $this->getEntityManager()->remove($downloadZipToken);
            $this->getEntityManager()->flush();
            return true;
        } else {
            return false;
        }
    }

    public function getZipItemsForFile($file)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('dztf')
            ->from(DownloadZipTokenFile::ENTITY_NAME, 'dztf')
            ->where('dztf.file = :file')
            ->setParameter('file', $file);

        $query = $qb->getQuery();
        return $query->getResult();
    }
}
