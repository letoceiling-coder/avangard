<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create 
                            {--email= : Email пользователя}
                            {--password= : Пароль пользователя}
                            {--name= : Имя пользователя}
                            {--roles=* : Роли пользователя (slug через запятую)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать нового пользователя';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Если параметры не указаны, используем значения по умолчанию
        $email = $this->option('email') ?: 'dsc-23@yandex.ru';
        $password = $this->option('password') ?: '123123123';
        $name = $this->option('name') ?: 'Джон Уик';
        $rolesInput = $this->option('roles');

        // Если роли не указаны, присваиваем все роли (админ)
        if (empty($rolesInput)) {
            $roles = Role::all();
        } else {
            $roleSlugs = is_array($rolesInput) ? $rolesInput : explode(',', $rolesInput[0] ?? '');
            $roles = Role::whereIn('slug', $roleSlugs)->get();
        }

        // Проверяем, существует ли пользователь
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->warn("Пользователь с email {$email} уже существует.");
            if (!$this->confirm('Обновить пользователя?')) {
                return 0;
            }
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->save();
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        // Присваиваем роли
        $user->roles()->sync($roles->pluck('id'));

        $this->info("Пользователь успешно создан/обновлен:");
        $this->line("Email: {$user->email}");
        $this->line("Имя: {$user->name}");
        $this->line("Роли: " . $roles->pluck('name')->implode(', '));

        return 0;
    }
}
