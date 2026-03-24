app/Modules/

├── Core/

│   ├── Application/

│   │   ├── Contracts/

│   │   │   ├── FileStorageServiceInterface.php

│   │   │   ├── ReadServiceInterface.php

│   │   │   ├── ServiceInterface.php

│   │   │   └── WriteServiceInterface.php

│   │   ├── DTOs/

│   │   │   ├── BaseDto.php

│   │   │   └── BaseDto.php.back

│   │   ├── Services/

│   │   │   └── BaseService.php

│   │   └── UseCases/

│   ├── Domain/

│   │   ├── Contracts/

│   │   │   └── Repositories/

│   │   │       └── RepositoryInterface.php

│   │   ├── Events/

│   │   │   └── BaseEvent.php

│   │   ├── Exceptions/

│   │   │   ├── DomainException.php

│   │   │   └── NotFoundException.php

│   │   └── ValueObjects/

│   │       ├── Address.php

│   │       ├── ApiKeys.php

│   │       ├── CacheConfig.php

│   │       ├── Code.php

│   │       ├── DatabaseConfig.php

│   │       ├── Email.php

│   │       ├── FeatureFlags.php

│   │       ├── MailConfig.php

│   │       ├── Metadata.php

│   │       ├── Money.php

│   │       ├── Name.php

│   │       ├── PhoneNumber.php

│   │       ├── QueueConfig.php

│   │       ├── Sku.php

│   │       ├── UserPreferences.php

│   │       ├── ValidatedEmail.php

│   │       └── ValueObject.php

│   ├── Infrastructure/

│   │   ├── Concerns/

│   │   │   └── HasTranslations.php

│   │   ├── Http/

│   │   │   ├── Controllers/

│   │   │   │   └── BaseController.php

│   │   │   ├── Middleware/

│   │   │   └── Resources/

│   │   │       └── BaseResource.php

│   │   ├── Persistence/

│   │   │   ├── Eloquent/

│   │   │   │   ├── Models/

│   │   │   │   │   └── BaseModel.php

│   │   │   │   └── Traits/

│   │   │   │       ├── HasTenant.php

│   │   │   │       ├── HasTranslations.php

│   │   │   │       └── HasUuid.php

│   │   │   └── Repositories/

│   │   │       ├── ApiRepository.php

│   │   │       ├── BaseRepository.php

│   │   │       ├── CollectionRepository.php

│   │   │       └── EloquentRepository.php

│   │   ├── Providers/

│   │   │   └── CoreServiceProvider.php

│   │   └── Services/

│   │       └── FileStorageService.php

│   ├── Shared/

│   │   ├── Exceptions/

│   │   │   └── BaseException.php

│   │   └── Helpers/

│   │       └── helpers.php

│   ├── config/

│   │   └── core.php

│   └── database/

│       └── migrations/

│

├── OrganizationUnit/

│   ├── Application/

│   │   ├── Contracts/

│   │   │   ├── CreateOrganizationUnitServiceInterface.php

│   │   │   ├── DeleteOrganizationUnitAttachmentServiceInterface.php

│   │   │   ├── DeleteOrganizationUnitServiceInterface.php

│   │   │   ├── MoveOrganizationUnitServiceInterface.php

│   │   │   ├── UpdateOrganizationUnitServiceInterface.php

│   │   │   └── UploadOrganizationUnitAttachmentServiceInterface.php

│   │   ├── DTOs/

│   │   │   ├── MoveOrganizationUnitData.php

│   │   │   └── OrganizationUnitData.php

│   │   ├── Services/

│   │   │   ├── CreateOrganizationUnitService.php

│   │   │   ├── DeleteOrganizationUnitAttachmentService.php

│   │   │   ├── DeleteOrganizationUnitService.php

│   │   │   ├── MoveOrganizationUnitService.php

│   │   │   ├── UpdateOrganizationUnitService.php

│   │   │   └── UploadOrganizationUnitAttachmentService.php

│   │   └── UseCases/

│   ├── Domain/

│   │   ├── Entities/

│   │   │   ├── OrganizationUnit.php

│   │   │   └── OrganizationUnitAttachment.php

│   │   ├── Events/

│   │   │   ├── OrganizationUnitCreated.php

│   │   │   ├── OrganizationUnitDeleted.php

│   │   │   ├── OrganizationUnitMoved.php

│   │   │   └── OrganizationUnitUpdated.php

│   │   ├── Exceptions/

│   │   │   ├── AttachmentNotFoundException.php

│   │   │   └── OrganizationUnitNotFoundException.php

