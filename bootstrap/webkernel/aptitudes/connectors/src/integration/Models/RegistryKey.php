<?php declare(strict_types=1);

namespace Webkernel\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class RegistryKey extends Model
{
    protected $table = 'inst_modules_src_keys';
    protected $connection = 'webkernel_sqlite';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'registry',
        'vendor',
        'token_encrypted',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
