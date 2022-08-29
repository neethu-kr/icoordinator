<?php

namespace iCoordinator\Service;

use Carbon\Carbon;
use Doctrine\ORM\EntityNotFoundException;
use iCoordinator\Config\Route\FilesRouteConfig;
use iCoordinator\Entity\DownloadServer;
use iCoordinator\Entity\DownloadToken;
use iCoordinator\Entity\File;
use iCoordinator\Entity\Folder;
use iCoordinator\Entity\SmartFolder;
use iCoordinator\Entity\User;
use iCoordinator\Service\Exception\NotFoundException;
use iCoordinator\Service\Helper\TokenHelper;
use Slim\Router;

class DownloadTokenService extends AbstractService
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
    public function getDownloadServer($file, Router $router, $openStyle, $version = null, $user = null)
    {
        if (is_numeric($user)) {
            $userId = $user;
            /** @var User $user */
            $user = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            /** @var File $file */
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $token = $this->createToken($file, $user);

        $downloadServer = new DownloadServer();
        $downloadServer->setUrl(
            $router->pathFor(
                FilesRouteConfig::ROUTE_FILE_GET_CONTENT_WITH_TOKEN,
                [
                    'file_id' => $token->getFile()->getId(),
                    'token' => $token->getToken()
                ],
                ($version ? ['version' => $version, 'open_style' => $openStyle] : ['open_style' => $openStyle])
            )
        )
        ->setTtl(self::TOKEN_TTL);

        return $downloadServer;
    }

    /**
     * @param File $file
     * @param User|null $createdBy
     * @return DownloadToken
     * @throws NotFoundException
     * @throws \Exception
     */
    public function createToken(File $file, User $createdBy = null)
    {
        if ($file instanceof Folder || $file instanceof SmartFolder) {
            throw new \Exception('Only files can be have download tokens');
        }

        $token = $this->getToken($file, $createdBy);
        if ($token === null) {
            $token = new DownloadToken();
            $token->setFile($file)
                ->setCreatedBy($createdBy)
                ->setToken(TokenHelper::getSecureToken())
                ->setExpiresAt(Carbon::now()->addSeconds(self::TOKEN_TTL));

            $this->getEntityManager()->persist($token);
            $this->getEntityManager()->flush();
        }

        return $token;
    }

    /**
     * @param $file
     * @param $created_by
     * @return DownloadToken|null
     * @throws NotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getToken($file, $created_by)
    {
        if (is_numeric($created_by)) {
            $userId = $created_by;
            /** @var User $deletedBy */
            $created_by = $this->getEntityManager()->getReference(User::ENTITY_NAME, $userId);
        }

        if (is_numeric($file)) {
            $fileId = $file;
            $file = $this->getEntityManager()->find(File::ENTITY_NAME, $fileId);
            if (!$file) {
                throw new NotFoundException();
            }
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('dt'))
            ->from(DownloadToken::ENTITY_NAME, 'dt')
            ->andWhere('dt.file = :file')
            ->setParameter('file', $file)
            ->andWhere('dt.created_by = :created_by')
            ->setParameter('created_by', $created_by)
            ->andWhere('dt.expires_at >= :expires_at')
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
        $qbDelete->delete(DownloadToken::ENTITY_NAME, 'dt')
            ->andWhere('dt.expires_at < :expires_at')
            ->setParameter(':expires_at', Carbon::now());
        $qbDelete->getQuery()->execute();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(array('dt'))
            ->from(DownloadToken::ENTITY_NAME, 'dt')
            ->andWhere('dt.token = :token')
            ->setParameter('token', $token)
            ->andWhere('dt.expires_at >= :expires_at')
            ->setParameter(':expires_at', Carbon::now());
        $downloadToken = $qb->getQuery()->getOneOrNullResult();
        if ($downloadToken) {
            $this->getEntityManager()->remove($downloadToken);
            $this->getEntityManager()->flush();
            return true;
        } else {
            return false;
        }
    }
}
