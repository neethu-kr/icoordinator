<?php

namespace iCoordinator\Service\Helper;

class TokenHelper
{
    public static function getSecureToken($length = 16)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}
