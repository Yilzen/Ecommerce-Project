<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\OtpService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class AuthController 
{
    public function __construct(
        private Environment $twig,
        private UserModel $userModel,
        private OtpService $otpService,
        private string      $basePath,
    ) {}

    //Show login form
    public function showLogin(Request $request, Response $response): Response 
    {
        $html = $this->twig->render('pages/auth/login.html.twig', [
            'basePath' => $this->basePath
        ]);
        $response->getBody()->write($html);
        return $response;
    }

     //Login Verification
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            $response->getBody()->write("Invalid email or password");
            return $response;
        }

        $_SESSION['pending_user_id'] = $user->id;

        return $response
            ->withHeader('Location', $this->basePath . '/login/request')
            ->withStatus(302);
    }

    //Get Code
    public function requestOtp(Request $request, Response $response): Response
    {
        $userId = $_SESSION['pending_user_id'] ?? null;

        if (!$userId) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $user = $this->userModel->findById((int)$userId);

        if (!$user) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        // CREATE ONLY ONCE
        if (empty($user->totp_secret)) {
            $user->totp_secret = $this->otpService->createSecret();
            $this->userModel->save($user);
        }

        $qr = $this->otpService->getQr($user->email, $user->totp_secret);

        $html = $this->twig->render('pages/auth/requestOtp.html.twig', [
            'qr_code' => $qr,
            'basePath' => $this->basePath
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    //Show Verify Form
    public function showVerify(Request $request, Response $response): Response
    {
        $html = $this->twig->render('pages/auth/verifyOtp.html.twig', [
            'basePath' => $this->basePath,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    //Verify Code
    public function verifyOtp(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $code = trim($data['code'] ?? '');

        $userId = $_SESSION['pending_user_id'] ?? null;

        if (!$userId) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $user = $this->userModel->findById((int)$userId);

        if (!$user || !$user->totp_secret) {
            return $response->withHeader('Location', $this->basePath . '/login')->withStatus(302);
        }

        $valid = $this->otpService->verify($user->totp_secret, $code);

        if (!$valid) {
            $html = $this->twig->render('pages/auth/verifyOtp.html.twig', [
                'error' => true,
                'basePath' => $this->basePath
            ]);

            $response->getBody()->write($html);
            return $response;
        }

        unset($_SESSION['pending_user_id']);

        $_SESSION['user'] = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email
        ];

        session_regenerate_id(true);

        if($_SESSION['user']['username'] === 'admin') {
            return $response
                ->withHeader('Location', $this->basePath . '/homeAdmin')
                ->withStatus(302);
        }
        
        return $response
        ->withHeader('Location', $this->basePath . '/home')
        ->withStatus(302);
    }

    //Show register form
    public function showRegister(Request $request, Response $response): Response 
    {
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        $html = $this->twig->render('pages/auth/register.html.twig', [
            'basePath' => $this->basePath,
            'error' => $error
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    //Register User
    public function register(Request $request, Response $response): Response 
    {
        $data = $request->getParsedBody();

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        $error = null;

        if (!$username || !$email || !$password) {
            $error = "All fields are required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } elseif ($this->userModel->findByEmail($email)) {
            $error = "Email already exists";
        }

        if ($error) {
            $_SESSION['error'] = $error;

            return $response
                ->withHeader('Location', $this->basePath . '/register')
                ->withStatus(302);
        }

        $this->userModel->create($username, $email, $password);

        return $response
            ->withHeader('Location', $this->basePath . '/login')
            ->withStatus(302);
    }

    //Logout
    public function logout(Request $request, Response $response): Response
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        return $response
            ->withHeader('Location', $this->basePath . '/login')
            ->withStatus(302);
    }

    //Password or Gmail or Username errors
    private function error(Response $response, string $message): Response
    {
        $response->getBody()->write($message);
        return $response->withStatus(400);
    }
}