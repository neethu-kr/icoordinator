<?php

namespace iCoordinator\File;

use iCoordinator\File\Storage\StorageInterface;
use phpseclib\Crypt\AES;

class Encryptor
{
    //deprecated (works only for old files)
    const KEY = 'y)${3!gQ35/dS:V.Ubv^")L<;~yJ($';

    //deprecated (works only for old files)
    const CHUNK_SIZE = 1048576; // 1 MB

    const ENCRYPTION_ALGORITHM = MCRYPT_RIJNDAEL_128;
    const ENCRYPTION_MODE = MCRYPT_MODE_CBC;

    const ENCRYPT_STREAM_FILTER = 'mcrypt.rijndael-128';
    const DECRYPT_STREAM_FILTER = 'mdecrypt.rijndael-128';

    protected $key;
    protected $iv;

    /**
     * @var AES
     */
    private $encryptor = null;

    public static function generateIv()
    {
        $ivSize = mcrypt_get_iv_size(self::ENCRYPTION_ALGORITHM, self::ENCRYPTION_MODE);
        return base64_encode(mcrypt_create_iv($ivSize, MCRYPT_RAND));
    }

    public function setKey($key)
    {
        $key = base64_decode($key);
        $keySize = strlen($key);
        if (!in_array($keySize, array(16, 24, 32))) {
            throw new \InvalidArgumentException('Incorrect key size, should be: 16, 24 or 32');
        }
        $this->key = $key;
    }

    public function setIv($iv)
    {
        $iv = base64_decode($iv);
        $ivSize = mcrypt_get_iv_size(self::ENCRYPTION_ALGORITHM, self::ENCRYPTION_MODE);
        if ($ivSize != strlen($iv)) {
            throw new \InvalidArgumentException('Incorrect IV size, should be: ' . $ivSize);
        }
        $this->iv = $iv;
    }

    public function setStreamFilterForStorage(StorageInterface $storage, $filerName)
    {
        if (empty($this->iv)) {
            throw new \RuntimeException("Encryptor IV is not defined. Use setIv() method to set IV.");
        }
        $storage->setStreamFilter($filerName, array(
            'iv' => $this->iv,
            'key' => $this->key
        ));
    }

    /**
     * @param $filePath
     * @deprecated use stream filters instead
     */
    public function encrypt($filePath)
    {
        $encryptor = $this->getEncryptor();
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'ecrypting_');

        $originHandle = fopen($filePath, "rb");
        $destHandle = fopen($tmpFilePath, "ab");

        while (!feof($originHandle)) {
            $contents = fread($originHandle, self::CHUNK_SIZE);
            $contents = $encryptor->encrypt($contents);
            fwrite($destHandle, $contents);
        }
        fclose($originHandle);
        fclose($destHandle);

        unlink($filePath);
        rename($tmpFilePath, $filePath);
    }

    /**
     * @param $filePath
     * @deprecated use stream filters instead
     */
    public function decrypt($filePath)
    {
        $encryptor = $this->getEncryptor();

        $tmpFilePath = tempnam(sys_get_temp_dir(), 'decrypting_');

        $originHandle = fopen($filePath, "rb");
        $destHandle = fopen($tmpFilePath, "ab");

        while (!feof($originHandle)) {
            $contents = fread($originHandle, $this->getEncryptedChunkSize());
            $contents = $encryptor->decrypt($contents);
            fwrite($destHandle, $contents);
        }

        fclose($originHandle);
        fclose($destHandle);

        unlink($filePath);
        rename($tmpFilePath, $filePath);
    }

    /**
     * @return int
     */
    private function getEncryptedChunkSize()
    {
        $encryptor = $this->getEncryptor();
        $encryptedChunkSize = (self::CHUNK_SIZE / $encryptor->block_size + 1) * $encryptor->block_size;

        return $encryptedChunkSize;
    }

    /**
     * @return AES
     */
    private function getEncryptor()
    {
        if ($this->encryptor === null) {
            $this->encryptor = new AES();
            $this->encryptor->setKey(self::KEY);
        }

        return $this->encryptor;
    }
}
