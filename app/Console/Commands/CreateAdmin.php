<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'commerce:create-admin {email} {--name=}';

    protected $description = 'Create a verified administrator with an interactively entered password';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $name = $this->option('name') ?: $this->ask('Administrator name');
        $password = $this->secret('Password (at least 12 characters, letters and numbers)');
        $confirmation = $this->secret('Confirm password');
        $v = Validator::make(['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation], ['email' => 'required|email|unique:users', 'name' => 'required|string|max:100', 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $u = new User(['email' => $email, 'name' => $name, 'password' => $password]);
        $u->forceFill(['role' => 'super_admin', 'email_verified_at' => now()])->save();
        $this->info('Administrator created. No password was logged.');

        return self::SUCCESS;
    }
}
