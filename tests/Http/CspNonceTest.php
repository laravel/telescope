<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Support\Str;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;

class CspNonceTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class]);
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        Telescope::$nonceAttribute = '';

        parent::tearDown();
    }

    public function test_csp_nonce_is_not_rendered_in_style_and_script_tags_if_not_set()
    {
        $response = $this->get('/telescope');

        $response->assertOk()
            ->assertSeeHtml('<style>')
            ->assertSeeHtml('<script type="module">');
    }

    public function test_csp_nonce_is_rendered_in_style_and_script_tags_if_set()
    {
        $nonce = Str::random(40);

        Telescope::cspNonce($nonce);

        $response = $this->get('/telescope');

        $response->assertOk()
            ->assertSeeHtml("<style nonce=\"{$nonce}\">")
            ->assertSeeHtml("<script type=\"module\" nonce=\"{$nonce}\">");
    }

    public function test_csp_nonce_value_is_escaped_when_rendered()
    {
        Telescope::cspNonce('"><script>alert(1)</script>');

        $response = $this->get('/telescope');

        $response->assertOk()
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSeeHtml('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
