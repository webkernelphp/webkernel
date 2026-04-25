⚠️ **CRITICAL: NO COMMITS ALLOWED DURING THIS REFACTORING** ⚠️

Once this refactoring begins, **DO NOT CREATE ANY GIT COMMITS** until the entire migration is complete and verified. All changes must be staged together as a single atomic refactoring commit at the end.

⚠️ **IMPORTANT: DIRECTORY STRUCTURE ALONE DOES NOT GUARANTEE CORRECTNESS** ⚠️

The presence of correctly organized directories and updated namespace declarations is necessary but NOT sufficient. After directory migration and namespace updates:

1. **File contents may still be wrong** - Just because a file is in the correct directory doesn't mean its imports and code are correct
2. **References may break** - Manual namespace updates must catch ALL class references, not just `use` statements
3. **Configuration must be verified** - Service providers, facades, registries, and config files must be checked for broken references
4. **Tests must pass** - The entire test suite must pass, not just have no syntax errors (at the end do a vendor/bin/phpstan)

Each phase requires careful verification to ensure no imports or references are broken.

---

## **FINAL COMPLETE DIRECTORY STRUCTURE**

```
bootstrap/webkernel/src/
│
├── WebApp.php                              [ANCHOR]
├── ServiceProvider.php                     [ANCHOR]
│
│
├── ╔═══════════════════════════════════════════════════╗
├── ║        TOP-LEVEL ONLY (Minimal, Entry Points)     ║
├── ║            Everything else → Base/                ║
├── ╚═══════════════════════════════════════════════════╝
│
├── Traits/                                 ← Generic mixins
│   ├── HasBackgroundTasks.php
│   ├── HasIdentifiers.php
│   ├── HasQuickTouch.php
│   └── HasSelfResolvedView.php
│
├── Plugins/                                ← Plugin system
│   ├── PluginRegistry.php
│   └── PluginLoader.php
│
├── Providers/                              ← Laravel service provider registration
│   ├── CommandServiceProvider.php
│   ├── FilamentRenderHooks.php
│   ├── IndexAwareViewFinder.php
│   └── ViewPathsAndComponents.php
│
├── Http/                                   ← HTTP layer (routes, middleware, controllers)
│   ├── Middleware/
│   │   ├── CheckBusinessAccess.php
│   │   ├── CheckModuleAccess.php
│   │   ├── CheckSystemAccess.php
│   │   └── ResolveDomainContext.php
│   │
│   ├── Controllers/
│   │   └── RootController.php
│   │
│   └── Routes/
│       ├── api.php
│       ├── web.php
│       └── channels.php
│
├── Facades/                                ← Public API
│   ├── Users.php
│   ├── Businesses.php
│   ├── Databases.php
│   ├── Modules.php
│   ├── Domains.php
│   └── Audit.php
│
├── Registries/                             ← Runtime documentation & discovery
│   ├── DomainRegistry.php
│   ├── ActionRegistry.php
│   ├── ServiceRegistry.php
│   ├── ContractRegistry.php
│   ├── ExceptionRegistry.php
│   └── EventRegistry.php
│
├── Console/                                ← Artisan commands
│   ├── Commands/
│   │   ├── GenerateFacadeCommand.php
│   │   ├── DocumentDomainCommand.php
│   │   ├── ListDomainsCommand.php
│   │   └── ListActionsCommand.php
│   │
│   └── Kernel.php
│
│
│
├── ╔═══════════════════════════════════════════════════╗
├── ║      BASE: ALL FRAMEWORK FEATURES & DOMAINS       ║
├── ║            Webkernel\Base\*                       ║
├── ║    (No more scattered folders at root!)           ║
├── ╚═══════════════════════════════════════════════════╝
│
├── Base/
│   │
│   ├── ┌─────────────────────────────────────────────────┐
│   ├── │ BUSINESS DOMAINS                                │
│   ├── └─────────────────────────────────────────────────┘
│   │
│   ├── Users/
│   │   ├── UserDomain.php
│   │   ├── UserDomainProvider.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   └── UserPrivilege.php
│   │   ├── Actions/
│   │   │   ├── CreateUserAction.php
│   │   │   ├── UpdateUserAction.php
│   │   │   ├── DeleteUserAction.php
│   │   │   ├── ActivateUserAction.php
│   │   │   └── ResetPasswordAction.php
│   │   ├── Services/
│   │   │   ├── UserAuthService.php
│   │   │   ├── UserPermissionService.php
│   │   │   └── UserMailService.php
│   │   ├── Enums/
│   │   │   ├── UserOrigin.php
│   │   │   ├── UserStatus.php
│   │   │   └── UserPrivilegeLevel.php
│   │   ├── Exceptions/
│   │   │   ├── UserNotFoundException.php
│   │   │   ├── UserAlreadyExistsException.php
│   │   │   ├── InvalidUserStatusException.php
│   │   │   └── InsufficientPrivilegesException.php
│   │   ├── Contracts/
│   │   │   ├── UserRepository.php
│   │   │   ├── UserAuthContract.php
│   │   │   └── UserPermissionResolver.php
│   │   ├── Repositories/
│   │   │   └── EloquentUserRepository.php
│   │   └── Events/
│   │       ├── UserCreatedEvent.php
│   │       ├── UserUpdatedEvent.php
│   │       ├── UserDeletedEvent.php
│   │       └── UserActivatedEvent.php
│   │
│   ├── Businesses/
│   │   ├── BusinessDomain.php
│   │   ├── BusinessDomainProvider.php
│   │   ├── Models/
│   │   │   └── Business.php
│   │   ├── Actions/
│   │   │   ├── CreateBusinessAction.php
│   │   │   ├── UpdateBusinessAction.php
│   │   │   ├── DeleteBusinessAction.php
│   │   │   ├── ActivateBusinessAction.php
│   │   │   └── SuspendBusinessAction.php
│   │   ├── Services/
│   │   │   ├── BusinessProvisioningService.php
│   │   │   ├── BusinessAccessService.php
│   │   │   └── BusinessMetricsService.php
│   │   ├── Enums/
│   │   │   └── BusinessStatus.php
│   │   ├── Exceptions/
│   │   │   ├── BusinessNotFoundException.php
│   │   │   ├── BusinessNotAccessibleException.php
│   │   │   └── InvalidBusinessStatusException.php
│   │   ├── Contracts/
│   │   │   ├── BusinessRepository.php
│   │   │   ├── BusinessProvisioner.php
│   │   │   └── BusinessAccessResolver.php
│   │   ├── Repositories/
│   │   │   └── EloquentBusinessRepository.php
│   │   └── Events/
│   │       ├── BusinessCreatedEvent.php
│   │       ├── BusinessUpdatedEvent.php
│   │       ├── BusinessDeletedEvent.php
│   │       ├── BusinessActivatedEvent.php
│   │       └── BusinessSuspendedEvent.php
│   │
│   ├── Databases/
│   │   ├── DatabaseDomain.php
│   │   ├── DatabaseDomainProvider.php
│   │   ├── Models/
│   │   │   └── DbConnection.php
│   │   ├── Actions/
│   │   │   ├── CreateDbConnectionAction.php
│   │   │   ├── UpdateDbConnectionAction.php
│   │   │   ├── DeleteDbConnectionAction.php
│   │   │   └── VerifyDbConnectionAction.php
│   │   ├── Services/
│   │   │   ├── DatabaseConnectionResolver.php
│   │   │   ├── DbConfigBuilder.php
│   │   │   ├── DbConnectionVerifier.php
│   │   │   └── DbBackupService.php
│   │   ├── Enums/
│   │   │   ├── DbDriver.php
│   │   │   └── DbConnectionStatus.php
│   │   ├── Exceptions/
│   │   │   ├── DbConnectionNotFoundException.php
│   │   │   ├── DbConnectionVerificationFailedException.php
│   │   │   ├── InvalidDatabaseDriverException.php
│   │   │   └── ConnectionAlreadyVerifiedException.php
│   │   ├── Contracts/
│   │   │   ├── DatabaseRepository.php
│   │   │   ├── DbVerifier.php
│   │   │   └── DbConfigBuilder.php
│   │   ├── Repositories/
│   │   │   └── EloquentDatabaseRepository.php
│   │   └── Events/
│   │       ├── DbConnectionCreatedEvent.php
│   │       ├── DbConnectionVerifiedEvent.php
│   │       ├── DbConnectionFailedEvent.php
│   │       └── DbConnectionDeletedEvent.php
│   │
│   ├── Domains/
│   │   ├── DomainDomain.php
│   │   ├── DomainDomainProvider.php
│   │   ├── Models/
│   │   │   └── Domain.php
│   │   ├── Actions/
│   │   │   ├── CreateDomainAction.php
│   │   │   ├── UpdateDomainAction.php
│   │   │   └── DeleteDomainAction.php
│   │   ├── Services/
│   │   │   ├── DomainRouterService.php
│   │   │   └── DomainContextResolver.php
│   │   ├── Enums/
│   │   │   ├── PanelType.php
│   │   │   └── DomainStatus.php
│   │   ├── Exceptions/
│   │   │   ├── DomainNotFoundException.php
│   │   │   └── InvalidDomainNameException.php
│   │   ├── Contracts/
│   │   │   ├── DomainRepository.php
│   │   │   └── DomainRouter.php
│   │   ├── Repositories/
│   │   │   └── EloquentDomainRepository.php
│   │   └── Events/
│   │       ├── DomainCreatedEvent.php
│   │       ├── DomainUpdatedEvent.php
│   │       └── DomainDeletedEvent.php
│   │
│   ├── Audit/
│   │   ├── AuditDomain.php
│   │   ├── AuditDomainProvider.php
│   │   ├── Models/
│   │   │   └── AuditLog.php
│   │   ├── Services/
│   │   │   ├── AuditLoggerService.php
│   │   │   └── AuditReportService.php
│   │   ├── Exceptions/
│   │   │   └── AuditLogNotFoundException.php
│   │   ├── Contracts/
│   │   │   └── AuditRepository.php
│   │   ├── Repositories/
│   │   │   └── EloquentAuditRepository.php
│   │   └── Events/
│   │       └── AuditedEvent.php
│   │
│   │
│   ├── ┌─────────────────────────────────────────────────┐
│   ├── │ FRAMEWORK FEATURES (NOT domains, but core)      │
│   ├── └─────────────────────────────────────────────────┘
│   │
│   ├── System/                            ← Framework internals
│   │   ├── Access/
│   │   │   └── Managers/
│   │   │       ├── AppManager.php
│   │   │       ├── AuthManager.php
│   │   │       ├── ContextManager.php
│   │   │       ├── RuntimeManager.php
│   │   │       ├── SecurityManager.php
│   │   │       └── UsersManager.php
│   │   │
│   │   ├── Host/
│   │   │   ├── Managers/
│   │   │   │   ├── HostManager.php
│   │   │   │   ├── InstanceManager.php
│   │   │   │   ├── OsManager.php
│   │   │   │   └── VersionManager.php
│   │   │   │
│   │   │   ├── Dto/
│   │   │   │   ├── CpuInfo.php
│   │   │   │   ├── DiskInfo.php
│   │   │   │   ├── MemoryInfo.php
│   │   │   │   ├── OsInfo.php
│   │   │   │   ├── PhpInfo.php
│   │   │   │   └── VersionInfo.php
│   │   │   │
│   │   │   ├── Enums/
│   │   │   │   ├── OsFamily.php
│   │   │   │   └── RuntimeSapi.php
│   │   │   │
│   │   │   └── Services/
│   │   │       ├── OsDetectionService.php
│   │   │       └── PhpReleasesService.php
│   │   │
│   │   ├── Ops/
│   │   │   ├── Contracts/
│   │   │   │   ├── CrudProvider.php
│   │   │   │   ├── DatabaseSchemaProvider.php
│   │   │   │   ├── Provider.php
│   │   │   │   ├── SourceProvider.php
│   │   │   │   └── SourceProviderWithMetadata.php
│   │   │   │
│   │   │   ├── Providers/
│   │   │   │   ├── ApiProvider.php
│   │   │   │   ├── ArrayProvider.php
│   │   │   │   ├── DatabaseProvider.php
│   │   │   │   └── FileProvider.php
│   │   │   │
│   │   │   └── OperationBuilder.php
│   │   │
│   │   ├── Security/
│   │   │   ├── CoreManifest.php
│   │   │   └── SealEnforcer.php
│   │   │
│   │   └── WebkernelAPI.php
│   │
│   ├── Generators/                       ← ID generation, image generation
│   │   ├── GradientImage/
│   │   │   └── GradientGenerator.php
│   │   │
│   │   └── UniqueId/
│   │       ├── UniqueIdGenerator.php
│   │       ├── Registry/
│   │       │   └── IdentifierRegistry.php
│   │       │
│   │       ├── Strategy/
│   │       │   ├── AbstractStrategy.php
│   │       │   ├── Cuid2Strategy.php
│   │       │   ├── EpochStrategy.php
│   │       │   ├── NanoIdStrategy.php
│   │       │   ├── NanoStrategy.php
│   │       │   ├── RandomStrategy.php
│   │       │   ├── SequentialStrategy.php
│   │       │   ├── ShortHashStrategy.php
│   │       │   ├── SqidsStrategy.php
│   │       │   ├── UlidStrategy.php
│   │       │   ├── UsernameStrategy.php
│   │       │   ├── UuidV4Strategy.php
│   │       │   └── UuidV7Strategy.php
│   │       │
│   │       └── Contracts/
│   │           └── IdentifierStrategyInterface.php
│   │
│   ├── Integration/                      ← External system adapters (Git, API, MCP)
│   │   ├── Git/
│   │   │   ├── Contracts/
│   │   │   │   └── GitHostAdapter.php
│   │   │   │
│   │   │   ├── Hosting/
│   │   │   │   ├── GitHubAdapter.php
│   │   │   │   └── GitLabAdapter.php
│   │   │   │
│   │   │   ├── Local/
│   │   │   │   ├── GitResult.php
│   │   │   │   └── GitRunner.php
│   │   │   │
│   │   │   ├── AdapterResolver.php
│   │   │   ├── Archive.php
│   │   │   ├── Checksum.php
│   │   │   ├── HttpGitClient.php
│   │   │   │
│   │   │   └── Exceptions/
│   │   │       ├── IntegrityException.php
│   │   │       └── NetworkException.php
│   │   │
│   │   ├── Api/
│   │   │   ├── global/
│   │   │   │   ├── Rest/
│   │   │   │   ├── GraphQL/
│   │   │   │   └── Soap/
│   │   │   │
│   │   │   └── local/
│   │   │       └── InternalApi/
│   │   │
│   │   ├── Webhooks/
│   │   │   ├── Contracts/
│   │   │   │   └── WebhookHandler.php
│   │   │   │
│   │   │   └── Handlers/
│   │   │       ├── GitHubWebhookHandler.php
│   │   │       └── GitLabWebhookHandler.php
│   │   │
│   │   ├── MCP/                          ← Model Context Protocol
│   │   │   ├── Chrome/
│   │   │   ├── Filesystem/
│   │   │   ├── Kubernetes/
│   │   │   ├── Macos/
│   │   │   ├── PDF/
│   │   │   ├── PowerPoint/
│   │   │   ├── Windows/
│   │   │   └── Word/
│   │   │
│   │   ├── RegistryInstaller.php         ← Module installation from registries
│   │   ├── RegistryAccounts.php
│   │   ├── RegistryCredentials.php
│   │   │
│   │   └── Models/
│   │       ├── RegistryAccount.php
│   │       └── RegistryKey.php
│   │
│   ├── Connectors/                       ← Data source connectors (abstraction layer)
│   │   ├── Contracts/
│   │   │   ├── SourceContract.php
│   │   │   └── ConnectorContract.php
│   │   │
│   │   ├── SourceRegistry.php
│   │   └── Traits/
│   │       └── ... (shared traits for connectors)
│   │
│   ├── Communication/                    ← Messaging delivery (shared infra)
│   │   ├── Chat/                         ← PascalCase
│   │   │   ├── Global/
│   │   │   │   ├── Discord/
│   │   │   │   ├── Slack/
│   │   │   │   └── Telegram/
│   │   │   │
│   │   │   └── Local/
│   │   │       └── InternalChat/
│   │   │
│   │   ├── Email/
│   │   │   ├── Global/
│   │   │   │   ├── Postmark/
│   │   │   │   └── SMTP/
│   │   │   │
│   │   │   ├── Local/
│   │   │   │   └── LocalSMTP/
│   │   │   │
│   │   │   └── Mailer.php                ← Core mailer abstraction
│   │   │
│   │   ├── InApp/
│   │   │   ├── Banner/
│   │   │   ├── Database/
│   │   │   └── Realtime/
│   │   │
│   │   ├── Push/
│   │   │   ├── Global/
│   │   │   │   ├── Firebase/
│   │   │   │   └── OneSignal/
│   │   │   │
│   │   │   └── Local/
│   │   │       └── LocalPush/
│   │   │
│   │   ├── SMS/
│   │   │   ├── Global/
│   │   │   │   ├── Twilio/
│   │   │   │   └── Vonage/
│   │   │   │
│   │   │   └── Local/
│   │   │       ├── Inwi/
│   │   │       ├── MarocTelecom/
│   │   │       └── OrangeMaroc/
│   │   │
│   │   ├── Social/
│   │   │   ├── Global/
│   │   │   │   ├── Facebook/
│   │   │   │   ├── LinkedIn/
│   │   │   │   └── Twitter/
│   │   │   │
│   │   │   └── Local/
│   │   │       └── LocalNetwork/
│   │   │
│   │   ├── Voice/
│   │   │   ├── Global/
│   │   │   │   ├── Twilio/
│   │   │   │   └── Vonage/
│   │   │   │
│   │   │   └── Local/
│   │   │       └── LocalVoice/
│   │   │
│   │   └── WhatsApp/
│   │       └── Global/
│   │           ├── BusinessAPI/
│   │           └── CloudAPI/
│   │
│   ├── Auth/                             ← Authentication infrastructure
│   │   ├── UserInfo.php
│   │   ├── Providers/
│   │   │   ├── GoogleProvider.php
│   │   │   ├── GitHubProvider.php
│   │   │   ├── LinkedInProvider.php
│   │   │   └── CustomProvider.php
│   │   │
│   │   ├── Services/
│   │   │   ├── AuthenticationService.php
│   │   │   ├── TokenService.php
│   │   │   └── SessionService.php
│   │   │
│   │   ├── Contracts/
│   │   │   ├── AuthProvider.php
│   │   │   └── TokenGenerator.php
│   │   │
│   │   └── Security/
│   │       ├── PasswordHasher.php
│   │       └── EncryptionService.php
│   │
│   ├── Arcanes/                          ← Module management + Scaffolding (code generation)
│   │   │
│   │   ├── Modules/                      ← MODULE MANAGEMENT (merged here)
│   │   │   ├── ModuleDomain.php
│   │   │   ├── ModuleDomainProvider.php
│   │   │   │
│   │   │   ├── Models/
│   │   │   │   ├── Module.php
│   │   │   │   └── BusinessModuleMap.php
│   │   │   │
│   │   │   ├── Actions/
│   │   │   │   ├── InstallModuleAction.php
│   │   │   │   ├── UninstallModuleAction.php
│   │   │   │   ├── EnableModuleAction.php
│   │   │   │   ├── DisableModuleAction.php
│   │   │   │   └── UpdateModuleAction.php
│   │   │   │
│   │   │   ├── Services/
│   │   │   │   ├── ModuleDiscoveryService.php
│   │   │   │   ├── ModuleInstallerService.php
│   │   │   │   ├── ModuleVersionResolver.php
│   │   │   │   └── ModuleAccessService.php
│   │   │   │
│   │   │   ├── Enums/
│   │   │   │   └── ModuleStatus.php
│   │   │   │
│   │   │   ├── Exceptions/
│   │   │   │   ├── ModuleNotFoundException.php
│   │   │   │   ├── ModuleInstallationFailedException.php
│   │   │   │   ├── ModuleAlreadyInstalledException.php
│   │   │   │   └── InvalidModuleVersionException.php
│   │   │   │
│   │   │   ├── Contracts/
│   │   │   │   ├── ModuleRepository.php
│   │   │   │   ├── ModuleInstaller.php
│   │   │   │   └── ModuleLoader.php
│   │   │   │
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentModuleRepository.php
│   │   │   │
│   │   │   └── Events/
│   │   │       ├── ModuleInstalledEvent.php
│   │   │       ├── ModuleUninstalledEvent.php
│   │   │       ├── ModuleEnabledEvent.php
│   │   │       ├── ModuleDisabledEvent.php
│   │   │       └── ModuleUpdatedEvent.php
│   │   │
│   │   ├── Scaffolding/                  ← CODE GENERATION / SCAFFOLDING
│   │   │   ├── Commands/
│   │   │   │   ├── DeclareCommands.php
│   │   │   │   └── MakeModule.php
│   │   │   │
│   │   │   ├── Matrix/
│   │   │   │   ├── ArtifactMatrix.php
│   │   │   │   └── NamingHelper.php
│   │   │   │
│   │   │   ├── Scaffold/
│   │   │   │   ├── Scaffolder.php
│   │   │   │   ├── ScaffoldParams.php
│   │   │   │   ├── ScaffoldResult.php
│   │   │   │   └── StubRenderer.php
│   │   │   │
│   │   │   └── Stubs/
│   │   │       ├── Domain.stub
│   │   │       ├── Model.stub
│   │   │       ├── Action.stub
│   │   │       ├── Service.stub
│   │   │       ├── Repository.stub
│   │   │       ├── Event.stub
│   │   │       ├── Exception.stub
│   │   │       └── ... (more stubs)
│   │   │
│   │   └── ArcanesServiceProvider.php
│   │
│   ├── Builders/                         ← Builder umbrella (for future: Website, Workflow, Mail, NoCode, Docs, Word, Excel)
│   │   ├── DBStudio/
│   │   │   ├── Backend/                  ← Business logic ONLY
│   │   │   │   ├── Models/
│   │   │   │   │   ├── StudioCollection.php
│   │   │   │   │   ├── StudioDashboard.php
│   │   │   │   │   ├── StudioField.php
│   │   │   │   │   ├── StudioPanel.php
│   │   │   │   │   ├── StudioRecord.php
│   │   │   │   │   ├── StudioRecordVersion.php
│   │   │   │   │   ├── StudioApiKey.php
│   │   │   │   │   ├── StudioSavedFilter.php
│   │   │   │   │   └── StudioFieldOption.php
│   │   │   │   │
│   │   │   │   ├── FieldTypes/
│   │   │   │   │   ├── AbstractFieldType.php
│   │   │   │   │   ├── FieldTypeRegistry.php
│   │   │   │   │   │
│   │   │   │   │   └── Types/
│   │   │   │   │       ├── TextFieldType.php
│   │   │   │   │       ├── NumberFieldType.php
│   │   │   │   │       ├── SelectFieldType.php
│   │   │   │   │       ├── DateFieldType.php
│   │   │   │   │       ├── FileFieldType.php
│   │   │   │   │       ├── RelationFieldType.php
│   │   │   │   │       ├── RichEditorFieldType.php
│   │   │   │   │       └── ... (40+ field types)
│   │   │   │   │
│   │   │   │   ├── Panels/
│   │   │   │   │   ├── AbstractStudioPanel.php
│   │   │   │   │   ├── PanelTypeRegistry.php
│   │   │   │   │   │
│   │   │   │   │   └── Types/
│   │   │   │   │       ├── BarChartPanel.php
│   │   │   │   │       ├── LineChartPanel.php
│   │   │   │   │       ├── PieChartPanel.php
│   │   │   │   │       ├── MetricPanel.php
│   │   │   │   │       ├── ListPanel.php
│   │   │   │   │       ├── LabelPanel.php
│   │   │   │   │       ├── MeterPanel.php
│   │   │   │   │       ├── TimeSeriesPanel.php
│   │   │   │   │       └── VariablePanel.php
│   │   │   │   │
│   │   │   │   ├── Services/
│   │   │   │   │   ├── CollectionSeeder.php
│   │   │   │   │   ├── DynamicFiltersBuilder.php
│   │   │   │   │   ├── DynamicFormSchemaBuilder.php
│   │   │   │   │   ├── DynamicTableColumnsBuilder.php
│   │   │   │   │   ├── EavQueryBuilder.php
│   │   │   │   │   ├── ConditionEvaluator.php
│   │   │   │   │   ├── LocaleResolver.php
│   │   │   │   │   └── VariableResolver.php
│   │   │   │   │
│   │   │   │   ├── Enums/
│   │   │   │   │   ├── FilterOperator.php
│   │   │   │   │   ├── SortDirection.php
│   │   │   │   │   ├── FieldWidth.php
│   │   │   │   │   ├── FillType.php
│   │   │   │   │   ├── CurveType.php
│   │   │   │   │   ├── AggregateFunction.php
│   │   │   │   │   ├── GroupPrecision.php
│   │   │   │   │   ├── PanelPlacement.php
│   │   │   │   │   ├── StudioPermission.php
│   │   │   │   │   ├── ApiAction.php
│   │   │   │   │   └── EavCast.php
│   │   │   │   │
│   │   │   │   ├── Contracts/
│   │   │   │   │   ├── FieldTypeContract.php
│   │   │   │   │   └── PanelContract.php
│   │   │   │   │
│   │   │   │   └── Database/
│   │   │   │       └── migrations/
│   │   │   │           ├── create_wdb_studio_collections_table.php
│   │   │   │           ├── create_wdb_studio_fields_table.php
│   │   │   │           ├── create_wdb_studio_panels_table.php
│   │   │   │           └── ... (13 migrations)
│   │   │   │
│   │   │   ├── Api/                      ← API for DBStudio
│   │   │   │   ├── Middleware/
│   │   │   │   │   └── ValidateApiKey.php
│   │   │   │   │
│   │   │   │   ├── Resources/
│   │   │   │   │   ├── RecordCollection.php
│   │   │   │   │   └── RecordResource.php
│   │   │   │   │
│   │   │   │   ├── StudioApiController.php
│   │   │   │   ├── StudioApiRouteRegistrar.php
│   │   │   │   │
│   │   │   │   └── OpenApi/
│   │   │   │       ├── StudioDocumentTransformer.php
│   │   │   │       └── StudioOperationTransformer.php
│   │   │   │
│   │   │   └── Observers/
│   │   │       ├── RecordVersioningObserver.php
│   │   │       └── StudioCollectionObserver.php
│   │   │
│   │   ├── WebsiteBuilder/                ← Future builder
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Components/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   ├── WorkflowBuilder/               ← Future builder
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Steps/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   ├── MailBuilder/                   ← Future builder
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Templates/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   ├── NoCodeAppBuilder/              ← Future builder
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Components/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   ├── DocsEquivalent/                ← Google Docs clone (self-hosted, free)
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Realtime/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   ├── WordEquivalent/                ← Word clone (self-hosted, free)
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Export/
│   │   │   ├── Actions/
│   │   │   ├── Events/
│   │   │   ├── Exceptions/
│   │   │   └── Contracts/
│   │   │
│   │   └── ExcelEquivalent/               ← Excel clone (self-hosted, free)
│   │       ├── Models/
│   │       ├── Services/
│   │       ├── Formulas/
│   │       ├── Actions/
│   │       ├── Events/
│   │       ├── Exceptions/
│   │       └── Contracts/
│
│
│
├── ╔═══════════════════════════════════════════════════╗
├── ║    PRESENTATION LAYER (Filament Panels)           ║
├── ║           (Namespace: wcp-{panel_id}::)           ║
├── ╚═══════════════════════════════════════════════════╝
│
├── Panels/                               ← Panel definitions
│   ├── SystemPanel/
│   │   ├── SystemPanelProvider.php
│   │   ├── Resources/
│   │   │   ├── AuditLogResource.php
│   │   │   └── Pages/
│   │   │       ├── ListAuditLogs.php
│   │   │       └── ViewAuditLog.php
│   │   │
│   │   ├── Pages/
│   │   │   ├── SystemInfoPage.php
│   │   │   ├── ServerHealthPage.php
│   │   │   └── MaintenancePage.php
│   │   │
│   │   ├── Widgets/
│   │   │   ├── SystemHealthWidget.php
│   │   │   ├── UptimeWidget.php
│   │   │   └── ActivityLogWidget.php
│   │   │
│   │   ├── views/
│   │   │   └── components/
│   │   │       ├── status-badge.blade.php
│   │   │       ├── health-indicator.blade.php
│   │   │       └── metric-card.blade.php
│   │   │
│   │   ├── routes.php
│   │   └── config.php
│   │
│   ├── AdminPanel/
│   │   ├── AdminPanelProvider.php
│   │   │
│   │   ├── Resources/
│   │   │   ├── Users/
│   │   │   │   ├── UserResource.php
│   │   │   │   ├── Pages/
│   │   │   │   │   ├── ListUsers.php
│   │   │   │   │   ├── CreateUser.php
│   │   │   │   │   ├── EditUser.php
│   │   │   │   │   └── ViewUser.php
│   │   │   │   │
│   │   │   │   ├── Forms/
│   │   │   │   │   ├── UserForm.php
│   │   │   │   │   └── PrivilegeForm.php
│   │   │   │   │
│   │   │   │   ├── Tables/
│   │   │   │   │   └── UsersTable.php
│   │   │   │   │
│   │   │   │   ├── Widgets/
│   │   │   │   │   ├── UserStatsWidget.php
│   │   │   │   │   └── RecentUsersWidget.php
│   │   │   │   │
│   │   │   │   ├── Listeners/
│   │   │   │   │   ├── RefreshListWhenUserCreated.php
│   │   │   │   │   └── ShowNotificationWhenUserActivated.php
│   │   │   │   │
│   │   │   │   └── views/
│   │   │   │       └── components/
│   │   │   │           ├── user-avatar.blade.php
│   │   │   │           └── privilege-badge.blade.php
│   │   │   │
│   │   │   ├── Businesses/
│   │   │   │   ├── BusinessResource.php
│   │   │   │   ├── Pages/
│   │   │   │   ├── Forms/
│   │   │   │   ├── Tables/
│   │   │   │   ├── Widgets/
│   │   │   │   ├── Listeners/
│   │   │   │   └── views/
│   │   │   │
│   │   │   ├── Databases/
│   │   │   │   ├── DbConnectionResource.php
│   │   │   │   ├── Pages/
│   │   │   │   ├── Forms/
│   │   │   │   ├── Tables/
│   │   │   │   ├── Widgets/
│   │   │   │   ├── Listeners/
│   │   │   │   └── views/
│   │   │   │
│   │   │   ├── Domains/
│   │   │   │   ├── DomainResource.php
│   │   │   │   ├── Pages/
│   │   │   │   ├── Forms/
│   │   │   │   ├── Tables/
│   │   │   │   ├── Listeners/
│   │   │   │   └── views/
│   │   │   │
│   │   │   └── Audit/
│   │   │       ├── AuditLogResource.php
│   │   │       ├── Pages/
│   │   │       ├── Tables/
│   │   │       ├── Filters/
│   │   │       └── views/
│   │   │
│   │   ├── Pages/
│   │   │   ├── Dashboard.php
│   │   │   └── SettingsPage.php
│   │   │
│   │   ├── Widgets/
│   │   │   ├── QuickStatsWidget.php
│   │   │   └── RecentActivityWidget.php
│   │   │
│   │   ├── views/
│   │   │   └── components/
│   │   │
│   │   ├── routes.php
│   │   └── config.php
│   │
│   ├── ModulePanel/
│   │   ├── ModulePanelProvider.php
│   │   ├── Resources/
│   │   ├── Pages/
│   │   ├── Widgets/
│   │   ├── views/
│   │   ├── routes.php
│   │   └── config.php
│   │
│   ├── BusinessPanel/
│   │   ├── BusinessPanelProvider.php
│   │   ├── Resources/
│   │   ├── Pages/
│   │   ├── Widgets/
│   │   ├── views/
│   │   ├── routes.php
│   │   └── config.php
│   │
│   ├── ... (7+ more panels)
│   │
│   └── Shared/                           ← Shared across all panels
│       ├── Components/
│       │   ├── BaseWidget.php
│       │   ├── BaseResource.php
│       │   └── BaseTable.php
│       │
│       ├── views/
│       │   └── components/
│       │       ├── notification.blade.php
│       │       ├── modal-dialog.blade.php
│       │       ├── loading-spinner.blade.php
│       │       └── ...
│       │
│       └── Traits/
│           ├── HasTimestamps.php
│           └── HasSoftDeletes.php
│
├── CP/                                   ← Control panel (Filament presentation for Builders)
│   ├── Builders/
│   │   ├── DBStudio/                     ← DBStudio presentation ONLY
│   │   │   ├── Resources/
│   │   │   │   ├── CollectionManagerResource.php
│   │   │   │   ├── ApiSettingsResource.php
│   │   │   │   └── DashboardResource.php
│   │   │   │
│   │   │   ├── Pages/
│   │   │   │   ├── StudioDashboardPage.php
│   │   │   │   ├── CreateCollectionPage.php
│   │   │   │   ├── EditCollectionPage.php
│   │   │   │   └── ... (other pages)
│   │   │   │
│   │   │   ├── Widgets/
│   │   │   │   ├── BarChartWidget.php
│   │   │   │   ├── LineChartWidget.php
│   │   │   │   ├── PieChartWidget.php
│   │   │   │   ├── MetricWidget.php
│   │   │   │   ├── ListWidget.php
│   │   │   │   └── ... (other widgets)
│   │   │   │
│   │   │   ├── Livewire/
│   │   │   │   ├── FilterBuilder.php
│   │   │   │   └── LocaleSwitcher.php
│   │   │   │
│   │   │   └── views/
│   │   │       ├── components/
│   │   │       │   ├── filter-builder.blade.php
│   │   │       │   ├── locale-switcher.blade.php
│   │   │       │   └── ... (components)
│   │   │       │
│   │   │       ├── layouts/
│   │   │       │   └── studio-layout.blade.php
│   │   │       │
│   │   │       └── pages/
│   │   │           ├── dashboard.blade.php
│   │   │           └── ... (pages)
│   │   │
│   │   ├── WebsiteBuilder/               ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   ├── WorkflowBuilder/              ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   ├── MailBuilder/                  ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   ├── NoCodeAppBuilder/             ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   ├── DocsEquivalent/               ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   ├── WordEquivalent/               ← Future
│   │   │   ├── Resources/
│   │   │   ├── Pages/
│   │   │   ├── Widgets/
│   │   │   └── views/
│   │   │
│   │   └── ExcelEquivalent/              ← Future
│   │       ├── Resources/
│   │       ├── Pages/
│   │       ├── Widgets/
│   │       └── views/
│   │
│   ├── Providers/
│   │   ├── PresentationServiceProvider.php
│   │   └── ResourceServiceProvider.php
│   │
│   └── Support/
│       └── FilamentHelper.php
```

