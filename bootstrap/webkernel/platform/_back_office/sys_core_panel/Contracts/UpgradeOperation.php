<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Contracts;

/**
 * Contract for upgrade/process operations to define their progress steps.
 * Enables any module or core feature to use the ProcessUpgrade page with custom steps.
 *
 * The primary logo is always Webkernel's favicon - immutable.
 * Only the secondary logo (module/operation branding) can be customized.
 */
interface UpgradeOperation
{
    /**
     * Get the operation title displayed at the top.
     * Example: "Webkernel Update", "Module Installation", "Database Migration"
     */
    public function getUpgradeTitle(): string;

    /**
     * Get the secondary logo URL (shown to the right of Webkernel logo).
     * Use this to brand your module or operation. Return null for Webkernel-only operations.
     * The primary Webkernel favicon is always shown and cannot be overridden.
     */
    public function getUpgradeSecondaryLogo(): ?string;

    /**
     * Define the steps for this operation.
     * Each step maps to progress percentage milestones.
     *
     * @return array<string, array{label: string, progressPercent: int}>
     *   Example: [
     *     'backup' => ['label' => 'Creating backup', 'progressPercent' => 15],
     *     'download' => ['label' => 'Downloading files', 'progressPercent' => 35],
     *   ]
     */
    public function getUpgradeSteps(): array;
}
