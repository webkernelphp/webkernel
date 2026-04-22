<?php declare(strict_types=1);

namespace Webkernel\Integration\Models;

use Illuminate\Database\Eloquent\Model;

class RegistryAccount extends Model
{
    protected $table = 'inst_registry_accounts';
    protected $connection = 'webkernel_sqlite';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'registry',
        'account_name',
        'account_email',
        'account_type',
        'token_encrypted',
        'metadata_encrypted',
        'verified',
        'active',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'active' => 'boolean',
    ];
}
