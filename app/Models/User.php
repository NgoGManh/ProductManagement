<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Hashidable;
use App\Models\CoreModel;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

/**
 * User model — secure, auditable, and JWT-ready
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles, LogsActivity, Hashidable;

    protected $guard_name = "api";

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "mobile",
        "password",
        "status",
        "device_id",
        "avatar",
        "email_verified_at",
        "created_by",
        "updated_by",
    ];

    protected $hidden = ["password", "remember_token"];

    protected $attributes = [
        "status" => "ACTIVE",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
    ];

    /* -------- Accessors -------- */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? "") . " " . ($this->last_name ?? "")) ?: "Unknown User";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(($this->first_name[0] ?? "") . ($this->last_name[0] ?? ""));
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && Storage::disk("public")->exists($this->avatar)) {
            return Storage::url($this->avatar);
        }

        return "https://ui-avatars.com/api/?name=" . urlencode($this->full_name) . "&background=random";
    }

    protected $appends = ["full_name", "initials", "avatar_url"];

    /* -------- Relationships -------- */
    public function creator()
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function updator()
    {
        return $this->belongsTo(User::class, "updated_by");
    }

    /* -------- JWT -------- */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /* -------- Logging -------- */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["first_name", "last_name", "email", "mobile", "status", "avatar"])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
