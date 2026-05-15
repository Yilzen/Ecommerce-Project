<?php

declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);


session_start();

use App\Controllers\ArtController;
use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\DownloadController;
use App\Controllers\FollowController;
use App\Controllers\MembershipController;
use App\Controllers\PaymentController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Models\ArtModel;
use App\Models\CommentModel;
use App\Models\DownloadModel;
use App\Models\FollowModel;
use App\Models\MembershipModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use App\Services\OtpService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R;
use Slim\Factory\AppFactory;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

require __DIR__ . '/vendor/autoload.php';

//Db
$dbPath = __DIR__ . '/var/artlet.db';
R::setup('sqlite:' . $dbPath);
R::freeze(false);

$artModel = new ArtModel();
$commentModel = new CommentModel();
$downloadModel = new DownloadModel();
$followModel = new FollowModel();
$membershipModel = new MembershipModel();
$paymentModel = new PaymentModel();
$userModel = new UserModel();



//Insert into db if doesn't exist
$admin = R::findOne('user', 'username = ?', ['admin']);

if (!$admin) {
    $admin = R::dispense('user');
    $admin->username = 'admin';
    $admin->email = 'admin@artlet.com';
    $admin->password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin->bio = '';
    $admin->daily_download_count = 0;
    $admin->created_at = date('Y-m-d H:i:s');

    R::store($admin);
}

if (R::count('art') === 0) {

    $samples = [
        ['user_id' => 1, 'title' => 'Sunset Overdrive', 'description' => 'Warm digital sunset landscape'],
        ['user_id' => 1, 'title' => 'Blue Ocean Dream', 'description' => 'Calm deep blue waves'],
        ['user_id' => 1, 'title' => 'Cyber City', 'description' => 'Neon futuristic city at night'],
        ['user_id' => 1, 'title' => 'Mountain Silence', 'description' => 'Minimalist mountain photography'],
        ['user_id' => 1, 'title' => 'Abstract Emotion', 'description' => 'Color explosion of feelings'],
    ];

    foreach ($samples as $data) {
        $art = R::dispense('art');
        $art->user_id = $data['user_id'];
        $art->title = $data['title'];
        $art->description = $data['description'];
        $art->created_at = date('Y-m-d H:i:s');

        R::store($art);
    }
}

//Templates
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig   = new Environment($loader, [
    'cache'         => false,
    'auto_reload'   => true,
]);

//Twig user variable
$twig->addGlobal('user', $_SESSION['user'] ?? null);

$translator = new Translator($_SESSION["lang"] ?? "en");

$translator->addLoader('array', new ArrayLoader());

$translator->addResource('array', require __DIR__ . '/translations/messages.en.php', 'en');
$translator->addResource('array', require __DIR__ . '/translations/messages.fr.php', 'fr');

$twig->addFunction(new TwigFunction('trans', function (string $key, array $params = []) use ($translator) {
    $locale = $_SESSION['lang'] ?? 'en';
    return $translator->trans($key, $params, null, $locale);
}));


//DI injection
$basePath = '/Artlet';

$container = new \DI\Container();
$container -> set(Environment::class, $twig);
$container->set(ArtController::class, fn () => new ArtController($twig, $artModel, $commentModel, $userModel, $basePath));
$container->set(AuthController::class, fn () => new AuthController($twig, $userModel, new OtpService(), $basePath));
$container->set(CommentController::class, fn () => new CommentController($twig, $commentModel, $basePath));
$container->set(DownloadController::class, fn () => new DownloadController($twig, $downloadModel, $artModel, $basePath));
$container->set(FollowController::class, fn () => new FollowController($twig, $followModel, $basePath));
$container->set(MembershipController::class, fn () => new MembershipController($twig, $membershipModel, $userModel, $basePath));
$container->set(PaymentController::class, fn () => new PaymentController($twig, $paymentModel, $userModel, $basePath));
$container->set(UserController::class, fn () => new UserController($twig, $userModel, $basePath));

//App
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->add(new MaintenanceMiddleware(
    flagFile:        __DIR__ . '/var/maintenance.flag',
    responseFactory: $app->getResponseFactory()
));

$app->add(new SecurityHeadersMiddleware());

$authMiddleware = new AuthMiddleware(
    responseFactory: $app->getResponseFactory(),
    basePath: $basePath
);

