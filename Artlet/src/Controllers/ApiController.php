<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArtModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApiController
{
    public function __construct(private ArtModel $model) {}

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function index(Request $request, Response $response): Response
    {
        $arts = $this->model->findAll();

        $payload = array_map(function ($art) {
            return [
                'id'          => (int) $art->id,
                'user_id'     => (int) $art->user_id,
                'title'       => (string) $art->title,
                'description' => (string) $art->description,
                'created_at'  => (string) $art->created_at,
            ];
        }, $arts);

        return $this->json($response, $payload);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $userId      = (int) ($_SESSION['user']['id'] ?? 0);
        $title       = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');

        if ($userId === 0) {
            return $this->json($response, [
                'error' => 'unauthorized'
            ], 401);
        }

        if ($title === '' || $description === '') {
            return $this->json($response, [
                'error' => 'title and description are required'
            ], 422);
        }

        $id = $this->model->create($userId, $title, $description);

        $art = $this->model->findById($id);

        return $this->json($response, [
            'id'          => (int) $art->id,
            'user_id'     => (int) $art->user_id,
            'title'       => (string) $art->title,
            'description' => (string) $art->description,
            'created_at'  => (string) $art->created_at,
        ], 201);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $art = $this->model->findById((int) $args['id']);

        if (!$art) {
            return $this->json($response, [
                'error' => 'not found'
            ], 404);
        }

        return $this->json($response, [
            'id'          => (int) $art->id,
            'user_id'     => (int) $art->user_id,
            'title'       => (string) $art->title,
            'description' => (string) $art->description,
            'created_at'  => (string) $art->created_at,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $art = $this->model->findById((int) $args['id']);

        if (!$art) {
            return $this->json($response, [
                'error' => 'not found'
            ], 404);
        }

        $this->model->delete((int) $args['id']);

        return $response->withStatus(204);
    }
}