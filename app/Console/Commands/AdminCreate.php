<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InboxBucketResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('admin:create')]
#[Description('Create the admin user account')]
class AdminCreate extends Command
{
    public function __construct(private readonly InboxBucketResolver $inboxBucketResolver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('E-Mail');
        $password = $this->secret('Password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->inboxBucketResolver->resolve();

        $this->info("Admin account created for {$user->email}.");

        return self::SUCCESS;
    }
}
