<?php

namespace App\Wiki\Exceptions;

use RuntimeException;

class CircularReferenceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cannot move a page to one of its own descendants.');
    }
}
