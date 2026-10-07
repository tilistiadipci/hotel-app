<?php

namespace Tests\Unit;

use App\Http\Controllers\PlayerPublishController;
use ReflectionMethod;
use Tests\TestCase;

class PlayerPublishControllerTest extends TestCase
{
    public function test_submitted_channel_choices_are_not_overwritten_by_selected_preset(): void
    {
        $channels = [
            ['tv_channel_id' => 101, 'is_active' => true, 'sort_order' => 0],
            ['tv_channel_id' => 102, 'is_active' => false, 'sort_order' => 1],
        ];
        $method = new ReflectionMethod(PlayerPublishController::class, 'applySelectedGroups');

        $result = $method->invoke(app(PlayerPublishController::class), [
            'channel_group_id' => 999,
            'channels' => $channels,
        ]);

        $this->assertSame($channels, $result['channels']);
    }
}
