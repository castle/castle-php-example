<?php

use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    protected function setUp(): void
    {
        Castle::setApiKey('test_secret');
        webhooks_clear();
    }

    protected function tearDown(): void
    {
        webhooks_clear();
    }

    private function sign(string $body): string
    {
        return base64_encode(hash_hmac('sha256', $body, 'test_secret', true));
    }

    public function testVerifiedWebhookIsStoredAndListed(): void
    {
        $body = json_encode(['type' => 'review.opened', 'data' => ['id' => 'rev_1']]);

        $status = process_incoming_webhook($body, $this->sign($body));

        $this->assertSame(204, $status);
        $stored = webhooks_all();
        $this->assertCount(1, $stored);
        $this->assertSame('review.opened', $stored[0]['body']['type']);
    }

    public function testUnverifiedWebhookIsRejected(): void
    {
        $body = json_encode(['type' => 'review.opened']);

        $status = process_incoming_webhook($body, 'bad-signature');

        $this->assertSame(404, $status);
        $this->assertCount(0, webhooks_all());
    }

    public function testWebhookStoreCaps(): void
    {
        for ($i = 0; $i < 55; $i++) {
            webhooks_add(['type' => 'event', 'i' => $i]);
        }
        $this->assertCount(50, webhooks_all());
    }
}
