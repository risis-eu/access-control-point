<?php
/* Access Control Point to Nano */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = new Silex\Application();

// Chargement de la config dans parameters.json
require_once __DIR__.'/../config/config.php';

// Activation du debugging a desactiver en production
$app['debug'] = $app['parameters']['debug'];

/*********************************************************************************************
 *                                                                                           *
 * Database config. See http://silex.sensiolabs.org/doc/providers/doctrine.html              *
 *                                                                                           *
 ********************************************************************************************/
$app['db.options'] = array(
    'driver'   => $app['parameters']['db.options']['driver'],
    'host' => $app['parameters']['db.options']['host'],
    'port' => $app['parameters']['db.options']['port'],
    'dbname' => $app['parameters']['db.options']['dbname'],
    'user' => $app['parameters']['db.options']['user'],
    'password' => $app['parameters']['db.options']['password'],
    'charset' => $app['parameters']['db.options']['charset'],
);

// Register Monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.$app['parameters']['monolog']['logfile'],
    'monolog.name' => $app['parameters']['monolog']['name']
));

$app->register(new Silex\Provider\DoctrineServiceProvider());

$app->before(function ($request) use ($app) {

    // Check access
    $token = $request->get('accessToken');
    if ( $token != $app['parameters']['accessToken'] )
        die( "You don't have access !" );

}, Application::EARLY_EVENT);

$app->get('/v1.0/entity/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType."/".$id.")" );

    if ( $entityType == "Patent" ) {
        $sql = "SELECT * FROM cortext_a02_corpus_cortext WHERE appln_id=?";
        $res = $app['db']->fetchAll($sql, array( $id ) );
        //$res = $app['db']->fetchAll( $sql );
        foreach( $res as $properties ) {
            $patent["entityID"] = (int)$properties["appln_id"];
            $patent["properties"] = $properties;
        }
        return $app->json($patent);
    }

    // If entity type doesn't exists, return an error
    $error["code"]=2;
    $error["message"]="EntityType unavailable";
    $error["fields"]=$entityType;
    return $app->json( $error );

});

$app->get('/v1.0/entities/{entityType}', function(Application $app, Request $request, $entityType ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities (".$entityType.")" );

    $offset = (int)$request->get('offset');
    $limit = (int)$request->get('limit');
    if ( $limit == 0 ) $limit = 10;
    $app['monolog']->addInfo( "offset (".$offset.")" );
    $app['monolog']->addInfo( "limit (".$limit.")" );
    
    if ( $entityType == "Patent" ) {
        $sql = "SELECT * FROM cortext_a02_corpus_cortext LIMIT ".$offset.",".$limit;
        //$res = $app['db']->fetchAll($sql, array( $offset, $limit ) );
        $res = $app['db']->fetchAll( $sql );
        foreach( $res as $properties ) {
            $patent["entityID"] = (int)$properties["appln_id"];
            $patent["properties"] = $properties;
            $response[] = $patent;
        }
        return $app->json($response);
    }

    // If entity type doesn't exists, return an error
    $error["code"]=2;
    $error["message"]="EntityType unavailable";
    $error["fields"]=$entityType;
    return $app->json( $error );

});

$app->get('/v1.0/entities/{entityType}/count', function(Application $app, Request $request, $entityType) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities count (".$entityType.")" );

    if ( $entityType == "Patent" ) {
        $sql = "SELECT COUNT(*) AS nb FROM cortext_a02_corpus_cortext";
        $res = $app['db']->fetchAll( $sql );
        return $app->json( (int)$res[0]["nb"] );
    }

    // If entity type doesn't exists, return an error
    $error["code"]=2;
    $error["message"]="EntityType unavailable";
    $error["fields"]=$entityType;
    return $app->json( $error );
});

$app->get('/v1.0/entityTypes', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "EntityTypes" );
    // Creation of one entity type
    $entity['name']="Patent";
    $entity['path']="http://".$_SERVER['HTTP_HOST']."/v1.0/entities/Patent";
    // Addition of the entity type to the response
    $response[]=$entity;
    // Previous operation could be repeated to add more entityTypes

    // Return of entity types list in json format
    return $app->json( $response );
});

$app->get('/v1.0/metadata', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "Metadata" );
    $meta["title"]="Nano";
    $meta["description"]="Patents on nanotechnologies";
    $meta["creationDate"]="1970-01-01 00:00:00";
    $meta["owner"]="Lionel Villard - ESIEE";
    $meta["contact"]="lionel.villard@esiee.fr";
    return $app->json( $meta );
});

$app->get('/v1.0/relations', function(Application $app, Request $request) {
            
            
            return new Response('How about implementing relationsGet as a GET method ?');
            });
            

$app->get('/v1.0/relations/{relationName}', function(Application $app, Request $request, $relationName) {
            $offset = $request->get('offset');    $limit = $request->get('limit');    
            
            return new Response('How about implementing relationsRelationNameGet as a GET method ?');
            });
            

$app->get('/v1.0/relations/{relationName}/count', function(Application $app, Request $request, $relationName) {
            
            
            return new Response('How about implementing relationsRelationNameCountGet as a GET method ?');
            });

$app->run();
