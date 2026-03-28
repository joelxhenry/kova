<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_landing_page_renders(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Kova');
        $response->assertSee('without the hassle');
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
