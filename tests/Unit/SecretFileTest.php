<?php

namespace Tests\Unit;

use App\Support\SecretFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SecretFileTest extends TestCase
{
    public function test_file_overrides_environment_and_strips_only_line_endings(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'santa-secret-');
        file_put_contents($path, "file-value \n");
        try {
            $this->assertSame('file-value ', SecretFile::read($path, 'environment-value'));
            $this->assertSame('environment-value', SecretFile::read(null, 'environment-value'));
            unlink($path);
            $this->expectException(RuntimeException::class);
            SecretFile::read($path, 'environment-value');
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
