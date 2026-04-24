<?php declare(strict_types=1);

namespace Webkernel\CP\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebkernelSettingCategory extends Model
{
    protected $table = 'inst_webkernel_setting_categories';
    protected $connection = 'webkernel_sqlite';

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'label',
        'description',
        'icon',
        'sort_order',
        'is_system',
        'meta_json',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'meta_json' => 'array',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(WebkernelSetting::class, 'category', 'key');
    }
}
