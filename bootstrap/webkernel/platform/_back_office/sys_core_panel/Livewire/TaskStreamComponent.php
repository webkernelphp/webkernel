<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Livewire;

use Livewire\Component;
use Livewire\Attributes\Modelable;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;

class TaskStreamComponent extends Component
{
    #[Modelable]
    public WebkernelBackgroundTask $task;

    public function render()
    {
        $this->task->refresh();

        return view('webkernel-system::livewire.task-stream', [
            'task' => $this->task,
            'isRunning' => $this->task->status === 'running' || $this->task->status === 'pending',
        ]);
    }
}
