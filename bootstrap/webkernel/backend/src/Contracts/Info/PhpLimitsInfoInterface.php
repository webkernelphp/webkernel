<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * Active PHP ini runtime limits.
 *
 * @api
 */
interface PhpLimitsInfoInterface
{
    /**
     * max_execution_time in seconds.
     * Returns 0 when set to unlimited.
     */
    public function maxExecutionTime(): int;

    /** upload_max_filesize in bytes. */
    public function uploadMaxFilesize(): int;

    /** post_max_size in bytes. */
    public function postMaxSize(): int;

    /** max_input_vars as integer. */
    public function maxInputVars(): int;

    /** Human-readable max_execution_time. Returns "∞" when 0. */
    public function humanMaxExecutionTime(): string;

    /** Human-readable upload_max_filesize, e.g. "32 MB". */
    public function humanUploadMaxFilesize(): string;

    /** Human-readable post_max_size, e.g. "32 MB". */
    public function humanPostMaxSize(): string;
}
