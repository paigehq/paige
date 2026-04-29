<?php

namespace App\Wiki\Exceptions;

use RuntimeException;

class SlugExhaustedException extends RuntimeException
{
    public function __construct(string $baseSlug)
    {
        parent::__construct(
            "Slug exhausted: no available slug for base '$baseSlug' (tried up to - 10)."
        );
    }
}