│   │   ├── RepositoryInterfaces/

│   │   │   ├── OrganizationUnitAttachmentRepositoryInterface.php

│   │   │   └── OrganizationUnitRepositoryInterface.php

│   │   └── ValueObjects/

│   ├── Infrastructure/

│   │   ├── Http/

│   │   │   ├── Controllers/

│   │   │   │   ├── OrganizationUnitAttachmentController.php

│   │   │   │   └── OrganizationUnitController.php

│   │   │   ├── Requests/

│   │   │   │   ├── MoveOrganizationUnitRequest.php

│   │   │   │   └── UploadOrganizationUnitAttachmentRequest.php

│   │   │   └── Resources/

│   │   │       ├── OrganizationUnitAttachmentResource.php

│   │   │       ├── OrganizationUnitCollection.php

│   │   │       ├── OrganizationUnitResource.php

│   │   │       └── OrganizationUnitTreeResource.php

│   │   ├── Persistence/

│   │   │   └── Eloquent/

│   │   │       ├── Models/

│   │   │       │   ├── OrganizationUnitAttachmentModel.php

│   │   │       │   └── OrganizationUnitModel.php

│   │   │       └── Repositories/

│   │   │           ├── EloquentOrganizationUnitAttachmentRepository.php

│   │   │           └── EloquentOrganizationUnitRepository.php

│   │   └── Providers/

│   │       └── OrganizationUnitServiceProvider.php

│   ├── config/

│   ├── database/

│   │   └── migrations/

│   │       ├── 2025\_01\_01\_000001\_create\_organization\_units\_table.php

│   │       └── 2025\_01\_01\_000002\_create\_organization\_unit\_attachments\_table.php

│   └── routes/

│       └── api.php

│

├── Tenant/

│   ├── Application/

│   │   ├── Contracts/

│   │   │   ├── CreateTenantServiceInterface.php

│   │   │   ├── DeleteTenantAttachmentServiceInterface.php

│   │   │   ├── DeleteTenantServiceInterface.php

│   │   │   ├── TenantConfigClientInterface.php

│   │   │   ├── TenantConfigManagerInterface.php

│   │   │   ├── UpdateTenantConfigServiceInterface.php

│   │   │   ├── UpdateTenantServiceInterface.php

│   │   │   └── UploadTenantAttachmentServiceInterface.php

│   │   ├── DTOs/

│   │   │   ├── TenantAttachmentData.php

│   │   │   ├── TenantConfigData.php

│   │   │   └── TenantData.php

│   │   ├── Services/

│   │   │   ├── CreateTenantService.php

│   │   │   ├── DeleteTenantAttachmentService.php

│   │   │   ├── DeleteTenantService.php

│   │   │   ├── TenantConfigManager.php

│   │   │   ├── UpdateTenantConfigService.php

│   │   │   ├── UpdateTenantService.php

│   │   │   └── UploadTenantAttachmentService.php

│   │   └── UseCases/

│   │       ├── CreateTenant.php

│   │       ├── DeleteTenant.php

│   │       ├── GetTenant.php

│   │       ├── ListTenants.php

│   │       ├── UpdateTenant.php

│   │       └── UpdateTenantConfig.php

│   ├── Domain/

│   │   ├── Contracts/

│   │   │   └── TenantConfigInterface.php

│   │   ├── Entities/

│   │   │   ├── Tenant.php

│   │   │   └── TenantAttachment.php

│   │   ├── Events/

│   │   │   ├── TenantConfigChanged.php

│   │   │   ├── TenantCreated.php

│   │   │   ├── TenantDeleted.php

│   │   │   └── TenantUpdated.php

│   │   ├── Exceptions/

│   │   │   ├── AttachmentNotFoundException.php

│   │   │   └── TenantNotFoundException.php

│   │   └── RepositoryInterfaces/

│   │       ├── TenantAttachmentRepositoryInterface.php

│   │       └── TenantRepositoryInterface.php

│   ├── Infrastructure/

│   │   ├── Http/

│   │   │   ├── Controllers/

│   │   │   ├── Middleware/

│   │   │   │   └── ResolveTenant.php

│   │   │   ├── Requests/

│   │   │   └── Resources/

│   │   ├── Persistence/

│   │   │   └── Eloquent/

│   │   │       ├── Models/

│   │   │       │   ├── TenantAttachmentModel.php

│   │   │       │   └── TenantModel.php

│   │   │       └── Repositories/

│   │   │           ├── EloquentTenantAttachmentRepository.php

│   │   │           └── EloquentTenantRepository.php

│   │   ├── Providers/

