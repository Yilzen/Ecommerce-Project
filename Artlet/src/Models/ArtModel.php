<?php

declare(strict_types=1);

namespace App\Models;

use RedBeanPHP\R;

class ArtModel
{
    private string $table = 'art';

    public function findAll(): array
    {
        return R::findAll($this->table, 'ORDER BY id DESC');
    }

    public function findById(int $id): mixed
    {
        $art = R::load($this->table, $id);

        if (!$art->id) return null;

        $user = R::load('user', $art->user_id);

        $art->artist = $user->id ? $user->username : 'Unknown Artist';

        return $art;
    }

    public function create(int $userId, string $title, string $description): int
    {
        $art = R::dispense($this->table);

        $art->user_id = $userId;
        $art->title = $title;
        $art->description = $description;
        $art->created_at = date('Y-m-d H:i:s');

        return R::store($art);
    }

    public function delete(int $id): void
    {
        $art = R::load($this->table, $id);

        if ($art->id) {
            R::trash($art);
        }
    }

    public function update(int $id, string $title, string $description): void
    {
        $art = R::load($this->table, $id);

        if ($art->id) {
            $art->title = $title;
            $art->description = $description;

            R::store($art);
        }
    }

    public function search(string $term): array
    {
        return R::find('art', 'title LIKE ?', ['%' . $term . '%']);
    }
}