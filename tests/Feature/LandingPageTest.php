<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\LandingPageVisit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_base_url_renders_configured_html_and_records_visit(): void
    {
        $landingPage = LandingPage::query()->firstOrFail();
        $landingPage->update([
            'meta_title' => 'Landing Page Test',
            'site_name' => 'Hotel Test',
            'html_content' => '<strong>{{SITE_NAME}}</strong><form action="{{LOGIN_URL}}">{{CSRF_FIELD}}<button>Masuk</button></form>',
            'is_active' => true,
        ]);

        $before = LandingPageVisit::query()->count();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<title>Landing Page Test</title>', false)
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee('<strong>Hotel Test</strong>', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('{{LOGIN_URL}}', false)
            ->assertDontSee('{{CSRF_FIELD}}', false);

        $this->assertSame($before + 1, LandingPageVisit::query()->count());
    }

    public function test_landing_page_editor_requires_authentication(): void
    {
        $this->get(route('platform.landing-page.edit'))
            ->assertRedirect(route('login'));
    }
}
