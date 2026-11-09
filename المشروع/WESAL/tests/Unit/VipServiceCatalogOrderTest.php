<?php

namespace Tests\Unit;

use App\Services\VipServiceCatalog;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/Services/VipServiceCatalog.php';

class VipServiceCatalogOrderTest extends TestCase
{
    public function test_sort_order_is_primary_and_equal_values_keep_the_submitted_order(): void
    {
        $services = [
            ['id' => 1, 'service_type' => 'last', 'sort_order' => 30],
            ['id' => 99, 'service_type' => 'first', 'sort_order' => 10],
            ['id' => 2, 'service_type' => 'second', 'sort_order' => 10],
            ['id' => 4, 'service_type' => 'middle', 'sort_order' => 20],
        ];

        $ordered = VipServiceCatalog::sortByOrder($services);

        $this->assertSame(
            ['first', 'second', 'middle', 'last'],
            array_column($ordered, 'service_type')
        );
    }

    public function test_normalise_order_assigns_unique_increasing_values_in_tens(): void
    {
        $services = [
            ['service_type' => 'third', 'sort_order' => 20],
            ['service_type' => 'first', 'sort_order' => 10],
            ['service_type' => 'second', 'sort_order' => 10],
        ];

        $normalised = VipServiceCatalog::normaliseOrder($services);

        $this->assertSame(
            ['first', 'second', 'third'],
            array_column($normalised, 'service_type')
        );
        $this->assertSame([10, 20, 30], array_column($normalised, 'sort_order'));
    }

    public function test_equal_values_do_not_fall_back_to_service_id_order(): void
    {
        $services = [
            ['id' => 200, 'service_type' => 'chosen-first', 'sort_order' => 10],
            ['id' => 1, 'service_type' => 'chosen-second', 'sort_order' => 10],
        ];

        $ordered = VipServiceCatalog::sortByOrder($services);

        $this->assertSame(
            ['chosen-first', 'chosen-second'],
            array_column($ordered, 'service_type')
        );
    }
}
