<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Role Helpers ────────────────────────────────────────────────

    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) $roles = [$roles];
        return in_array($this->role, $roles);
    }

    public function isSuperAdmin(): bool    { return $this->role === 'super_admin'; }
    public function isAdminProgram(): bool  { return $this->role === 'admin_program'; }
    public function isAdminDapur(): bool    { return $this->role === 'admin_dapur'; }
    public function isViewer(): bool        { return $this->role === 'viewer'; }

    /**
     * Apakah user bisa input transaksi (masuk/keluar)
     */
    public function canTransact(): bool
    {
        return $this->hasRole(['super_admin', 'admin_program', 'admin_dapur']);
    }

    /**
     * Apakah user bisa CRUD item master
     */
    public function canManageItems(): bool
    {
        return $this->hasRole(['super_admin', 'admin_program']);
    }

    /**
     * Apakah user bisa export & lihat audit log
     */
    public function canExport(): bool
    {
        // Semua role (termasuk viewer) diizinkan untuk mengunduh laporan
        return true;
    }

    public function getRoleLabelAttribute(): string
    {
        return config('mbg.roles')[$this->role] ?? $this->role;
    }

    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'super_admin'   => 'danger',
            'admin_program' => 'primary',
            'admin_dapur'   => 'success',
            'viewer'        => 'secondary',
            default         => 'secondary',
        };
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function notifications_mbg(): HasMany
    {
        return $this->hasMany(NotificationMbg::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications_mbg()->whereNull('read_at');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'created_by');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'created_by');
    }
}
