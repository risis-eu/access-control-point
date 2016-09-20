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


function getPropertiesDescription( Application $app, $entityType ) {

    $sql = "SELECT name, description, hasEntityType FROM dictionnary WHERE entityType=?";

    return $app['db']->fetchAll($sql, array( $entityType ) );
}


function validEntityType( $entityType ) {
    global $app;
    if ( ! in_array( $entityType, $app['parameters']['metadata']['entityType'] ) ) {
        // If entity type doesn't exists, return an error
        $error["code"]=2;
        $error["message"]="EntityType unavailable";
        $error["fields"]=$entityType;
        return $error;
    }
    return false;
}


$app->get('/v1.0/entity/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType."/".$id.")" );

    if ( $error = validEntityType( $entityType ) ) { return $app->json( $error ); }

    $entity["dataset"] = $app['parameters']['metadata']['dataset'];
    $entity["version"] = $app['parameters']['metadata']['version'];
    $entity["entity_type"] = $entityType;

    $entity["property_description"] = getPropertiesDescription( $app, $entityType );

    $sql = "SELECT * FROM " . $entityType . " WHERE id=?";

    $results = $app['db']->fetchAll($sql, array( $id ) );

    foreach( $results as $res ) {
        foreach( $res as $property => $value ) {
            if ( $property === "id" ) {
                $instance["id"] = $value;
            } else {
                $prop["property"] = $property;
                $prop["value"]    = $value;
                $instance["property_values"][] = $prop;
            }
        }
        $entity["instances"][] = $instance;
        unset( $instance );
    }

    return $app->json($entity);

});

$app->get('/v1.0/entities/{entityType}', function(Application $app, Request $request, $entityType ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities (".$entityType.")" );

    if ( $error = validEntityType( $entityType ) ) { return $app->json( $error ); }

    $entity["dataset"] = $app['parameters']['metadata']['dataset'];
    $entity["version"] = $app['parameters']['metadata']['version'];
    $entity["entity_type"] = $entityType;

    $entity["property_description"] = getPropertiesDescription( $app, $entityType );

    $sql = "SELECT * FROM " . $entityType ;

    $offset = (int)$request->get('offset');
    $limit = (int)$request->get('limit');
    if ( $limit == 0 ) $limit = 10;
    $app['monolog']->addInfo( "offset (".$offset.")" );
    $app['monolog']->addInfo( "limit (".$limit.")" );

    $sql .= " LIMIT ".$offset.",".$limit;

    $results = $app['db']->fetchAll( $sql );

    foreach( $results as $res ) {
        foreach( $res as $property => $value ) {
            if ( $property === "id" ) {
                $instance["id"] = $value;
            } else {
                $prop["property"] = $property;
                $prop["value"]    = $value;
                $instance["property_values"][] = $prop;
            }
        }
        $entity["instances"][] = $instance;
        unset( $instance );
    }

    return $app->json($entity);
});

$app->get('/v1.0/entities/{entityType}/count', function(Application $app, Request $request, $entityType) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities count (".$entityType.")" );

    if ( $error = validEntityType( $entityType ) ) { return $app->json( $error ); }

    $sql = "SELECT COUNT(*) AS nb FROM " . $entityType ;

    $res = $app['db']->fetchAll( $sql );
    return $app->json( (int)$res[0]["nb"] );
});

$app->get('/v1.0/entityTypes', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "EntityTypes" );

    $entitiesList = $app['parameters']['metadata']['entityType'];

    foreach( $entitiesList as $oneEntity ) {
        // Creation of one entity type
        $entity['name'] = $oneEntity;
        $entity['path'] = "http://".$_SERVER['HTTP_HOST']."/v1.0/entities/".$oneEntity;
        $entity['description'] = "One description of the entity type";
        // Addition of the entity type to the response
        $response[]=$entity;
    }

    // Return of entity types list in json format
    return $app->json( $response );
});

$app->get('/v1.0/metadata', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "Metadata" );

    $meta["title"]        = $app['parameters']['metadata']['dataset'];
    $meta["description"]  = $app['parameters']['metadata']['description'];
    $meta["creationDate"] = $app['parameters']['metadata']['creationDate'];
    $meta["owner"]        = $app['parameters']['metadata']['owner'];
    $meta["contact"]      = $app['parameters']['metadata']['contact'];

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
