<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AddressCityRelationshipTest extends TestCase
{
    public function test_address_city_relation_uses_city_id_without_soft_delete_scope(): void
    {
        $addressPath = __DIR__ . '/../../app/Models/Address.php';
        $cityPath = __DIR__ . '/../../app/Models/City.php';

        $addressSource = file_get_contents($addressPath);
        $citySource = file_get_contents($cityPath);

        self::assertIsString($addressSource);
        self::assertIsString($citySource);

        $methodStart = strpos($addressSource, 'public function city()');
        self::assertNotFalse($methodStart);

        $methodEnd = strpos($addressSource, '}', $methodStart);
        self::assertNotFalse($methodEnd);

        $cityMethod = substr($addressSource, $methodStart, $methodEnd - $methodStart + 1);

        self::assertStringContainsString(
            "belongsTo(City::class, 'city_id')",
            $cityMethod
        );
        self::assertStringNotContainsString('withTrashed()', $cityMethod);

        // City does not use Laravel SoftDeletes, so the relation must not
        // attempt to call the SoftDeletes-only withTrashed() scope.
        self::assertStringNotContainsString('use SoftDeletes;', $citySource);
    }
}