$logFile = __DIR__ . '/var/app.log';

//Global Middleware

$loggerMiddleware = function (Request $request, \Psr\Http\Server\RequestHandlerInterface $handler) use ($logFile) 
{
    $start  = microtime(true);
    $method = $request->getMethod();
    $path   = $request->getUri()->getPath();

    $response = $handler->handle($request);

    $status  = $response->getStatusCode();
    $elapsed = round((microtime(true) - $start) * 1000);

    $line = sprintf(
        "[%s] %-6s %-25s → %d  (%dms)\n",
        date('Y-m-d H:i:s'),
        $method,
        $path,
        $status,
        $elapsed
    );

    file_put_contents($logFile, $line, FILE_APPEND);

    return $response;
};

$app->add($loggerMiddleware);

$app->get('/debug', function ($req, $res) {
    $res->getBody()->write($req->getUri()->getPath());
    return $res;
});

$app->get('/', function (Request $req, Response $res) use ($basePath) {
    return $res
        ->withHeader('Location', $basePath . '/home')
        ->withStatus(302);
});

$app->get('/home', function (Request $req, Response $res) use ($twig, $basePath, $artModel) {

    $artworks = $artModel->findAll();

    $html = $twig->render('pages/artlet/home.html.twig', [
        'basePath' => $basePath,
        'user' => $_SESSION['user'] ?? null,
        'artworks' => $artworks
    ]);

    $res->getBody()->write($html);
    return $res;
});

$app->get('/homeAdmin', function (Request $req, Response $res) use ($twig, $basePath, $artModel) {
    $artworks = $artModel->findAll();

    $html = $twig->render('pages/artlet/homeAdmin.html.twig', [
        'basePath' => $basePath,
        'user' => $_SESSION['user'] ?? null,
        'artworks' => $artworks
    ]);

    $res->getBody()->write($html);
    return $res;
});

$app->get('/api/users', function (Request $request, Response $response) use ($userModel) {

    $users = $userModel->findAll();

    $data = array_map(fn($user) => [
        'id' => $user->id,
        'username' => $user->username,
    ], $users);

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

//ROUTES

//Proteted Routes
$app->group('', function ($group) {
    //Catalog and Specific Art
    $group->get('/art', [ArtController::class, 'index']);
    $group->get('/art/{id}', [ArtController::class, 'show']);
    $group->post('/art/{id}/comment', [CommentController::class, 'create']);
    $group->post('/comment/{id}/edit', [CommentController::class, 'update']);
    $group->post('/comment/{id}/delete', [CommentController::class, 'delete']);

    //Download
    $group->get('/downloads', [DownloadController::class, 'index']);
    $group->get('/downloads/history', [DownloadController::class, 'history']);

    //Follow
    $group->post('/follow/{id}', [FollowController::class, 'follow']);
    $group->post('/unfollow/{id}', [FollowController::class, 'unfollow']);

    //Membership
    $group->get('/membership', [MembershipController::class, 'index']);
    $group->post('/membership/{type}', [MembershipController::class, 'goToPayment']);

    //Payments
    $group->get('/payment', [PaymentController::class, 'index']);
    $group->post('/payment', [PaymentController::class, 'process']);

    //Profile Managment
    $group->get('/profile', [UserController::class, 'profile']);
    $group->get('/profile/update', [UserController::class, 'showUpdate']);
    $group->post('/profile/update', [UserController::class, 'updateProfile']);
    $group->get('/profile/delete', [UserController::class, 'showDelete']);
    $group->post('/profile/delete', [UserController::class, 'delete']);
})->add($authMiddleware);

//Authentication(public)
$app->get('/login', [AuthController::class, 'showLogin']);
$app->post('/login', [AuthController::class, 'login']);
$app->get('/login/request', [AuthController::class, 'requestOtp']);
$app->get('/login/verify', [AuthController::class, 'showVerify']);
$app->post('/login/verify', [AuthController::class, 'verifyOtp']);
$app->get('/register', [AuthController::class, 'showRegister']);
$app->post('/register', [AuthController::class, 'register']);
$app->get('/logout', [AuthController::class, 'logout']);
$app->get('/download/{id}', [DownloadController::class, 'download']);

//Run
$app->run();