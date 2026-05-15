<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use RedBeanPHP\R;

class UserController
{
    public function __construct(
        private Environment $twig,
        private UserModel $userModel,
        private string $basePath
    ) {}

    public function profile(Request $request, Response $response): Response
    {
        $session = $_SESSION['user'] ?? null;

        if (!$session) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $user = $this->userModel->findById($session['id']);

        $artworks = R::find('art', 'user_id = ? ORDER BY id DESC', [$user->id]);

        $html = $this->twig->render('pages/user/profile.html.twig', [
            'basePath' => $this->basePath,
            'user' => $user,
            'artworks' => $artworks,
            'error' => $_SESSION['error'] ?? null,
            'success' => $_SESSION['success'] ?? null
        ]);

        unset($_SESSION['error'], $_SESSION['success']);

        $response->getBody()->write($html);
        return $response;
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        $session = $_SESSION['user'] ?? null;

        if (!$session) {
            return $response->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $data = $request->getParsedBody();

        $username = trim($data['username'] ?? '');
        $bio = trim($data['bio'] ?? '');

        $user = $this->userModel->findById($session['id']);

        if (!$username) {
            $_SESSION['error'] = "Username cannot be empty";
            return $response->withHeader('Location', $this->basePath . '/profile')
                ->withStatus(302);
        }
        
        if ($this->userModel->isUsernameTaken($username, (int)$user->id)) {
            $_SESSION['error'] = "Username already taken";
            return $response->withHeader('Location', $this->basePath . '/profile')
                ->withStatus(302);
        }

        $user->username = $username;
        $user->bio = $bio;

        $this->userModel->updateProfile($user);

        $_SESSION['user']['username'] = $username;
        $_SESSION['success'] = "Profile updated successfully";

        return $response->withHeader('Location', $this->basePath . '/profile')
            ->withStatus(302);
    }

    public function delete(Request $request, Response $response): Response
    {
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        $data = $request->getParsedBody();
        $password = $data['password'] ?? '';

        $user = $this->userModel->findById((int)$userId);

        if (!$user || !password_verify($password, $user->password)) {
            $_SESSION['error'] = 'Invalid password';
            return $response
                ->withHeader('Location', $this->basePath . '/profile')
                ->withStatus(302);
        }

        $this->userModel->delete((int)$userId);

        $_SESSION = [];
        session_destroy();

        return $response
            ->withHeader('Location', $this->basePath . '/')
            ->withStatus(302);
    }

    public function showUpdate(Request $request, Response $response): Response 
    {
        $html = $this->twig->render('pages/user/profileUpd.html.twig', [
            'basePath' => $this->basePath,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function showDelete(Request $request, Response $response): Response 
    {
        $html = $this->twig->render('pages/user/profileDel.html.twig', [
            'basePath' => $this->basePath,
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}