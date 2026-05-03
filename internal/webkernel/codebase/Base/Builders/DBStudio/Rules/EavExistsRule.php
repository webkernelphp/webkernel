<?php

namespace Webkernel\Base\Builders\DBStudio\Rules;

use Closure;
use Webkernel\Base\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Base\Builders\DBStudio\Models\StudioRecord;
use Illuminate\Contracts\Validation\ValidationRule;

class EavExistsRule implements ValidationRule
{
    public function __construct(
        protected StudioCollection $collection,
        protected ?int $tenantId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = StudioRecord::query()
            ->where('collection_id', $this->collection->id)
            ->where('uuid', $value)
            ->whereNull('deleted_at')
            ->when($this->tenantId !== null, fn ($q) => $q
                ->where('tenant_id', $this->tenantId)
            )
            ->when($this->tenantId === null, fn ($q) => $q
                ->whereNull('tenant_id')
            )
            ->exists();

        if (! $exists) {
            $fail("The selected {$attribute} does not exist.");
        }
    }
}
