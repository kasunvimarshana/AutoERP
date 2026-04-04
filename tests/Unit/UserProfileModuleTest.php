<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\User\Domain\ValueObjects\UserStatus;
use Modules\User\Domain\Entities\User;

class UserProfileModuleTest extends TestCase
{
    // --------------- UserStatus VO ---------------

    public function test_user_status_active_value(): void
    {
        $this->assertSame('active', UserStatus::ACTIVE);
    }

    public function test_user_status_inactive_value(): void
    {
        $this->assertSame('inactive', UserStatus::INACTIVE);
    }

    public function test_user_status_banned_value(): void
    {
        $this->assertSame('banned', UserStatus::BANNED);
    }

    public function test_user_status_from_active(): void
    {
        $vo = UserStatus::from(UserStatus::ACTIVE);
        $this->assertSame('active', (string) $vo);
    }

    public function test_user_status_from_inactive(): void
    {
        $vo = UserStatus::from(UserStatus::INACTIVE);
        $this->assertSame('inactive', (string) $vo);
    }

    public function test_user_status_from_banned(): void
    {
        $vo = UserStatus::from(UserStatus::BANNED);
        $this->assertSame('banned', (string) $vo);
    }

    public function test_user_status_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserStatus::from('superuser');
    }

    public function test_user_status_valid_list_contains_expected(): void
    {
        $valid = UserStatus::valid();
        $this->assertContains('active', $valid);
        $this->assertContains('inactive', $valid);
        $this->assertContains('banned', $valid);
    }

    // --------------- User entity ---------------

    private function makeUser(): User
    {
        return new User(
            id: 1,
            tenantId: 10,
            name: 'Alice',
            email: 'alice@example.com',
            status: UserStatus::ACTIVE,
        );
    }

    public function test_user_stores_id(): void
    {
        $user = $this->makeUser();
        $this->assertSame(1, $user->id);
    }

    public function test_user_stores_name(): void
    {
        $user = $this->makeUser();
        $this->assertSame('Alice', $user->name);
    }

    public function test_user_stores_email(): void
    {
        $user = $this->makeUser();
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_user_stores_status(): void
    {
        $user = $this->makeUser();
        $this->assertSame(UserStatus::ACTIVE, $user->status);
    }

    public function test_user_stores_tenant_id(): void
    {
        $user = $this->makeUser();
        $this->assertSame(10, $user->tenantId);
    }

    public function test_user_null_id_is_allowed(): void
    {
        $user = new User(
            id: null,
            tenantId: 5,
            name: 'Bob',
            email: 'bob@example.com',
            status: UserStatus::INACTIVE,
        );
        $this->assertNull($user->id);
    }

    public function test_user_optional_fields_default_to_null(): void
    {
        $user = $this->makeUser();
        $this->assertNull($user->avatar);
        $this->assertNull($user->preferences);
        $this->assertNull($user->emailVerifiedAt);
    }

    public function test_user_avatar_can_be_set(): void
    {
        $user = new User(
            id: 2,
            tenantId: 1,
            name: 'Carol',
            email: 'carol@example.com',
            status: UserStatus::ACTIVE,
            avatar: 'https://cdn.example.com/avatar.png',
        );
        $this->assertSame('https://cdn.example.com/avatar.png', $user->avatar);
    }

    public function test_user_preferences_can_be_set(): void
    {
        $prefs = ['theme' => 'dark', 'lang' => 'en'];
        $user = new User(
            id: 3,
            tenantId: 1,
            name: 'Dave',
            email: 'dave@example.com',
            status: UserStatus::ACTIVE,
            preferences: $prefs,
        );
        $this->assertSame($prefs, $user->preferences);
    }
}
