<?php

use PHPUnit\Framework\TestCase;

class PagesTest extends TestCase
{
    public function testHomeRenders(): void
    {
        $html = render_page('home', default_params());
        $this->assertStringContainsStringIgnoringCase('<html', $html);
        $this->assertStringContainsString('Castle workflows demo', $html);
    }

    /**
     * @dataProvider demoProvider
     */
    public function testEveryDemoPageRenders(string $name): void
    {
        if ($name === 'webhooks') {
            $params = default_params() + demos()['webhooks'];
            $params['friendly_name'] = demos()['webhooks']['friendly_name'];
            $params['webhook_endpoint'] = 'http://localhost/webhooks/castle';
            $params['webhooks_received'] = [];
        } else {
            $params = default_params() + demos()[$name];
            $params['friendly_name'] = demos()[$name]['friendly_name'];
        }

        $html = render_page('demos/' . $name, $params);
        $this->assertStringContainsStringIgnoringCase('<html', $html);
    }

    public function demoProvider(): array
    {
        return array_map(static function ($name) {
            return [$name];
        }, valid_urls());
    }

    public function testErrorPageRenders(): void
    {
        $html = render_page('error', default_params());
        $this->assertStringContainsStringIgnoringCase('<html', $html);
        $this->assertStringContainsString('Page not found', $html);
    }
}
