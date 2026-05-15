<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class FollowModel
{
    private string $table = 'follow';

    public function follow(int $followerId, int $followingId): int
    {
        $existing = R::findOne(
            $this->table,
            'follower_id = ? AND following_id = ?',
            [$followerId, $followingId]
        );

        if ($existing) {
            return $existing->id;
        }

        $follow = R::dispense($this->table);

        $follow->follower_id = $followerId;
        $follow->following_id = $followingId;
        $follow->created_at = date('Y-m-d H:i:s');

        return R::store($follow);
    }

    public function unfollow(int $followerId, int $followingId): void
    {
        $follow = R::findOne(
            $this->table,
            'follower_id = ? AND following_id = ?',
            [$followerId, $followingId]
        );

        if ($follow) {
            R::trash($follow);
        }
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        return (bool) R::findOne(
            $this->table,
            'follower_id = ? AND following_id = ?',
            [$followerId, $followingId]
        );
    }

    public function getFollowers(int $userId): array
    {
        return array_values(
            R::find($this->table, 'following_id = ?', [$userId])
        );
    }

    public function getFollowing(int $userId): array
    {
        return array_values(
            R::find($this->table, 'follower_id = ?', [$userId])
        );
    }
}