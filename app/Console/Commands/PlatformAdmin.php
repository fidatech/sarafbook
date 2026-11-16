<?php

namespace App\Console\Commands;

use App\Models\Central\PlatformUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PlatformAdmin extends Command
{
    protected $signature = 'platform:admin {email} {--name=Administrator} {--password= : Left empty, a password is made and shown once}';

    protected $description = 'Create a platform administrator, or set a new password for one';

    public function handle(): int
    {
        $password = $this->option('password') ?: Str::password(14, symbols: false);
        $user = PlatformUser::query()->updateOrCreate(
            ['email' => strtolower($this->argument('email'))],
            ['name' => $this->option('name'), 'password' => $password, 'is_active' => true],
        );

        $this->info(($user->wasRecentlyCreated ? 'Created ' : 'Updated ').$user->email);
        if (! $this->option('password')) {
            $this->line('Password: '.$password);
        }

        return self::SUCCESS;
    }
}
