<?php declare(strict_types=1);
namespace Webkernel\Console\Commands;

use Webkernel\Console\Commands\Concerns\CanGeneratePanels;
use Webkernel\Console\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Exceptions\FailureCommandOutput;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:webkernel-panel', aliases: [
    'webkernel:make-panel',
    'webkernel:panel',
])]
class Panel extends Command
{
    use CanGeneratePanels;
    use CanManipulateFiles;

    protected $description = 'Create a new Webkernel panel';

    protected $name = 'make:webkernel-panel';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'filament:make-panel',
        'filament:panel',
    ];

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'id',
                mode: InputArgument::OPTIONAL,
                description: 'The ID of the panel',
            ),
        ];
    }

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'force',
                shortcut: 'F',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite the contents of the files if they already exist',
            ),
        ];
    }

    public function handle(): int
    {
        try {
            $this->generatePanel(
                id: $this->argument('id'),
                placeholderId: 'app',
                isForced: $this->option('force'),
            );
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}
