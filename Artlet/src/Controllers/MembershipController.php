<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MembershipModel;
use App\Models\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class MembershipController
{
    public function __construct(
        private Environment $twig,
        private MembershipModel $membershipModel,
        private UserModel $userModel,
        private string $basePath
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $html = $this->twig->render('pages/shop/membership.html.twig', [
            'user' => $_SESSION['user'] ?? null,
            'basePath' => $this->basePath
        ]);

        $response->getBody()->write($html);
        return $response;
    }
    
    public function goToPayment(Request $request, Response $response, array $args): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $type = $args['type'];

        // optional validation
        if (!in_array($type, ['free', 'premium'])) {
            return $response->withStatus(400);
        }

        $_SESSION['pending_membership'] = $type;

        return $response
            ->withHeader('Location', $this->basePath . '/payment')
            ->withStatus(302);
    }

    public function showCancel(Request $request, Response $response): Response
    {
        $html = $this->twig->render('pages/shop/cancel_membership.html.twig', [
            'basePath' => $this->basePath,
            'user' => $_SESSION['user'] ?? null
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function cancel(Request $request, Response $response): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response
                ->withHeader('Location', $this->basePath . '/login')
                ->withStatus(302);
        }

        // downgrade user
        $userBean = \RedBeanPHP\R::load('user', (int)$user['id']);
        $userBean->membership_status = 'free';
        \RedBeanPHP\R::store($userBean);

        $_SESSION['user']['membership_status'] = 'free';

        return $response
            ->withHeader('Location', $this->basePath . '/membership')
            ->withStatus(302);
    }
}