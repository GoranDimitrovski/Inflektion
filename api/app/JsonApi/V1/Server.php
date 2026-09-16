<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use App\JsonApi\V1\Programs\ProgramSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    protected string $baseUri = '/api/v1';

    public function serving(): void
    {
        // no-op
    }

    /**
     * @return array<int, class-string>
     */
    protected function allSchemas(): array
    {
        return [
            ProgramSchema::class,
        ];
    }
}
