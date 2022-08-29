<?php

namespace iCoordinator\Service\Exception;

class ChargifyException extends \Exception
{
    /**
     * @var array
     */
    private $errors = [];

    public function __construct(array $errors)
    {
        $this->errors = $errors;

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->message;
        }

        $this->message = implode('; ', $messages);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
