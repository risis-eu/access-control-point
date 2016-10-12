<?php
/* Access Control Point */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = new Silex\Application();

// Configuration loading from parameters.json
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

// Register Doctrine to access DB
$app->register(new Silex\Provider\DoctrineServiceProvider());

// Register UrlGenerator to get dynamic routing fonctionnalities
$app->register(new Silex\Provider\RoutingServiceProvider());

// Validation of user access via accessToken. This function is called before every route.
$app->before(function ($request) use ($app) {

    // Get accessToken from request
    $token = $request->get('accessToken');

    // Is accessToken defined in parameters file ?
    if ( ! in_array( $token, $app['parameters']['accessToken'] ) ) {
        $error["code"]    = 1;
        $error["message"] = "Access forbidden";
        $error["fields"]  = "";
        return $app->json( $error, 401 );
    }

}, Application::EARLY_EVENT);

// Connection of v1.0, thereby we can hold together multiple versions
$app->mount( '/v1.0', include 'acp_v1_0.php' );
$app->mount( '/v1.1', include 'acp_v1_1.php' );

// Example of a new version. Both can work at the same time.
// $app->mount( '/v2.0', include 'acp_v2_0.php' );

// Let's rock
$app->run();
