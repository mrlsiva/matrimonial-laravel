<?php

namespace App\Exceptions;

use RuntimeException;

/** Validation failure for a single bulk-import row; carries the messages to show the admin. */
class ImportRowFailed extends RuntimeException
{
    public function __construct(public array $messages)
    {
        parent::__construct(implode(' ', $messages));
    }
}
