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


/**
 * Get dictionnary of properties of entities from DB
 */
function getPropertiesDescription( Application $app, $entityType ) {

    $sql = "SELECT name, description, hasEntityType FROM dictionnary WHERE entityType=?";
    return $app['db']->fetchAll($sql, array( $entityType ) );
}


/**
 * Return false if entityType is defined in the parameters file
 * Ohterwise it returns an array with an error message
 */
function invalidEntityType( $entityType ) {
    global $app;
    if ( ! in_array( $entityType, $app['parameters']['metadata']['entityType'] ) ) {
        // If entity type doesn't exists, return an error
        $error["code"]    = 2;
        $error["message"] = "EntityType unavailable";
        $error["fields"]  = $entityType;
        return $error;
    }
    return false;
}


$app->get('/v1.0/entities/{entityType}/count', function(Application $app, Request $request, $entityType) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities count (".$entityType.")" );

    if ( $error = invalidEntityType( $entityType ) ) { return $app->json( $error, 404 ); }

    $sql = "SELECT COUNT(*) AS nb FROM " . $entityType ;

    $res = $app['db']->fetchAll( $sql );

    $response["total"] = (int)$res[0]["nb"];
    $response["max_per_page"] = $app['parameters']['db.options']['max_limit'];

    return $app->json( $response );
});


$app->get('/v1.0/entities/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType."/".$id.")" );

    if ( $error = invalidEntityType( $entityType ) ) { return $app->json( $error, 404 ); }

    $entity["dataset"]     = $app['parameters']['metadata']['dataset'];
    $entity["version"]     = $app['parameters']['metadata']['version'];
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

    if ( count($entity["instances"]) == 0 ) {
        $error["code"]    = 3;
        $error["message"] = "No instance found";
        $error["field"]   = $entityType . (isset($id)?" $id":"");
        return $app->json( $error, 404 );
    }

    return $app->json($entity);

});

$app->get('/v1.0/entities/{entityType}', function(Application $app, Request $request, $entityType ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities (".$entityType.")" );

    if ( $error = invalidEntityType( $entityType ) ) { return $app->json( $error, 404 ); }

    $entity["dataset"]     = $app['parameters']['metadata']['dataset'];
    $entity["version"]     = $app['parameters']['metadata']['version'];
    $entity["entity_type"] = $entityType;

    $entity["property_description"] = getPropertiesDescription( $app, $entityType );

    $offset = (int)$request->get('offset');
    $limit  = (int)$request->get('limit');
    if ( $limit == 0 ) $limit = $app['parameters']['db.options']['default_limit'];
    $limit = min( $limit, $app['parameters']['db.options']['max_limit'] );

    $sql = "SELECT * FROM " . $entityType . " LIMIT " . $offset . "," . $limit;

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

    if ( count($entity["instances"]) == 0 ) {
        $error["code"]    = 3;
        $error["message"] = "No instance found";
        $error["field"]   = $entityType . (isset($id)?" $id":"");
        return $app->json( $error, 404 );
    }

    return $app->json($entity);
});


$app->get('/v1.0/entityTypes', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "EntityTypes" );

    $entitiesList = $app['parameters']['metadata']['entityType'];

    foreach( $entitiesList as $oneEntity ) {
        // Creation of one entity type
        $entity['name'] = $oneEntity;
        $entity['path'] = "http://".$_SERVER['HTTP_HOST']."/v1.0/entities/".$oneEntity;

        $sql = "SELECT description FROM entities WHERE entity=?";
        $result = $app['db']->fetchAll( $sql, array( $oneEntity ) );

        $entity['description'] = $result[0]['description'];
        // Addition of the entity type to the response
        $response[] = $entity;
    }

    // Return of entity types list in json format
    return $app->json( $response );
});

$app->get('/v1.0/metadata', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "Metadata" );

    $meta["title"]                = $app['parameters']['metadata']['dataset'];
    $meta["description"]          = $app['parameters']['metadata']['description'];
    $meta["creationDate"]         = $app['parameters']['metadata']['creationDate'];
    $meta["contact_person"]       = $app['parameters']['metadata']['contact_person'];
    $meta["contact_organisation"] = $app['parameters']['metadata']['contact_organisation'];
    $meta["contact_email"]        = $app['parameters']['metadata']['contact_email'];

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
