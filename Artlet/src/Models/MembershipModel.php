<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class MembershipModel
{
    private string $table = 'membership';

    public function create(int $userId, string $type, string $status = 'active'): int 
    {
        $membership = R::dispense($this->table);

        $membership->user_id = $userId;
        $membership->type = $type;
        $membership->status = $status;
        $membership->created_at = date('Y-m-d H:i:s');
        $membership->expires_at = null;

        return R::store($membership);
    }

    public function getActiveByUser(int $userId): mixed
    {
        return R::findOne(
            $this->table,
            'user_id = ? AND status = ? ORDER BY id DESC',
            [$userId, 'active']
        );
    }

    public function upgrade(int $userId, string $type, ?string $expiresAt = null): void
    {
        $old = R::find($this->table, 'user_id = ? AND status = ?', [$userId, 'active']);

        foreach ($old as $m) {
            $m->status = 'expired';
            R::store($m);
        }

        $membership = R::dispense($this->table);

        $membership->user_id = $userId;
        $membership->type = $type;
        $membership->status = 'active';
        $membership->created_at = date('Y-m-d H:i:s');
        $membership->expires_at = $expiresAt;

        R::store($membership);
    }

    public function isPremium(int $userId): bool
    {
        $user = R::load('user', $userId);

        return isset($user->membership_status)
            && $user->membership_status === 'premium';
    }

    public function setUserMembership(int $userId, string $type): void
    {
        $user = R::load('user', $userId);

        if ($user->id) {
            $user->membership_status = $type;
            R::store($user);
        }
    }
}