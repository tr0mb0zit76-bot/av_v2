<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'site_id',
        'role_id',
        'theme',
        'is_active',
        'ai_preferences',
        'ai_learning_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'ai_learning_enabled' => 'boolean',
        'ai_preferences' => 'json',
    ];

    // Связь с ролью
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Проверки ролей
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSupervisor()
    {
        return $this->hasRole('supervisor');
    }
	
    public function isManager()
    {
        return $this->hasRole('manager');
    }

    public function isDispatcher()
    {
        return $this->hasRole('dispatcher');
    }

    public function isAccountant()
    {
        return $this->hasRole('accountant');
    }

    // Получение имени роли
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->name : 'viewer';
    }

    public function getRoleDisplayNameAttribute()
    {
        if (!$this->role) return 'Просмотр';
        
        $names = [
            'admin' => 'Администратор',
            'supervisor' => 'Руководитель',
            'manager' => 'Менеджер',
            'dispatcher' => 'Диспетчер',
            'accountant' => 'Бухгалтер',
            'viewer' => 'Только просмотр'
        ];
        
        return $names[$this->role->name] ?? $this->role->display_name ?? 'Пользователь';
    }
}