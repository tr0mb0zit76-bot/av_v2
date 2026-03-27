<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'columns_config',
    ];

    protected $casts = [
        'permissions' => 'json',
        'columns_config' => 'json',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    // Получить конфигурацию колонок для конкретного модуля
    public function getColumnsConfig($module = 'orders')
    {
        if (!$this->columns_config) {
            return null;
        }
        
        return $this->columns_config[$module] ?? null;
    }
    
    // Установить конфигурацию колонок для модуля
    public function setColumnsConfig($module, $config)
    {
        $configArray = $this->columns_config ?? [];
        $configArray[$module] = $config;
        $this->columns_config = $configArray;
        $this->save();
    }
}