│   │   │   ├── TenantConfigServiceProvider.php

│   │   │   └── TenantServiceProvider.php

│   │   └── Services/

│   │       ├── TenantConfig.php

│   │       └── TenantConfigClient.php

│   ├── config/

│   │   └── tenant.php

│   ├── database/

│   │   └── migrations/

│   │       ├── 2025\_01\_01\_000001\_create\_tenants\_table.php

│   │       └── 2025\_01\_01\_000002\_create\_tenant\_attachments\_table.php

│   └── routes/

│       └── api.php

│

└── User/

&#x20;   ├── Application/

&#x20;   │   ├── Contracts/

&#x20;   │   │   ├── AssignRoleServiceInterface.php

&#x20;   │   │   ├── CreateUserServiceInterface.php

&#x20;   │   │   ├── DeleteUserAttachmentServiceInterface.php

&#x20;   │   │   ├── DeleteUserServiceInterface.php

&#x20;   │   │   ├── UpdatePreferencesServiceInterface.php

&#x20;   │   │   ├── UpdateUserServiceInterface.php

&#x20;   │   │   └── UploadUserAttachmentServiceInterface.php

&#x20;   │   ├── DTOs/

&#x20;   │   │   ├── UserData.php

&#x20;   │   │   └── UserPreferencesData.php

&#x20;   │   ├── Services/

&#x20;   │   │   ├── AssignRoleService.php

&#x20;   │   │   ├── CreateUserService.php

&#x20;   │   │   ├── DeleteUserAttachmentService.php

&#x20;   │   │   ├── DeleteUserService.php

&#x20;   │   │   ├── UpdatePreferencesService.php

&#x20;   │   │   ├── UpdateUserService.php

&#x20;   │   │   └── UploadUserAttachmentService.php

&#x20;   │   └── UseCases/

&#x20;   ├── Domain/

&#x20;   │   ├── Entities/

&#x20;   │   │   ├── Permission.php

&#x20;   │   │   ├── Role.php

&#x20;   │   │   ├── User.php

&#x20;   │   │   └── UserAttachment.php

&#x20;   │   ├── Events/

&#x20;   │   │   ├── RoleAssigned.php

&#x20;   │   │   ├── UserCreated.php

&#x20;   │   │   └── UserUpdated.php

&#x20;   │   ├── Exceptions/

&#x20;   │   │   ├── AttachmentNotFoundException.php

&#x20;   │   │   ├── RoleNotFoundException.php

&#x20;   │   │   └── UserNotFoundException.php

&#x20;   │   └── RepositoryInterfaces/

&#x20;   │       ├── PermissionRepositoryInterface.php

&#x20;   │       ├── RoleRepositoryInterface.php

&#x20;   │       ├── UserAttachmentRepositoryInterface.php

&#x20;   │       └── UserRepositoryInterface.php

&#x20;   ├── Infrastructure/

&#x20;   │   ├── Http/

&#x20;   │   │   ├── Controllers/

&#x20;   │   │   ├── Requests/

&#x20;   │   │   └── Resources/

&#x20;   │   ├── Persistence/

&#x20;   │   │   └── Eloquent/

&#x20;   │   │       ├── Models/

&#x20;   │   │       │   ├── PermissionModel.php

&#x20;   │   │       │   ├── RoleModel.php

&#x20;   │   │       │   ├── UserAttachmentModel.php

&#x20;   │   │       │   └── UserModel.php

&#x20;   │   │       └── Repositories/

&#x20;   │   │           ├── EloquentPermissionRepository.php

&#x20;   │   │           ├── EloquentRoleRepository.php

&#x20;   │   │           ├── EloquentUserAttachmentRepository.php

&#x20;   │   │           └── EloquentUserRepository.php

&#x20;   │   └── Providers/

&#x20;   │       └── UserServiceProvider.php

&#x20;   ├── config/

&#x20;   ├── database/

&#x20;   │   └── migrations/

&#x20;   │       ├── 2025\_01\_01\_000001\_create\_users\_table.php

&#x20;   │       ├── 2025\_01\_01\_000002\_create\_roles\_table.php

&#x20;   │       ├── 2025\_01\_01\_000003\_create\_permissions\_table.php

&#x20;   │       ├── 2025\_01\_01\_000004\_create\_role\_user\_table.php

&#x20;   │       ├── 2025\_01\_01\_000005\_create\_permission\_role\_table.php

&#x20;   │       └── 2025\_01\_01\_000006\_create\_user\_attachments\_table.php

&#x20;   └── routes/

&#x20;       └── api.php

