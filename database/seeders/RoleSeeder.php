<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Администратор',
                'slug' => 'admin',
                'description' => 'Полный доступ ко всем функциям системы',
            ],
            [
                'name' => 'Менеджер',
                'slug' => 'manager',
                'description' => 'Доступ к управлению контентом',
            ],
            [
                'name' => 'Пользователь',
                'slug' => 'user',
                'description' => 'Базовый доступ',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
