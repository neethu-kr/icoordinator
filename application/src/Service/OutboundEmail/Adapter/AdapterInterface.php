<?php

namespace iCoordinator\Service\OutboundEmail\Adapter;

interface AdapterInterface
{
    public function __construct(array $config);

    public function addTo($email, $name = null);

    public function setTo($email, $name = null);

    public function setFrom($email, $name = null);

    public function setSubject($subject);

    public function setLang($lang);

    public function send($templateName, array $vars = array());
}
