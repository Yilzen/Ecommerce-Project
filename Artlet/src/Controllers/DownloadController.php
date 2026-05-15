<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DownloadModel;
use App\Models\ArtModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class DownloadController
{
    public function __construct(
        private Environment $twig,
        private DownloadModel $downloadModel,
        private ArtModel $artModel,
        private string $basePath
    ) {}

    public function download(Request $request, Response $response, array $args): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $artId = (int) $args['id'];

        $art = $this->artModel->findById($artId);

        if (!$art) {
            return $response->withStatus(404);
        }

        $base = __DIR__ . '/../../storage/' . $artId;

        if (file_exists($base . '.png')) {
            $filePath = $base . '.png';
        } elseif (file_exists($base . '.jpg')) {
            $filePath = $base . '.jpg';
        } else {
            return $response->withStatus(404);
        }

        $this->downloadModel->create((int)$user['id'], $artId);

        $stream = new \Slim\Psr7\Stream(fopen($filePath, 'rb'));

        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Disposition', 'attachment; filename="art-' . $artId . '.png"')
            ->withBody($stream);
    }
}