---

## **KEY POINTS**

✅ **Arcanes kept** - Not renamed  
✅ **Modules merged into Base/Arcanes/** - Module management + Scaffolding together  
✅ **Everything in Base/** - No scattered root folders  
✅ **Communication in Base/** - Messaging infrastructure  
✅ **Generators in Base/** - ID/image generation  
✅ **Integration in Base/** - External adapters  
✅ **Connectors in Base/** - Data source abstractions  
✅ **System in Base/** - Framework internals  
✅ **Auth expanded** - Full authentication infrastructure  
✅ **Builders umbrella** - Ready for Website, Workflow, Mail, NoCode, Docs, Word, Excel  
✅ **DBStudio split** - Backend in Base/Builders/DBStudio/, presentation in CP/Builders/DBStudio/  
✅ **PascalCase everywhere** - Chat/, Email/, not chat/, email/  
✅ **No duplicate names** - No System/System conflicts  
✅ **Panels self-contained** - Each has own Resources, Pages, Widgets  
✅ **Namespace: wcp-{panel_id}::** - For panel-specific views  
✅ **Arcanes/Modules** - Together managing modules + scaffolding

---

## **CLARIFICATION: UNCERTAIN DIRECTORY PLACEMENTS**

The following directories exist in the codebase but were not explicitly mentioned in the original architecture. This section clarifies their placement:

### **Directories That Stay at Root Level**

These are framework infrastructure or Laravel conventions and **remain at the root level** with namespace updates only:

| Directory        | Current Namespace                             | Action                                        | Reasoning                                            |
| ---------------- | --------------------------------------------- | --------------------------------------------- | ---------------------------------------------------- |
| `Traits/`        | `Webkernel\Traits`                            | ✅ Keep at root                               | Generic mixins - cross-cutting concerns              |
| `Plugins/`       | `Webkernel\Plugins`                           | ✅ Keep at root                               | Plugin registry system                               |
| `Providers/`     | `Webkernel\Providers`                         | ✅ Keep at root                               | Laravel service provider registration                |
| `Http/`          | `Webkernel\Http`                              | ✅ Keep at root                               | HTTP layer (routes, middleware, controllers)         |
| `Facades/`       | `Webkernel\Facades`                           | ✅ Keep at root                               | Public API surface                                   |
| `Registries/`    | `Webkernel\Registry` → `Webkernel\Registries` | ✅ Keep at root, rename Registry → Registries | Runtime documentation & discovery                    |
| `Console/`       | `Webkernel\Commands` → `Webkernel\Console`    | ✅ Rename to Console, keep at root            | Artisan commands (Laravel convention)                |
| `Async/`         | `Webkernel\Async`                             | ✅ Keep at root                               | Framework-level async infrastructure (Pool, Promise) |
| `Jobs/`          | `Webkernel\Jobs`                              | ✅ Keep at root                               | Queue jobs (Laravel convention)                      |
| `Notifications/` | `Webkernel\Notifications`                     | ✅ Keep at root                               | Notification drivers (Laravel convention)            |
| `CP/`            | `Webkernel\CP`                                | ✅ Already correct                            | Control panel presentation layer (Filament)          |

### **Directories to Move into Base/**

These are business/framework features that belong in `Base/`:

| Directory        | Current Namespace         | New Namespace                    | Action                                            | Placement               |
| ---------------- | ------------------------- | -------------------------------- | ------------------------------------------------- | ----------------------- |
| `Users/`         | `Webkernel\Users`         | `Webkernel\Base\Users`           | Move into Base/                                   | `Base/Users/`           |
| `Businesses/`    | `Webkernel\Businesses`    | `Webkernel\Base\Businesses`      | Move into Base/                                   | `Base/Businesses/`      |
| `Databases/`     | `Webkernel\Databases`     | `Webkernel\Base\Databases`       | Move into Base/                                   | `Base/Databases/`       |
| `Domains/`       | `Webkernel\Domains`       | `Webkernel\Base\Domains`         | Move into Base/                                   | `Base/Domains/`         |
| `Audit/`         | `Webkernel\Audit`         | `Webkernel\Base\Audit`           | Move into Base/                                   | `Base/Audit/`           |
| `System/`        | `Webkernel\System`        | `Webkernel\Base\System`          | Move into Base/                                   | `Base/System/`          |
| `Generators/`    | `Webkernel\Generators`    | `Webkernel\Base\Generators`      | Move into Base/                                   | `Base/Generators/`      |
| `Integration/`   | `Webkernel\Integration`   | `Webkernel\Base\Integration`     | Move into Base/, update Integration/Mcp structure | `Base/Integration/`     |
| `Connectors/`    | `Webkernel\Connectors`    | `Webkernel\Base\Connectors`      | Move into Base/                                   | `Base/Connectors/`      |
| `Communication/` | `Webkernel\Communication` | `Webkernel\Base\Communication`   | Move into Base/, flatten Global/Local structure   | `Base/Communication/`   |
| `Auth/`          | `Webkernel\Auth`          | `Webkernel\Base\Auth`            | Move into Base/                                   | `Base/Auth/`            |
| `Arcanes/`       | `Webkernel\Arcanes`       | `Webkernel\Base\Arcanes`         | Move into Base/                                   | `Base/Arcanes/`         |
| `Modules/`       | `Webkernel\Modules`       | `Webkernel\Base\Arcanes\Modules` | Move into Base/Arcanes/, merge module management  | `Base/Arcanes/Modules/` |
| `Builders/`      | `Webkernel\Builders`      | `Webkernel\Base\Builders`        | Move into Base/                                   | `Base/Builders/`        |
| `Services/`      | `Webkernel\Services`      | `Webkernel\Base\Services`        | Move into Base/                                   | `Base/Services/`        |
| `Payment/`       | `Webkernel\Payment`       | `Webkernel\Base\Payment`         | Move into Base/                                   | `Base/Payment/`         |
| `Query/`         | `Webkernel\Query`         | `Webkernel\Base\Query`           | Move into Base/                                   | `Base/Query/`           |
| `QuickTouch/`    | `Webkernel\QuickTouch`    | `Webkernel\Base\QuickTouch`      | Move into Base/                                   | `Base/QuickTouch/`      |

### **Presentation Layer (CP/ subdirectories)**

These belong in the presentation layer under `CP/`:

| Current Location            | New Location                            | Action                                             |
| --------------------------- | --------------------------------------- | -------------------------------------------------- |
| `Pages/`                    | `CP/Pages/`                             | Move to presentation layer                         |
| `UI/`                       | `CP/UI/` or `Panels/Shared/Components/` | Move to presentation layer                         |
| `View/` (if view rendering) | `CP/Support/ViewHelpers/`               | Move to presentation, or check if shared utility   |
| `Routes/` (if separate)     | `Http/Routes/`                          | Move with HTTP layer                               |
| `Mcp/` (if separate)        | `Base/Integration/Mcp/`                 | Move to Integration if it's an integration adapter |

### **Special Handling**

1. **Enum → Enums**: In all domains (Users, Businesses, etc.), rename `Enum/` to `Enums/` to match the refactor task structure
2. **Security → Concerns**: In domains, rename `Security/` subdirectories to `Concerns/` to reflect trait/concern pattern
3. **Global/Local Flattening**: In Communication and Integration, remove Global/Local folder hierarchy and use kebab-case provider names
4. **Commands → Console**: Rename the Commands/ directory to Console/ and update namespace from `Webkernel\Commands` to `Webkernel\Console`
5. **Registry → Registries**: Rename Registry/ to Registries/ (plural) and update namespace

---

## **IMPLEMENTATION INSTRUCTIONS FOR AGENT**

This section provides step-by-step instructions for an agent to execute the full refactoring of `bootstrap/webkernel/src/`.

### **Phase 1: Pre-Refactoring Analysis**

1. **Read the current directory structure** in `bootstrap/webkernel/src/`
2. **Catalog all existing files** by path and namespace
3. **Identify all imports and dependencies** that will need updating
4. **Document trait subdirectories** - these will be renamed to "Concerns"
5. **Document Integration and Connectors subdirectories** - these will be converted to kebab-case
6. **Note any custom code** that depends on the old structure

### **Phase 2: Create Base Directory Infrastructure**

Create these directories (in order, parent to child):

```
bootstrap/webkernel/src/Base/
bootstrap/webkernel/src/Base/Users/
bootstrap/webkernel/src/Base/Users/Concerns/
bootstrap/webkernel/src/Base/Users/Models/
bootstrap/webkernel/src/Base/Users/Actions/
bootstrap/webkernel/src/Base/Users/Services/
bootstrap/webkernel/src/Base/Users/Enums/
bootstrap/webkernel/src/Base/Users/Exceptions/
bootstrap/webkernel/src/Base/Users/Contracts/
bootstrap/webkernel/src/Base/Users/Repositories/
bootstrap/webkernel/src/Base/Users/Events/

bootstrap/webkernel/src/Base/Businesses/
bootstrap/webkernel/src/Base/Businesses/Concerns/
bootstrap/webkernel/src/Base/Businesses/Models/
bootstrap/webkernel/src/Base/Businesses/Actions/
bootstrap/webkernel/src/Base/Businesses/Services/
bootstrap/webkernel/src/Base/Businesses/Enums/
bootstrap/webkernel/src/Base/Businesses/Exceptions/
bootstrap/webkernel/src/Base/Businesses/Contracts/
bootstrap/webkernel/src/Base/Businesses/Repositories/
bootstrap/webkernel/src/Base/Businesses/Events/

bootstrap/webkernel/src/Base/Databases/
bootstrap/webkernel/src/Base/Databases/Concerns/
bootstrap/webkernel/src/Base/Databases/Models/
bootstrap/webkernel/src/Base/Databases/Actions/
bootstrap/webkernel/src/Base/Databases/Services/
bootstrap/webkernel/src/Base/Databases/Enums/
bootstrap/webkernel/src/Base/Databases/Exceptions/
bootstrap/webkernel/src/Base/Databases/Contracts/
bootstrap/webkernel/src/Base/Databases/Repositories/
bootstrap/webkernel/src/Base/Databases/Events/

bootstrap/webkernel/src/Base/Domains/
bootstrap/webkernel/src/Base/Domains/Concerns/
bootstrap/webkernel/src/Base/Domains/Models/
bootstrap/webkernel/src/Base/Domains/Actions/
bootstrap/webkernel/src/Base/Domains/Services/
bootstrap/webkernel/src/Base/Domains/Enums/
bootstrap/webkernel/src/Base/Domains/Exceptions/
bootstrap/webkernel/src/Base/Domains/Contracts/
bootstrap/webkernel/src/Base/Domains/Repositories/
bootstrap/webkernel/src/Base/Domains/Events/

bootstrap/webkernel/src/Base/Audit/
bootstrap/webkernel/src/Base/Audit/Concerns/
bootstrap/webkernel/src/Base/Audit/Models/
bootstrap/webkernel/src/Base/Audit/Services/
bootstrap/webkernel/src/Base/Audit/Exceptions/
bootstrap/webkernel/src/Base/Audit/Contracts/
bootstrap/webkernel/src/Base/Audit/Repositories/
bootstrap/webkernel/src/Base/Audit/Events/

bootstrap/webkernel/src/Base/System/
bootstrap/webkernel/src/Base/System/Access/Managers/
bootstrap/webkernel/src/Base/System/Host/Managers/
bootstrap/webkernel/src/Base/System/Host/Dto/
bootstrap/webkernel/src/Base/System/Host/Enums/
bootstrap/webkernel/src/Base/System/Host/Services/
bootstrap/webkernel/src/Base/System/Ops/Contracts/
bootstrap/webkernel/src/Base/System/Ops/Providers/
bootstrap/webkernel/src/Base/System/Security/

bootstrap/webkernel/src/Base/Generators/
bootstrap/webkernel/src/Base/Generators/GradientImage/
bootstrap/webkernel/src/Base/Generators/UniqueId/Registry/
bootstrap/webkernel/src/Base/Generators/UniqueId/Strategy/
bootstrap/webkernel/src/Base/Generators/UniqueId/Contracts/

bootstrap/webkernel/src/Base/Integration/
bootstrap/webkernel/src/Base/Integration/Git/Contracts/
bootstrap/webkernel/src/Base/Integration/Git/Hosting/
bootstrap/webkernel/src/Base/Integration/Git/Local/
bootstrap/webkernel/src/Base/Integration/Git/Exceptions/
bootstrap/webkernel/src/Base/Integration/Api/
bootstrap/webkernel/src/Base/Integration/Webhooks/Contracts/
bootstrap/webkernel/src/Base/Integration/Webhooks/Handlers/
bootstrap/webkernel/src/Base/Integration/MCP/Chrome/
bootstrap/webkernel/src/Base/Integration/MCP/Filesystem/
bootstrap/webkernel/src/Base/Integration/MCP/Kubernetes/
bootstrap/webkernel/src/Base/Integration/MCP/Macos/
bootstrap/webkernel/src/Base/Integration/MCP/PDF/
bootstrap/webkernel/src/Base/Integration/MCP/PowerPoint/
bootstrap/webkernel/src/Base/Integration/MCP/Windows/
bootstrap/webkernel/src/Base/Integration/MCP/Word/
bootstrap/webkernel/src/Base/Integration/Models/

bootstrap/webkernel/src/Base/Connectors/
bootstrap/webkernel/src/Base/Connectors/Contracts/
bootstrap/webkernel/src/Base/Connectors/Traits/

bootstrap/webkernel/src/Base/Communication/
bootstrap/webkernel/src/Base/Communication/Chat/Global/Discord/
bootstrap/webkernel/src/Base/Communication/Chat/Global/Slack/
bootstrap/webkernel/src/Base/Communication/Chat/Global/Telegram/
bootstrap/webkernel/src/Base/Communication/Chat/Local/InternalChat/
bootstrap/webkernel/src/Base/Communication/Email/Global/Postmark/
bootstrap/webkernel/src/Base/Communication/Email/Global/SMTP/
bootstrap/webkernel/src/Base/Communication/Email/Local/LocalSMTP/
bootstrap/webkernel/src/Base/Communication/InApp/Banner/
bootstrap/webkernel/src/Base/Communication/InApp/Database/
bootstrap/webkernel/src/Base/Communication/InApp/Realtime/
bootstrap/webkernel/src/Base/Communication/Push/Global/Firebase/
bootstrap/webkernel/src/Base/Communication/Push/Global/OneSignal/
bootstrap/webkernel/src/Base/Communication/Push/Local/LocalPush/
bootstrap/webkernel/src/Base/Communication/SMS/Global/Twilio/
bootstrap/webkernel/src/Base/Communication/SMS/Global/Vonage/
bootstrap/webkernel/src/Base/Communication/SMS/Local/Inwi/
bootstrap/webkernel/src/Base/Communication/SMS/Local/MarocTelecom/
bootstrap/webkernel/src/Base/Communication/SMS/Local/OrangeMaroc/
bootstrap/webkernel/src/Base/Communication/Social/Global/Facebook/
bootstrap/webkernel/src/Base/Communication/Social/Global/LinkedIn/
bootstrap/webkernel/src/Base/Communication/Social/Global/Twitter/
bootstrap/webkernel/src/Base/Communication/Social/Local/LocalNetwork/
bootstrap/webkernel/src/Base/Communication/Voice/Global/Twilio/
bootstrap/webkernel/src/Base/Communication/Voice/Global/Vonage/
bootstrap/webkernel/src/Base/Communication/Voice/Local/LocalVoice/
bootstrap/webkernel/src/Base/Communication/WhatsApp/Global/BusinessAPI/
bootstrap/webkernel/src/Base/Communication/WhatsApp/Global/CloudAPI/

bootstrap/webkernel/src/Base/Auth/
bootstrap/webkernel/src/Base/Auth/Providers/
bootstrap/webkernel/src/Base/Auth/Services/
bootstrap/webkernel/src/Base/Auth/Contracts/
bootstrap/webkernel/src/Base/Auth/Security/

bootstrap/webkernel/src/Base/Arcanes/
bootstrap/webkernel/src/Base/Arcanes/Modules/Models/
bootstrap/webkernel/src/Base/Arcanes/Modules/Actions/
bootstrap/webkernel/src/Base/Arcanes/Modules/Services/
bootstrap/webkernel/src/Base/Arcanes/Modules/Enums/
bootstrap/webkernel/src/Base/Arcanes/Modules/Exceptions/
bootstrap/webkernel/src/Base/Arcanes/Modules/Contracts/
bootstrap/webkernel/src/Base/Arcanes/Modules/Repositories/
bootstrap/webkernel/src/Base/Arcanes/Modules/Events/
bootstrap/webkernel/src/Base/Arcanes/Scaffolding/Commands/
bootstrap/webkernel/src/Base/Arcanes/Scaffolding/Matrix/
bootstrap/webkernel/src/Base/Arcanes/Scaffolding/Scaffold/
bootstrap/webkernel/src/Base/Arcanes/Scaffolding/Stubs/

bootstrap/webkernel/src/Base/Builders/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Models/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/FieldTypes/Types/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Panels/Types/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Services/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Enums/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Contracts/
bootstrap/webkernel/src/Base/Builders/DBStudio/Backend/Database/migrations/
bootstrap/webkernel/src/Base/Builders/DBStudio/Api/Middleware/
bootstrap/webkernel/src/Base/Builders/DBStudio/Api/Resources/
bootstrap/webkernel/src/Base/Builders/DBStudio/Api/OpenApi/
bootstrap/webkernel/src/Base/Builders/DBStudio/Observers/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Models/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Services/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Components/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Actions/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Events/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Exceptions/
bootstrap/webkernel/src/Base/Builders/WebsiteBuilder/Contracts/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Models/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Services/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Steps/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Actions/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Events/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Exceptions/
bootstrap/webkernel/src/Base/Builders/WorkflowBuilder/Contracts/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Models/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Services/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Templates/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Actions/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Events/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Exceptions/
bootstrap/webkernel/src/Base/Builders/MailBuilder/Contracts/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Models/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Services/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Components/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Actions/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Events/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Exceptions/
bootstrap/webkernel/src/Base/Builders/NoCodeAppBuilder/Contracts/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Models/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Services/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Realtime/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Actions/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Events/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Exceptions/
bootstrap/webkernel/src/Base/Builders/DocsEquivalent/Contracts/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Models/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Services/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Export/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Actions/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Events/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Exceptions/
bootstrap/webkernel/src/Base/Builders/WordEquivalent/Contracts/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Models/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Services/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Formulas/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Actions/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Events/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Exceptions/
bootstrap/webkernel/src/Base/Builders/ExcelEquivalent/Contracts/
```

### **Phase 3: File Migration by Category**

#### **3.1: Domain Files (Users, Businesses, Databases, Domains, Audit)**

For each domain directory, migrate files with these namespace transformations:

**Before:**

```php
namespace Webkernel\Users\Models;
namespace Webkernel\Users\Actions;
namespace Webkernel\Users\Services;
namespace Webkernel\Users\Enum;
namespace Webkernel\Users\Security;
// ... etc
```

**After:**

```php
namespace Webkernel\Base\Users\Models;
namespace Webkernel\Base\Users\Actions;
namespace Webkernel\Base\Users\Services;
namespace Webkernel\Base\Users\Enums;
namespace Webkernel\Base\Users\Concerns;
// ... etc
```

**Important subdirectory renames within domains:**

- `Enum/` → `Enums/` (plural)
- `Security/` or trait folders → `Concerns/` (for shared traits/concerns)

Do NOT create a new namespace - update existing files in place and move them. Update all internal file imports to reflect the new namespace paths.

#### **3.2: Enum Directories → Plural "Enums"**

For ALL enum subdirectories within domains:

- Rename `Enum/` subdirectories to `Enums/` (plural)
- Update namespace from `\Enum\` to `\Enums\`
- Example: `Users/Enum/UserStatus.php` → `Users/Enums/UserStatus.php` with namespace `Webkernel\Base\Users\Enums`
- Update all imports and uses of these enums across the codebase

#### **3.3: Trait Subdirectories → "Concerns"**

For ALL trait subdirectories within domains:

- Rename trait/trait subdirectories to `Concerns/` (or rename `Security/` to `Concerns/` if it contains shared concerns)
- Update namespace from `\Traits\` or `\Security\` to `\Concerns\`
- Example: `Users/Traits/HasUserAuth.php` → `Users/Concerns/HasUserAuth.php`
- Update imports in all files using these traits

#### **3.4: System Framework Feature Migration**

Move `System/` into `Base/System/` with internal structure preserved:

- System/Access/ → Base/System/Access/
- System/Host/ → Base/System/Host/
- System/Ops/ → Base/System/Ops/
- System/Security/ → Base/System/Security/

Update all namespaces to reflect `Webkernel\Base\System\*`

#### **3.5: Generators Migration**

Move existing generators to `Base/Generators/`:

- Gradients → `Base/Generators/GradientImage/`
- UniqueId strategies → `Base/Generators/UniqueId/Strategy/`
- Registry → `Base/Generators/UniqueId/Registry/`

Update namespaces from `Webkernel\Generators\*` to `Webkernel\Base\Generators\*`

#### **3.6: Integration Migration → kebab-case Subdirectories**

**Critical: Convert subdirectories to kebab-case**

Move Integration features to `Base/Integration/` and convert subdirectories:

- `Integrations/Providers/` or old structure → `Base/Integration/` with kebab-case:
    - GitHub → `git-hosting/` (hosts: `github/`, `gitlab/`)
    - APIs → `api-providers/` (hosts: `rest/`, `graphql/`, `soap/`)
    - Webhooks → `webhook-handlers/`
    - MCP → `mcp/` (subfolders: `chrome/`, `filesystem/`, `kubernetes/`, etc.)

**Remove Global/Local organizational split** - use flat structure:

- Instead of `Api/Global/Rest/` and `Api/Local/InternalApi/`, use:
    - `Api/rest-providers/` (external REST APIs)
    - `Api/internal-api/` (internal/local APIs)

Update all namespaces: `Webkernel\Base\Integration\*`

#### **3.7: Connectors Migration → kebab-case Subdirectories**

Move to `Base/Connectors/` with kebab-case subdirectories:

**Convert existing connectors to kebab-case naming:**

- DatabaseConnector → `database/`
- ApiConnector → `api-provider/`
- FileConnector → `file-source/`
- Custom connectors → `custom-name/`

Remove Global/Local split - flatten to kebab-case subdirectories

Update all namespaces: `Webkernel\Base\Connectors\*`

#### **3.8: Communication Migration → PascalCase + Remove Global/Local Split**

Move to `Base/Communication/` with PascalCase top-level folders and flat subdirectories:

- `Chat/` (PascalCase)
    - Move existing chat integrations to: `Chat/discord/`, `Chat/slack/`, `Chat/telegram/`, `Chat/internal-chat/`
    - Remove Global/Local folders
- `Email/` (PascalCase)
    - Move existing email integrations to: `Email/postmark/`, `Email/smtp/`, `Email/local-smtp/`
    - Remove Global/Local folders

- `Push/` (PascalCase)
    - Move existing push integrations to: `Push/firebase/`, `Push/onesignal/`, `Push/local-push/`
    - Remove Global/Local folders

- `SMS/` (PascalCase)
    - Move existing SMS integrations to: `SMS/twilio/`, `SMS/vonage/`, `SMS/inwi/`, `SMS/maroc-telecom/`, `SMS/orange-maroc/`
    - Remove Global/Local folders

**Key point:** Keep top-level folder names in PascalCase (Chat, Email, Push, SMS, Social, Voice, InApp, WhatsApp), but move provider implementations to kebab-case subdirectories and remove the Global/Local intermediate folders.

Update all namespaces: `Webkernel\Base\Communication\*`

#### **3.9: Auth Migration**

Move authentication infrastructure to `Base/Auth/`:

- Providers/ → Base/Auth/Providers/
- Services/ → Base/Auth/Services/
- Contracts/ → Base/Auth/Contracts/
- Security/ → Base/Auth/Security/

Update namespaces: `Webkernel\Base\Auth\*`

#### **3.10: Arcanes Migration (Module Management + Scaffolding)**

**IMPORTANT: Arcanes keeps its name, but Modules merges into it**

Move to `Base/Arcanes/` with two subdirectories:

1. `Base/Arcanes/Modules/` - Module management (install, uninstall, discover, version, access)
2. `Base/Arcanes/Scaffolding/` - Code generation (commands, matrix, scaffold engine, stubs)

If there's an old `Modules/` directory at top-level or elsewhere, merge all its content into `Base/Arcanes/Modules/`
If there's an old `Scaffolding/` or `Generators/` for code scaffolding, merge into `Base/Arcanes/Scaffolding/`

Update namespaces:

- `Webkernel\Modules\*` → `Webkernel\Base\Arcanes\Modules\*`
- `Webkernel\Scaffolding\*` → `Webkernel\Base\Arcanes\Scaffolding\*`

#### **3.11: Builders Migration**

Move Builders to `Base/Builders/` (business logic only, no presentation):

**DBStudio backend:**

- Move all business logic to `Base/Builders/DBStudio/Backend/`
- Move API layer to `Base/Builders/DBStudio/Api/`
- Move Observers to `Base/Builders/DBStudio/Observers/`
- **Presentation stays in `CP/Builders/DBStudio/` (separate from backend)**

**Future builders:**

- Stub out directory structures for: WebsiteBuilder, WorkflowBuilder, MailBuilder, NoCodeAppBuilder, DocsEquivalent, WordEquivalent, ExcelEquivalent
- Each gets: Models/, Services/, Actions/, Events/, Exceptions/, Contracts/, + domain-specific folders (Components, Steps, Templates, Formulas, Export, Realtime)

Update namespaces: `Webkernel\Base\Builders\*`

### **Phase 4: Update Top-Level Entries (Stay at Root)**

These directories **remain at the root level** and require namespace verification only:

- `Traits/` - Generic mixins (namespace: `Webkernel\Traits`)
- `Plugins/` - Plugin system (namespace: `Webkernel\Plugins`)
- `Providers/` - Laravel service providers (namespace: `Webkernel\Providers`)
- `Http/` - HTTP layer (namespace: `Webkernel\Http`)
- `Facades/` - Public API (namespace: `Webkernel\Facades`)
- `Registries/` - Runtime documentation (rename from `Registry/`, namespace: `Webkernel\Registries`)
- `Console/` - Artisan commands (rename from `Commands/`, namespace: `Webkernel\Console`)
- `Async/` - Framework async infrastructure (namespace: `Webkernel\Async`)
- `Jobs/` - Queue jobs (namespace: `Webkernel\Jobs`)
- `Notifications/` - Notification drivers (namespace: `Webkernel\Notifications`)
- `CP/` - Control panel presentation (namespace: `Webkernel\CP`)

**Action:** Verify their namespaces do NOT incorrectly import from old scattered locations. For `Registry/` → `Registries/` and `Commands/` → `Console/`, update the directory name and all internal namespace declarations.

### **Phase 5: Update Presentation Layer**

#### **5.1: Panels/ directory**

Create `Panels/` if it doesn't exist with standard structure:

- `Panels/SystemPanel/`
- `Panels/AdminPanel/`
- `Panels/ModulePanel/`
- `Panels/BusinessPanel/`
- Etc.

Each panel should have:

- `Resources/` (Filament resources)
- `Pages/` (Filament pages)
- `Widgets/` (Filament widgets)
- `views/` (Blade components)
- `routes.php`
- `config.php`

Update all imports to reference domain logic from `Webkernel\Base\*` instead of old locations.

#### **5.2: CP/Builders/ directory**

Create `CP/Builders/` structure for builder-specific presentation:

- `CP/Builders/DBStudio/` - Contains ONLY presentation (Resources, Pages, Widgets, Views, Livewire)
- `CP/Builders/WebsiteBuilder/`, etc. for future builders

**Do NOT move backend logic here** - backend stays in `Base/Builders/`

### **Phase 6: Global Import Replacement**

**Search and replace all imports across the entire codebase:**

| Old Namespace Pattern                                 | New Namespace Pattern                  |
| ----------------------------------------------------- | -------------------------------------- |
| `use Webkernel\Users\*`                               | `use Webkernel\Base\Users\*`           |
| `use Webkernel\Businesses\*`                          | `use Webkernel\Base\Businesses\*`      |
| `use Webkernel\Databases\*`                           | `use Webkernel\Base\Databases\*`       |
| `use Webkernel\Domains\*`                             | `use Webkernel\Base\Domains\*`         |
| `use Webkernel\Audit\*`                               | `use Webkernel\Base\Audit\*`           |
| `use Webkernel\System\*`                              | `use Webkernel\Base\System\*`          |
| `use Webkernel\Generators\*`                          | `use Webkernel\Base\Generators\*`      |
| `use Webkernel\Integration\*`                         | `use Webkernel\Base\Integration\*`     |
| `use Webkernel\Connectors\*`                          | `use Webkernel\Base\Connectors\*`      |
| `use Webkernel\Communication\*`                       | `use Webkernel\Base\Communication\*`   |
| `use Webkernel\Auth\*`                                | `use Webkernel\Base\Auth\*`            |
| `use Webkernel\Arcanes\*`                             | `use Webkernel\Base\Arcanes\*`         |
| `use Webkernel\Builders\*`                            | `use Webkernel\Base\Builders\*`        |
| `use Webkernel\Modules\*`                             | `use Webkernel\Base\Arcanes\Modules\*` |
| `use Webkernel\Traits\*` → `use Webkernel\Concerns\*` | (Keep at root)                         |

Also update:

- Facade paths in `bootstrap/webkernel/src/Facades/`
- Service provider registrations in `bootstrap/webkernel/src/Providers/`
- Http route imports
- Console command registrations
- Any direct class references in strings (blade files, config, env checks)

### **Phase 7: Update Configuration and Bootstrapping**

1. **Update `WebApp.php`** - Ensure domain providers reference new paths
2. **Update `ServiceProvider.php`** - Register all domain providers from Base/
3. **Update `bootstrap/webkernel/src/Facades/*.php`** - Point to correct namespaces
4. **Update `bootstrap/webkernel/src/Registries/*.php`** - Scan from new Base/ locations
5. **Update `bootstrap/webkernel/src/Providers/*.php`** - Point to new namespaces
6. **Update composer.json autoload** - Ensure PSR-4 mapping includes:
    ```json
    "Webkernel\\Base\\" : "bootstrap/webkernel/src/Base/"
    ```

### **Phase 8: Database and Migrations**

1. Move all domain-specific migrations from `database/migrations/` into their respective domain locations (e.g., `Base/Builders/DBStudio/Backend/Database/migrations/`)
2. Update migration references in ServiceProviders
3. Ensure Eloquent models reference migrations from new paths

### **Phase 9: Testing and Verification**

1. **Run composer dump-autoload** to regenerate autoloader
2. **Test artisan command discovery** - Run `php artisan list`
3. **Verify no fatal PHP errors** - Load the app bootstrap
4. **Check all facades work** - Test: `Users::create()`, `Databases::verify()`, etc.
5. **Verify no broken imports** in console commands, HTTP routes, and providers
6. **Run existing test suite** if present
7. **Check Filament panels** - Verify all panel resources load correctly
8. **Validate webpack/vite builds** - Ensure asset compilation works

### **Phase 10: Cleanup**

1. **Delete old scattered directories** - Remove any top-level directories that have been migrated:
    - Old Traits folders (if domain-specific ones moved to Concerns/)
    - Old Modules/ (merged into Base/Arcanes/Modules/)
    - Old Generators/ (if only ID/image generation, moved to Base/Generators/)
    - Old Integration/ at root (moved to Base/)
    - Old Connectors/ at root (moved to Base/)
    - Old Communication/ at root (moved to Base/)
    - Old Auth/ at root (moved to Base/)
    - Old Arcanes/ at root (moved to Base/)
    - Old Builders/ at root (moved to Base/)

### **Phase 11: FINAL VERIFICATION & ATOMIC COMMIT (Only After All Tests Pass)**

⚠️ **DO NOT COMMIT until this entire phase is complete and all checks pass.**

1. **Run final verification:**
    - `php artisan list` - no errors
    - `composer dump-autoload` - no errors
    - `vendor/bin/phpstan` - no errors
    - Full test suite passes (if applicable)
    - Manual spot-check of 10+ randomly selected files - no broken imports

2. **ONLY AFTER verification passes, create single atomic commit:**

    ```bash
    git add -A
    git commit -m "Refactor: Restructure webkernel architecture into Base namespace

    - Move all domains into Base/ (Users, Businesses, Databases, Domains, Audit)
    - Move all framework features into Base/ (System, Generators, Integration, Connectors, Communication, Auth, Arcanes, Builders, Services, Payment, Query, QuickTouch)
    - Merge Modules into Base/Arcanes/Modules/
    - Rename Registry/ → Registries/, Commands/ → Console/
    - Rename Enum/ → Enums/ in all domains
    - Rename domain trait dirs → Concerns/
    - Convert Integration/Connectors to kebab-case providers
    - Flatten Communication/Integration Global/Local hierarchy
    - Move Mcp to Base/Integration/Mcp/
    - Move presentation (Pages, UI) to CP/
    - Update all namespaces: Webkernel\Domain → Webkernel\Base\Domain
    - Update all imports across codebase (466 files)
    - PSR-4 mapping updated in composer.json"
    ```

3. **Push to remote only after commit is verified locally:**
    ```bash
    git push origin main
    ```

---

### **Key Refinements Applied**

✅ **Traits renamed to Concerns** - Domain trait subdirectories use "Concerns" naming for clarity  
✅ **kebab-case for providers/adapters** - Integration and Connectors use kebab-case subdirectories (git-hosting, api-providers, etc.)  
✅ **Global/Local removed** - Communication, Integration, and Connectors use flat kebab-case structure  
✅ **PascalCase for top-level Communication folders** - Chat/, Email/, Push/, SMS/, Social/, Voice/, InApp/, WhatsApp/  
✅ **Arcanes kept, Modules merged** - Arcanes/Modules/ and Arcanes/Scaffolding/ together in Base/Arcanes/  
✅ **Builders split presentation** - Backend logic in Base/Builders/, presentation in CP/Builders/  
✅ **No naming conflicts** - No System/System, View/View, etc.

### **Verification Checklist**

- [ ] All directories created per Phase 2
- [ ] All domain files migrated with updated namespaces
- [ ] All trait subdirectories renamed to Concerns
- [ ] All Integration subdirectories converted to kebab-case
- [ ] All Connectors subdirectories converted to kebab-case
- [ ] All Communication subdirectories cleaned (no Global/Local folders)
- [ ] All imports globally updated per Phase 6
- [ ] composer.json PSR-4 mapping updated
- [ ] WebApp.php and ServiceProvider.php updated
- [ ] All facades updated
- [ ] All registries updated
- [ ] composer dump-autoload runs without errors
- [ ] php artisan list executes successfully
- [ ] No fatal PHP errors on bootstrap
- [ ] All domain facades work (Users::, Databases::, etc.)
- [ ] Filament panels load correctly
- [ ] Asset pipeline works
- [ ] Old directories deleted
