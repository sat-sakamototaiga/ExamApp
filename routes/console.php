<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create-user
    {name : 管理者ユーザー名}
    {email : 管理者メールアドレス}
    {--password= : 管理者パスワード（未指定時は対話入力）}
    {--promote : 既存ユーザーを管理者に昇格する}
    {--verified : メール確認済みとして作成/更新する}', function () {
    $name = trim((string) $this->argument('name'));
    $email = strtolower(trim((string) $this->argument('email')));

    if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
        $this->error('users.role カラムが見つかりません。先にマイグレーションを実行してください。');
        $this->line('例: php artisan migrate');

        return self::FAILURE;
    }

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
    ], [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $existingUser = User::where('email', $email)->first();
    $shouldMarkVerified = (bool) $this->option('verified');

    if ($existingUser) {
        if (! $this->option('promote')) {
            if ($existingUser->isAdmin()) {
                $this->info('指定メールのユーザーは既に管理者です。');
                return self::SUCCESS;
            }

            $this->warn('指定メールのユーザーは既に存在します。管理者へ昇格するには --promote を指定してください。');
            return self::FAILURE;
        }

        $existingUser->name = $name;
        $existingUser->role = User::ROLE_ADMIN;
        if ($shouldMarkVerified && $existingUser->email_verified_at === null) {
            $existingUser->email_verified_at = now();
        }
        $existingUser->save();

        $this->info("既存ユーザーを管理者に昇格しました: {$existingUser->email}");
        return self::SUCCESS;
    }

    $password = (string) $this->option('password');
    if ($password === '') {
        $password = (string) $this->secret('初期パスワードを入力してください');
        $passwordConfirmation = (string) $this->secret('確認のため、もう一度入力してください');

        if ($password !== $passwordConfirmation) {
            $this->error('パスワードが一致しません。');
            return self::FAILURE;
        }
    }

    $passwordValidator = Validator::make([
        'password' => $password,
    ], [
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($passwordValidator->fails()) {
        foreach ($passwordValidator->errors()->all() as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'role' => User::ROLE_ADMIN,
        'email_verified_at' => $shouldMarkVerified ? now() : null,
    ]);

    $this->info("管理者ユーザーを作成しました: {$user->email}");
    return self::SUCCESS;
})->purpose('Create or promote an administrator user safely');
