<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class PaymentController
{
    public function __construct(
        private Environment $twig,
        private PaymentModel $paymentModel,
        private UserModel $userModel,
        private string $basePath
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $html = $this->twig->render('pages/shop/payment.html.twig', [
            'basePath' => $this->basePath,
            'user' => $_SESSION['user'] ?? null
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function process(Request $request, Response $response): Response
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $data = $request->getParsedBody();
        $amount = (float) ($data['amount'] ?? 0);

        $this->paymentModel->create((int)$user['id'], $amount, 'completed');

        $this->userModel->save(
            $this->userModel->findById((int)$user['id'])
        );

        $userBean = $this->userModel->findById((int)$user['id']);
        $userBean->membership_status = 'premium';
        $this->userModel->save($userBean);

        $_SESSION['user']['membership_status'] = 'premium';

        return $response->withHeader('Location', $this->basePath . '/profile')->withStatus(302);
    }
}