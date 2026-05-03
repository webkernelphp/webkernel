<?php declare(strict_types=1);

namespace Webkernel\CP\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebkernelSettingHistory extends Model
{
    protected $table = 'instance_settings_history';
    protected $connection = 'webkernel_sqlite';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'setting_id',
        'category',
        'key',
        'old_value',
        'new_value',
        'changed_by',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(WebkernelSetting::class, 'setting_id');
    }
}
