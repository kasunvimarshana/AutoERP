<?php

namespace YourVendor\LaravelDDDArchitect\Commands;

use Illuminate\Support\Str;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Generators\UseCaseGenerator;
use YourVendor\LaravelDDDArchitect\Generators\RepositoryGenerator;
use YourVendor\LaravelDDDArchitect\Generators\DomainServiceGenerator;
use YourVendor\LaravelDDDArchitect\Generators\DomainEventGenerator;
use YourVendor\LaravelDDDArchitect\Generators\CommandHandlerGenerator;
use YourVendor\LaravelDDDArchitect\Generators\QueryHandlerGenerator;
use YourVendor\LaravelDDDArchitect\Generators\DTOGenerator;
use YourVendor\LaravelDDDArchitect\Generators\AggregateGenerator;
use YourVendor\LaravelDDDArchitect\Generators\SpecificationGenerator;

// ============================================================================
// MakeUseCaseCommand
// ============================================================================

class MakeUseCaseCommand extends BaseCommand
{
    protected $signature = 'ddd:make-use-case
        {context : Bounded context name}
        {name    : Use case name (e.g. CreateOrder, CancelOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create an Application Use Case inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new UseCaseGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            useCaseName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("UseCase <options=bold>[{$name}UseCase]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeRepositoryCommand
// ============================================================================

class MakeRepositoryCommand extends BaseCommand
{
    protected $signature = 'ddd:make-repository
        {context : Bounded context name}
        {name    : Entity/Aggregate name the repository manages}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Repository interface + Eloquent implementation';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new RepositoryGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            entityName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Repository interface + Eloquent implementation created for <options=bold>[{$name}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeDomainServiceCommand
// ============================================================================

class MakeDomainServiceCommand extends BaseCommand
{
    protected $signature = 'ddd:make-domain-service
        {context : Bounded context name}
        {name    : Domain service class name}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Service inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DomainServiceGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            serviceName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Domain Service <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeDomainEventCommand
// ============================================================================

class MakeDomainEventCommand extends BaseCommand
{
    protected $signature = 'ddd:make-domain-event
        {context : Bounded context name}
        {name    : Domain event class name (e.g. OrderWasCreated)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Event inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DomainEventGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            eventName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Domain Event <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeCommandHandlerCommand
// ============================================================================

class MakeCommandHandlerCommand extends BaseCommand
{
    protected $signature = 'ddd:make-command-handler
        {context : Bounded context name}
        {name    : Command name without "Command" suffix (e.g. CreateOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a CQRS Command + Handler pair inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new CommandHandlerGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            commandName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Command + Handler <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeQueryHandlerCommand
// ============================================================================

class MakeQueryHandlerCommand extends BaseCommand
{
    protected $signature = 'ddd:make-query-handler
        {context : Bounded context name}
        {name    : Query name without "Query" suffix (e.g. GetOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a CQRS Query + Handler pair inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new QueryHandlerGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            queryName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Query + Handler <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeDTOCommand
// ============================================================================

class MakeDTOCommand extends BaseCommand
{
    protected $signature = 'ddd:make-dto
        {context : Bounded context name}
        {name    : DTO name without "DTO" suffix (e.g. CreateOrder)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Data Transfer Object inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new DTOGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            dtoName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("DTO <options=bold>[{$name}DTO]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeAggregateCommand
// ============================================================================

class MakeAggregateCommand extends BaseCommand
{
    protected $signature = 'ddd:make-aggregate
        {context : Bounded context name}
        {name    : Aggregate root class name}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Aggregate Root inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new AggregateGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            aggregateName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Aggregate Root <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// MakeSpecificationCommand
// ============================================================================

class MakeSpecificationCommand extends BaseCommand
{
    protected $signature = 'ddd:make-specification
        {context : Bounded context name}
        {name    : Specification class name (e.g. OrderIsActive)}
        {--force : Overwrite if exists}';

    protected $description = 'Create a Domain Specification (business rule) inside a bounded context';

    public function handle(): int
    {
        $context = Str::studly($this->argument('context'));
        $name    = Str::studly($this->argument('name'));

        $generator = new SpecificationGenerator(
            config: $this->config(),
            renderer: $this->renderer(),
            files: new FileGenerator(),
            contextName: $context,
            specName: $name,
        );

        if ($this->option('force')) {
            $generator->force();
        }

        $created = $generator->generate();
        $this->reportCreated($created);

        if (! empty($created)) {
            $this->components->success("Specification <options=bold>[{$name}]</> created in <options=bold>[{$context}]</>.");
        }

        return self::SUCCESS;
    }
}

// ============================================================================
// ListContextsCommand
// ============================================================================

class ListContextsCommand extends BaseCommand
{
    protected $signature = 'ddd:list';
    protected $description = 'List all discovered DDD bounded contexts in this application';

    public function handle(): int
    {
        $contexts = $this->registrar()->all();

        if (empty($contexts)) {
            $this->components->warn('No bounded contexts found. Run <options=bold>php artisan ddd:make-context {name}</> to create one.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  <options=bold>Discovered Bounded Contexts</>');
        $this->newLine();

        $rows = array_map(function (string $context) {
            return [
                $context,
                $this->registrar()->namespace($context),
                $this->registrar()->path($context),
            ];
        }, $contexts);

        $this->table(['Context', 'Namespace', 'Path'], $rows);

        $this->newLine();
        $this->line("  Total: <options=bold>" . count($contexts) . "</> context(s)");
        $this->newLine();

        return self::SUCCESS;
    }
}

// ============================================================================
// PublishStubsCommand
// ============================================================================

class PublishStubsCommand extends BaseCommand
{
    protected $signature = 'ddd:publish-stubs
        {--force : Overwrite existing published stubs}';

    protected $description = 'Publish DDD stub templates to resources/stubs/ddd for customisation';

    public function handle(): int
    {
        $this->callSilent('vendor:publish', [
            '--tag'   => 'ddd-architect-stubs',
            '--force' => $this->option('force'),
        ]);

        $this->components->success('DDD stubs published to <options=bold>resources/stubs/ddd/</>.');
        $this->line('  Edit any stub — the package will automatically use your custom versions.');

        return self::SUCCESS;
    }
}

// ============================================================================
// DDDInfoCommand
// ============================================================================

class DDDInfoCommand extends BaseCommand
{
    protected $signature = 'ddd:info';
    protected $description = 'Display current DDD Architect configuration and available commands';

    public function handle(): int
    {
        $config = $this->config();

        $this->newLine();
        $this->line('  <options=bold>Laravel DDD Architect — Configuration</>');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Mode',          $config['mode']      ?? 'full'],
            ['Base Path',     $config['base_path'] ?? 'app'],
            ['Namespace',     $config['namespace'] ?? 'App'],
            ['Auto Discover', ($config['auto_discover'] ?? true) ? 'Yes' : 'No'],
            ['Shared Kernel', ($config['shared_kernel']  ?? true) ? 'Yes' : 'No'],
            ['Generate .gitkeep', ($config['generate_gitkeep'] ?? true) ? 'Yes' : 'No'],
        ]);

        $this->newLine();
        $this->line('  <options=bold>Available Commands</>');
        $this->newLine();

        $commands = [
            ['ddd:make-context {name}',          'Scaffold a complete bounded context'],
            ['ddd:make-entity {ctx} {name}',      'Create a Domain Entity'],
            ['ddd:make-value-object {ctx} {name}','Create a Value Object'],
            ['ddd:make-aggregate {ctx} {name}',   'Create an Aggregate Root'],
            ['ddd:make-use-case {ctx} {name}',    'Create an Application Use Case'],
            ['ddd:make-repository {ctx} {name}',  'Create Repository interface + implementation'],
            ['ddd:make-domain-service {ctx} {nm}','Create a Domain Service'],
            ['ddd:make-domain-event {ctx} {name}','Create a Domain Event'],
            ['ddd:make-command-handler {ctx} {n}','Create CQRS Command + Handler pair'],
            ['ddd:make-query-handler {ctx} {n}',  'Create CQRS Query + Handler pair'],
            ['ddd:make-dto {ctx} {name}',         'Create a DTO'],
            ['ddd:make-specification {ctx} {n}',  'Create a Specification'],
            ['ddd:list',                           'List all bounded contexts'],
            ['ddd:publish-stubs',                  'Publish stub templates for customisation'],
            ['ddd:info',                           'Show this information screen'],
        ];

        $this->table(['Command', 'Description'], $commands);
        $this->newLine();

        return self::SUCCESS;
    }
}
