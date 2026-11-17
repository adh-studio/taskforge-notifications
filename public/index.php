<?php

declare(strict_types=1);

/**
 * Point d'entree HTTP du microservice de notifications.
 *
 * Assemble les dependances (composition root), declare les routes de
 * l'API RESTful puis delegue au noyau le traitement de la requete.
 */

use TaskForge\Notifications\Auth\JwtValidator;
use TaskForge\Notifications\Database\DatabaseFactory;
use TaskForge\Notifications\Http\Kernel;
use TaskForge\Notifications\Http\NotificationController;
use TaskForge\Notifications\Http\Request;
use TaskForge\Notifications\Http\Router;
use TaskForge\Notifications\Repository\PdoNotificationRepository;
use TaskForge\Notifications\Service\NotificationService;
use TaskForge\Notifications\Service\ReportService;
use TaskForge\Notifications\Support\SystemClock;

require __DIR__ . '/../vendor/autoload.php';

$clock = new SystemClock();
$pdo = DatabaseFactory::depuisEnvironnement();

$repository = new PdoNotificationRepository($pdo);
$notificationService = new NotificationService($repository, $clock);
$reportService = new ReportService($repository);

$cleJwt = getenv('JWT_SHARED_SECRET') ?: '';
$jwt = new JwtValidator($cleJwt, $clock);

$controller = new NotificationController($notificationService, $reportService, $jwt);

$router = new Router();
$router->ajouter('POST', '/events', [$controller, 'enregistrerEvenement']);
$router->ajouter('GET', '/notifications', [$controller, 'lister']);
$router->ajouter('PATCH', '/notifications/{id}/read', [$controller, 'marquerCommeLue']);
$router->ajouter('GET', '/reports/summary', [$controller, 'rapport']);

$kernel = new Kernel($router);
$reponse = $kernel->traiter(Request::depuisGlobals());
$reponse->emettre();
