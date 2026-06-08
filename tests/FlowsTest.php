<?php

use PHPUnit\Framework\TestCase;

class FlowsTest extends TestCase
{
    private function cfg(): array
    {
        return flow_cfg();
    }

    public function testDemoListMatchesConfig(): void
    {
        $this->assertSame(
            ['signup', 'login', 'account', 'password_reset', 'lists', 'privacy', 'webhooks'],
            valid_urls()
        );
    }

    // -- sign up -----------------------------------------------------------

    public function testSignupNewEmailIsRiskAssessed(): void
    {
        $decision = decide_signup([
            'name' => 'Lois Lane',
            'email' => 'lois.lane@dailyplanet.com',
            'request_token' => 'tok-1',
        ], $this->cfg());

        $this->assertSame('risk', $decision['api_endpoint']);
        $this->assertSame('$registration', $decision['castle_type']);
        $this->assertSame('$succeeded', $decision['castle_status']);
        $this->assertSame('lois.lane@dailyplanet.com', $decision['payload']['user']['email']);
        $this->assertSame('Lois Lane', $decision['payload']['user']['name']);
    }

    public function testSignupExistingEmailGoesToFilter(): void
    {
        $decision = decide_signup([
            'name' => 'Clark Kent',
            'email' => 'clark.kent@dailyplanet.com',
            'request_token' => 'tok-2',
        ], $this->cfg());

        $this->assertSame('filter', $decision['api_endpoint']);
        $this->assertSame('$failed', $decision['castle_status']);
    }

    // -- login -------------------------------------------------------------

    public function testLoginValidCredentialsCallRisk(): void
    {
        $decision = decide_login([
            'email' => 'clark.kent@dailyplanet.com',
            'password' => 'supersecret',
            'request_token' => 'tok-123',
        ], $this->cfg());

        $this->assertSame('risk', $decision['api_endpoint']);
        $this->assertSame('$login', $decision['castle_type']);
        $this->assertSame('$succeeded', $decision['castle_status']);
        $this->assertSame('00000000', $decision['payload']['user']['id']);
        $this->assertSame('clark.kent@dailyplanet.com', $decision['payload']['user']['email']);
        $this->assertArrayHasKey('registered_at', $decision['payload']['user']);
        $this->assertSame('tok-123', $decision['payload']['request_token']);
    }

    public function testLoginValidUserWrongPasswordCallsFilter(): void
    {
        $decision = decide_login([
            'email' => 'clark.kent@dailyplanet.com',
            'password' => 'wrong-password',
            'request_token' => 'tok-456',
        ], $this->cfg());

        $this->assertSame('filter', $decision['api_endpoint']);
        $this->assertSame('$failed', $decision['castle_status']);
        $this->assertSame('00000000', $decision['payload']['user']['id']);
        $this->assertArrayHasKey('registered_at', $decision['payload']['user']);
    }

    public function testLoginUnknownUserCallsFilterWithoutUserId(): void
    {
        $decision = decide_login([
            'email' => 'stranger@example.com',
            'password' => 'whatever',
            'request_token' => 'tok-789',
        ], $this->cfg());

        $this->assertSame('filter', $decision['api_endpoint']);
        $this->assertNull($decision['payload']['user']['id']);
        $this->assertArrayNotHasKey('registered_at', $decision['payload']['user']);
    }

    // -- profile update ----------------------------------------------------

    public function testProfileUpdateCallsRisk(): void
    {
        $decision = decide_profile_update([
            'name' => 'Kal-El',
            'email' => 'kal.el@dailyplanet.com',
            'request_token' => 'tok-3',
        ], $this->cfg());

        $this->assertSame('risk', $decision['api_endpoint']);
        $this->assertSame('$profile_update', $decision['castle_type']);
        $this->assertSame('Kal-El', $decision['payload']['user']['name']);
        $this->assertSame('kal.el@dailyplanet.com', $decision['payload']['user']['email']);
    }

    public function testProfileUpdateFallsBackToValidUsername(): void
    {
        $decision = decide_profile_update(['request_token' => 'tok-3'], $this->cfg());
        $this->assertSame('clark.kent@dailyplanet.com', $decision['payload']['user']['email']);
    }

    // -- logout ------------------------------------------------------------

    public function testLogoutLogsEvent(): void
    {
        $decision = decide_logout(['request_token' => 'tok-4'], $this->cfg());

        $this->assertSame('log', $decision['api_endpoint']);
        $this->assertSame('$logout', $decision['castle_type']);
    }

    // -- password reset ----------------------------------------------------

    public function testNewPasswordLogsSucceeded(): void
    {
        $decision = decide_password_reset([
            'password' => 'a-brand-new-password',
            'request_token' => 'tok-1',
        ], $this->cfg());

        $this->assertSame('log', $decision['api_endpoint']);
        $this->assertSame('$password_reset', $decision['castle_type']);
        $this->assertSame('$succeeded', $decision['castle_status']);
        $this->assertSame('clark.kent@dailyplanet.com', $decision['payload']['user']['email']);
    }

    public function testReusingCurrentPasswordLogsFailed(): void
    {
        $decision = decide_password_reset([
            'password' => 'supersecret',
            'request_token' => 'tok-2',
        ], $this->cfg());

        $this->assertSame('$failed', $decision['castle_status']);
    }

    // -- lists / privacy payloads -----------------------------------------

    public function testCreateListDefaults(): void
    {
        $this->assertSame([
            'name' => 'demo-blocklist',
            'color' => '$red',
            'primary_field' => 'user.email',
        ], build_create_list_payload([]));
    }

    public function testCreateListCustomPayload(): void
    {
        $this->assertSame([
            'name' => 'vip',
            'color' => '$green',
            'primary_field' => 'user.id',
        ], build_create_list_payload([
            'name' => 'vip',
            'color' => '$green',
            'primary_field' => 'user.id',
        ]));
    }

    public function testPrivacyPayloadDefaults(): void
    {
        $this->assertSame([
            'identifier' => 'clark.kent@dailyplanet.com',
            'identifier_type' => '$email',
        ], build_privacy_payload([], $this->cfg()));
    }

    public function testPrivacyPayloadCustom(): void
    {
        $this->assertSame([
            'identifier' => 'someone@else.com',
            'identifier_type' => '$user_id',
        ], build_privacy_payload([
            'identifier' => 'someone@else.com',
            'identifier_type' => '$user_id',
        ], $this->cfg()));
    }
}
