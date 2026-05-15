<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class UserModel
{
    private string $table = 'user';

    public function findAll(): array
    {
        return R::findAll($this->table, 'ORDER BY id ASC');
    }

    public function findById(int|string $id): mixed
    {
        return R::load('user', (int)$id);
    }

    public function findByEmail(string $email): mixed
    {
        return R::findOne($this->table, 'email = ?', [$email]);
    }

    public function findByUsername(string $username): mixed
    {
        return R::findOne($this->table, 'username = ?', [$username]);
    }

    public function create(string $username, string $email, string $password, string $bio = '', string $membershipStatus = 'free'): int 
    {
        $user = R::dispense($this->table);

        $user->username = $username;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->bio = $bio;
        $user->created_at = date('Y-m-d H:i:s');
        $user->daily_download_count = 0;
        $user->membership_status = $membershipStatus;

        return R::store($user);
    }

    public function save(mixed $user): int
    {
        return (int) R::store($user);
    }

    public function delete(int $id): void
    {
        $user = R::load($this->table, $id);

        if ($user->id) {
            R::trash($user);
        }
    }

    public function updateBio(int $id, string $bio): void
    {
        $user = R::load($this->table, $id);

        if ($user->id) {
            $user->bio = $bio;
            R::store($user);
        }
    }

    public function isUsernameTaken(string $username, int $currentUserId): bool
    {
        $user = R::findOne('user', 'username = ? AND id != ?', [$username, $currentUserId]);
        return $user !== null;
    }

    public function updateProfile(mixed $user): void
    {
        R::store($user);
    }

    public function isAdmin(int $userId): bool
    {
        $user = R::findOne('user', 'id = ? AND username = ?', [$userId, 'admin']);

        return $user !== null;
    }
}