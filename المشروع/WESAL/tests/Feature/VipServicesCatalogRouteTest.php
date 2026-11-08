<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VipServicesCatalogRouteTest extends TestCase
{
    public function test_vip_service_catalog_is_public_but_orders_remain_member_protected(): void
    {
        $catalogRoute = Route::getRoutes()->getByName('api.vip-services.list');

        $this->assertNotNull($catalogRoute, 'The public VIP service catalogue route must be registered.');
        $catalogMiddleware = $catalogRoute->gatherMiddleware();

        $this->assertNotContains('auth:sanctum', $catalogMiddleware);
        $this->assertNotContains('api_member', $catalogMiddleware);

        $ordersRoute = null;
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'api/vip-services/my-orders' && in_array('GET', $route->methods(), true)) {
                $ordersRoute = $route;
                break;
            }
        }

        $this->assertNotNull($ordersRoute, 'The authenticated VIP orders route must remain registered.');
        $ordersMiddleware = $ordersRoute->gatherMiddleware();

        $this->assertContains('auth:sanctum', $ordersMiddleware);
        $this->assertContains('api_member', $ordersMiddleware);
    }
}
