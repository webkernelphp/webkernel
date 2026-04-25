<?php

namespace Webkernel\Base\Builders\DBStudio\Observers;

use Webkernel\Base\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Base\Builders\DBStudio\Support\PermissionRegistrar;

class StudioCollectionObserver
{
    public function created(StudioCollection $collection): void
    {
        PermissionRegistrar::syncForCollection($collection);
    }

    public function updated(StudioCollection $collection): void
    {
        if ($collection->wasChanged('slug')) {
            $oldSlug = $collection->getOriginal('slug');
            $oldCollection = new StudioCollection(['slug' => $oldSlug]);
            PermissionRegistrar::removeForCollection($oldCollection);
        }

        PermissionRegistrar::syncForCollection($collection);
    }

    public function deleted(StudioCollection $collection): void
    {
        PermissionRegistrar::removeForCollection($collection);
    }
}
