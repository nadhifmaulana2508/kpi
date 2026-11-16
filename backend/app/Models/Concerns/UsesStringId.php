<?php

namespace App\Models\Concerns;

trait UsesStringId
{
    public function initializeUsesStringId(): void
    {
        $this->setIncrementing(false);
        $this->setKeyType('string');
    }
}
