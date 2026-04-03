<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Application\Services\ChangePasswordService;
use Modules\User\Application\Services\UpdateProfileService;
use Modules\User\Application\Services\UploadAvatarService;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserAvatarUpdated;
use Modules\User\Domain\Events\UserPasswordChanged;
use Modules\User\Domain\Events\UserProfileUpdated;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Modules\User\Infrastructure\Http\Requests\ChangePasswordRequest;
use Modules\User\Infrastructure\Http\Requests\UpdateProfileRequest;
use Modules\User\Infrastructure\Http\Requests\UploadAvatarRequest;
use PHPUnit\Framework\TestCase;

class UserProfileModuleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Domain: Events
    // -------------------------------------------------------------------------

    public function test_user_profile_updated_event_exists(): void
    {
        $this->assertTrue(class_exists(UserProfileUpdated::class));
    }

    public function test_user_password_changed_event_exists(): void
    {
        $this->assertTrue(class_exists(UserPasswordChanged::class));
    }

    public function test_user_avatar_updated_event_exists(): void
    {
        $this->assertTrue(class_exists(UserAvatarUpdated::class));
    }

    // -------------------------------------------------------------------------
    // Application: Contracts
    // -------------------------------------------------------------------------

    public function test_update_profile_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UpdateProfileServiceInterface::class));
    }

    public function test_change_password_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ChangePasswordServiceInterface::class));
    }

    public function test_upload_avatar_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(UploadAvatarServiceInterface::class));
    }

    // -------------------------------------------------------------------------
    // Application: Services
    // -------------------------------------------------------------------------

    public function test_update_profile_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UpdateProfileService::class));
    }

    public function test_change_password_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ChangePasswordService::class));
    }

    public function test_upload_avatar_service_class_exists(): void
    {
        $this->assertTrue(class_exists(UploadAvatarService::class));
    }

    public function test_update_profile_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(UpdateProfileService::class, UpdateProfileServiceInterface::class),
            'UpdateProfileService must implement UpdateProfileServiceInterface.'
        );
    }

    public function test_change_password_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(ChangePasswordService::class, ChangePasswordServiceInterface::class),
            'ChangePasswordService must implement ChangePasswordServiceInterface.'
        );
    }

    public function test_upload_avatar_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(UploadAvatarService::class, UploadAvatarServiceInterface::class),
            'UploadAvatarService must implement UploadAvatarServiceInterface.'
        );
    }

    // -------------------------------------------------------------------------
    // Application: DTOs
    // -------------------------------------------------------------------------

    public function test_update_profile_data_dto_exists(): void
    {
        $this->assertTrue(class_exists(UpdateProfileData::class));
    }

    public function test_change_password_data_dto_exists(): void
    {
        $this->assertTrue(class_exists(ChangePasswordData::class));
    }

    public function test_update_profile_data_can_be_instantiated(): void
    {
        $dto = UpdateProfileData::fromArray([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'phone'      => null,
            'address'    => null,
        ]);

        $this->assertSame('John', $dto->first_name);
        $this->assertSame('Doe', $dto->last_name);
        $this->assertNull($dto->phone);
    }

    public function test_change_password_data_can_be_instantiated(): void
    {
        $dto = ChangePasswordData::fromArray([
            'current_password'      => 'oldSecret1',
            'password'              => 'newSecret1',
            'password_confirmation' => 'newSecret1',
        ]);

        $this->assertSame('oldSecret1', $dto->current_password);
        $this->assertSame('newSecret1', $dto->password);
    }

    // -------------------------------------------------------------------------
    // Domain: Entity - avatar support
    // -------------------------------------------------------------------------

    public function test_user_entity_has_get_avatar_method(): void
    {
        $this->assertTrue(method_exists(User::class, 'getAvatar'));
    }

    public function test_user_entity_has_change_avatar_method(): void
    {
        $this->assertTrue(method_exists(User::class, 'changeAvatar'));
    }

    public function test_user_entity_avatar_defaults_to_null(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe'
        );

        $this->assertNull($user->getAvatar());
    }

    public function test_user_entity_change_avatar_updates_avatar(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe'
        );

        $user->changeAvatar('avatars/1/photo.jpg');
        $this->assertSame('avatars/1/photo.jpg', $user->getAvatar());
    }

    public function test_user_entity_change_avatar_accepts_null(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe',
            avatar: 'avatars/1/old.jpg'
        );

        $user->changeAvatar(null);
        $this->assertNull($user->getAvatar());
    }

    // -------------------------------------------------------------------------
    // Domain: Repository Interface
    // -------------------------------------------------------------------------

    public function test_user_repository_interface_has_change_password_method(): void
    {
        $this->assertTrue(method_exists(UserRepositoryInterface::class, 'changePassword'));
    }

    public function test_user_repository_interface_has_update_avatar_method(): void
    {
        $this->assertTrue(method_exists(UserRepositoryInterface::class, 'updateAvatar'));
    }

    public function test_user_repository_interface_has_verify_password_method(): void
    {
        $this->assertTrue(method_exists(UserRepositoryInterface::class, 'verifyPassword'));
    }

    // -------------------------------------------------------------------------
    // Infrastructure: HTTP Requests
    // -------------------------------------------------------------------------

    public function test_update_profile_request_exists(): void
    {
        $this->assertTrue(class_exists(UpdateProfileRequest::class));
    }

    public function test_change_password_request_exists(): void
    {
        $this->assertTrue(class_exists(ChangePasswordRequest::class));
    }

    public function test_upload_avatar_request_exists(): void
    {
        $this->assertTrue(class_exists(UploadAvatarRequest::class));
    }

    // -------------------------------------------------------------------------
    // Infrastructure: Controller
    // -------------------------------------------------------------------------

    public function test_profile_controller_exists(): void
    {
        $this->assertTrue(class_exists(ProfileController::class));
    }

    public function test_profile_controller_has_show_method(): void
    {
        $this->assertTrue(method_exists(ProfileController::class, 'show'));
    }

    public function test_profile_controller_has_update_method(): void
    {
        $this->assertTrue(method_exists(ProfileController::class, 'update'));
    }

    public function test_profile_controller_has_change_password_method(): void
    {
        $this->assertTrue(method_exists(ProfileController::class, 'changePassword'));
    }

    public function test_profile_controller_has_update_preferences_method(): void
    {
        $this->assertTrue(method_exists(ProfileController::class, 'updatePreferences'));
    }

    public function test_profile_controller_has_upload_avatar_method(): void
    {
        $this->assertTrue(method_exists(ProfileController::class, 'uploadAvatar'));
    }

    // -------------------------------------------------------------------------
    // Domain: Events - properties
    // -------------------------------------------------------------------------

    public function test_user_profile_updated_event_holds_user(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe'
        );

        $event = new UserProfileUpdated($user);
        $this->assertSame($user, $event->user);
    }

    public function test_user_password_changed_event_holds_user(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe'
        );

        $event = new UserPasswordChanged($user);
        $this->assertSame($user, $event->user);
    }

    public function test_user_avatar_updated_event_holds_user(): void
    {
        $user = new User(
            tenantId: 1,
            email: new \Modules\Core\Domain\ValueObjects\Email('test@example.com'),
            firstName: 'Jane',
            lastName: 'Doe',
            avatar: 'avatars/1/photo.jpg'
        );

        $event = new UserAvatarUpdated($user);
        $this->assertSame($user, $event->user);
    }
}
