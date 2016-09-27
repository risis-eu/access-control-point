<?php
/* Access Control Point to Nano */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = new Silex\Application();

// Chargement de la config dans parameters.json
require_once __DIR__.'/../config/config.php';

// Activation du debugging en fonction du fichier de paramètre
$app['debug'] = $app['parameters']['debug'];

/*********************************************************************************************
 *                                                                                           *
 * Database config. See http://silex.sensiolabs.org/doc/providers/doctrine.html              *
 *                                                                                           *
 ********************************************************************************************/
$app['db.options'] = array(
    'driver'   => $app['parameters']['db.options']['driver'],
    'host'     => $app['parameters']['db.options']['host'],
    'port'     => $app['parameters']['db.options']['port'],
    'dbname'   => $app['parameters']['db.options']['dbname'],
    'user'     => $app['parameters']['db.options']['user'],
    'password' => $app['parameters']['db.options']['password'],
    'charset'  => $app['parameters']['db.options']['charset'],
);

// Register Monolog to create log file
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.$app['parameters']['monolog']['logfile'],
    'monolog.name'    => $app['parameters']['monolog']['name']
));

$app->register(new Silex\Provider\DoctrineServiceProvider());

$app->before(function ($request) use ($app) {

    // Check access
    $token = $request->get('accessToken');

    if ( ! in_array( $token, $app['parameters']['accessToken'] ) ) {
        $error["code"]    = 1;
        $error["message"] = "Access forbidden";
        $error["fields"]  = "";
        return $app->json( $error, 401 );
    }

}, Application::EARLY_EVENT);

// Branchement de la v1.0, ainsi on pourra faire cohabiter les versions
$app->mount('/v1.0', include 'acp_v1_0.php');

$app->run();
