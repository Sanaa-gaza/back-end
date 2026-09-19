<?php

namespace Tests\Unit;

use App\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordRuleTest extends TestCase
{
    public function test_it_accepts_a_strong_password(): void
    {
        $failed = false;
        (new PasswordRule)->validate('password', 'Str0ng!Pass', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[DataProvider('weakPasswords')]
    public function test_it_rejects_weak_passwords(mixed $password): void
    {
        $failed = false;
        (new PasswordRule)->validate('password', $password, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Sh0rt!'],
            'no uppercase' => ['weak0pass!'],
            'no digit' => ['WeakPass!'],
            'no special char' => ['WeakPass0'],
            'not a string' => [12345678],
        ];
    }
}
