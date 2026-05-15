<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArtModel;
use App\Models\CommentModel;
use App\Models\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class ArtController
{
    public function __construct(
        private Environment $twig,
        private ArtModel $artModel,
        private CommentModel $commentModel,
        private UserModel $userModel,
        private string $basePath,
    ) {}

    //Show Catalog
    public function index(Request $request, Response $response): Response
    {
        $art = $this->artModel->findAll();

        $html = $this->twig->render('pages/artlet/home.html.twig', [
            'art' => $art,
            'basePath' => $this->basePath,
            'user' => $_SESSION['user'] ?? null,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    //Show Single Art
    public function show(Request $request, Response $response, array $args): Response
    {
        $artId = (int) $args['id'];

        $art = $this->artModel->findById($artId);
        $comments = $this->commentModel->findByArtId($artId);

        foreach ($comments as $comment) {
            $user = $this->userModel->findById((int)$comment->user_id);
            $comment->username = $user?->username ?? 'Unknown';
        }

        $html = $this->twig->render('pages/artlet/art.html.twig', [
            'art' => $art,
            'comments' => $comments,
            'basePath' => $this->basePath
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}