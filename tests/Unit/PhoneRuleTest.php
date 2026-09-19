<?php

namespace Tests\Unit;

use App\Rules\PhoneRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneRuleTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_it_accepts_valid_palestinian_phone_numbers(string $phone): void
    {
        $failed = false;
        (new PhoneRule)->validate('phone', $phone, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public static function validPhones(): array
    {
        return [
            'prefix +970, starts 59' => ['+970591234567'],
            'prefix +970, starts 56' => ['+970561234567'],
            'prefix +972, starts 59' => ['+972591234567'],
            'prefix +972, starts 56' => ['+972561234567'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_it_rejects_invalid_phone_numbers(mixed $phone): void
    {
        $failed = false;
        (new PhoneRule)->validate('phone', $phone, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public static function invalidPhones(): array
    {
        return [
            'wrong country code' => ['+1591234567'],
            'wrong prefix' => ['+970511234567'],
            'too short' => ['+97059123456'],
            'missing plus' => ['970591234567'],
            'not a string' => [123],
        ];
    }

    public function test_normalize_converts_972_to_970(): void
    {
        $this->assertSame('+970591234567', PhoneRule::normalize('+972591234567'));
    }

    public function test_normalize_leaves_970_unchanged(): void
    {
        $this->assertSame('+970591234567', PhoneRule::normalize('+970591234567'));
    }
}
