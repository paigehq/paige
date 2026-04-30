<?php

namespace App\Permission\Exceptions;

use Exception;

class PermissionDeniedException extends Exception
{
    public function __construct()
    {
        parent::__construct('You do not have permission to perform this action.');
    }
}
