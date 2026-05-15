<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class DownloadModel
{
    private string $table = 'download';

    public function create(int $userId, int $artId): int
    {
        $download = R::dispense($this->table);

        $download->user_id = $userId;
        $download->art_id = $artId;
        $download->created_at = date('Y-m-d H:i:s');

        return R::store($download);
    }

    public function findByUser(int $userId): array
    {
        return array_values(
            R::find($this->table, 'user_id = ? ORDER BY created_at DESC', [$userId])
        );
    }

    public function findByArt(int $artId): array
    {
        return array_values(
            R::find($this->table, 'art_id = ? ORDER BY created_at DESC', [$artId])
        );
    }
}