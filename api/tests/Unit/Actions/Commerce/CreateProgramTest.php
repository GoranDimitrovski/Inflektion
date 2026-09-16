<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Commerce;

use App\Actions\Commerce\CreateProgram;
use App\Actions\Commerce\CreateProgramData;
use App\Models\Program;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateProgramTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itReliesOnTheDbUniqueConstraintNotAPreCheckForSlugUniqueness(): void
    {
        $action = app(CreateProgram::class);
        $data = new CreateProgramData(name: 'Acme', slug: 'acme');

        $action->handle($data);

        // Bypasses ProgramRequest's Rule::unique() pre-check entirely.
        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $action->handle($data);
        } finally {
            $this->assertSame(1, Program::query()->where('slug', 'acme')->count());
        }
    }
}
