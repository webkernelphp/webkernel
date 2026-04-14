<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace Webkernel\Users\Models{
/**
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string|null $avatar_url
 * @property-read UserPrivilege|null $privilegeRelation
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $app_authentication_secret
 * @property string|null $app_authentication_recovery_codes
 * @property int $has_email_authentication
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppAuthenticationRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppAuthenticationSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasEmailAuthentication($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace Webkernel\Users\Models{
/**
 * @property int               $id
 * @property int               $user_id
 * @property UserPrivilegeLevel $privilege
 * @property-read User          $user
 * @method static Builder|static forPrivilege(UserPrivilegeLevel|string $privilege)
 * @method static Builder|static appOwners()
 * @method static Builder|static superUsers()
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder<static>|UserPrivilege newModelQuery()
 * @method static Builder<static>|UserPrivilege newQuery()
 * @method static Builder<static>|UserPrivilege query()
 * @method static Builder<static>|UserPrivilege whereCreatedAt($value)
 * @method static Builder<static>|UserPrivilege whereId($value)
 * @method static Builder<static>|UserPrivilege wherePrivilege($value)
 * @method static Builder<static>|UserPrivilege whereUpdatedAt($value)
 * @method static Builder<static>|UserPrivilege whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserPrivilege {}
}

