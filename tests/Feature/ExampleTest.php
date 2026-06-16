<?php

namespace Tests\Feature;

use App\Models\Setting;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Pre-configure website name and version in settings
        Setting::setValue('website_name', 'Chetak Pay');
        Setting::setValue('app_version', '2.5.4');
        Setting::setValue('app_update_url', 'https://play.google.com/store/apps/details?id=com.testing.app');

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Chetak Pay');
        $response->assertSee('v2.5.4');
        $response->assertSee('https://play.google.com/store/apps/details?id=com.testing.app');
    }
}
