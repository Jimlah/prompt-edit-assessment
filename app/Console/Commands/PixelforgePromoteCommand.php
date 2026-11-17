<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class PixelforgePromoteCommand extends Command
{
    protected $signature = 'pixelforge:promote {email : The email address of the user to make an admin}';

    protected $description = 'Grant admin access to a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with the email \"{$email}\".");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("{$user->name} <{$user->email}> is now an admin.");

        return self::SUCCESS;
    }
}
