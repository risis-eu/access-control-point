<?php
/* Access Control Point */
/* V1.1 of the ACP API */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;

// Initialisation of controller
$acp = $app['controllers_factory'];

require_once 'acp_functions.php';

/**
 * Retrieve one or all instances of a given entityType
 * id is a string that corresponds to the primary key of the entityType
 */
$acp->get('/entities/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType. (isset($id)?"/$id":"") .")" );

    // Validation of entityType
    if ( $error = invalidEntityType( $app, $entityType ) ) { return $app->json( $error, 404 ); }

    // Some metadata
    $entity["dataset"]     = $app['parameters']['metadata']['dataset'];
    $entity["version"]     = $app['parameters']['metadata']['version'];
    $entity["entity_type"] = $entityType;

    // Validation of parameters
    $offset = (int)$request->get('offset');
    $limit  = (int)$request->get('limit');
    if ( $limit == 0 ) $limit = $app['parameters']['db.options']['default_limit'];
    $limit = min( $limit, $app['parameters']['db.options']['max_limit'] );
    $entity["offset"] = $offset;
    $entity["limit"] = $limit;

    // Get dictionnary of properties
    $sql = "SELECT name, description, hasEntityType FROM dictionnary WHERE entityType=?";
    $entity["property_description"] = $app['db']->fetchAll($sql, array( $entityType ) );

    // Each entity table (or view) must have an id field, that is a primary key
    $sql = "SELECT * FROM ".$entityType.(isset($id)?" WHERE id=?":"")." LIMIT ".$offset.",".$limit;
    $query_params = array();
    if ( isset( $id ) ) $query_params[] = $id;
    $results = $app['db']->fetchAll($sql, $query_params );

    // Preparing the results
    foreach( $results as $res ) {
        foreach( $res as $property => $value ) {
            if ( $property === "id" ) {
                $instance["id"] = $value;
            }
            $prop["property"] = $property;
            $prop["value"]    = $value;
            $instance["property_values"][] = $prop;
        }
        $entity["instances"][] = $instance;
        unset( $instance );
    }

    // Error if instance id doesn't exist
    if ( count($entity["instances"]) == 0 ) {
        $error["code"]    = 3;
        $error["message"] = "No instance found";
        $error["field"]   = $entityType . (isset($id)?" $id":"");
        return $app->json( $error, 404 );
    }

    return $app->json($entity);
})->bind('entities_v1_1')->value('id',null);


/**
 * Get the list of available entity types. List is defined in config, so it's easy to deactivate one type.
 */
$acp->get('/entityTypes', function(Application $app, Request $request) {
    // Log of action
    $app['monolog']->addInfo( "EntityTypes" );

    // Get the available entity types
    $entitiesList = getAvailableEntities( $app );

    // Construction of information for each entity type
    foreach( $entitiesList as $oneEntity ) {
        // Creation of one entity type
        $entity['name'] = $oneEntity;

        if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ) {
            $scheme = "https://";
        } else if ( $_SERVER['SERVER_PORT'] == 443 ) {
            $scheme = "https://";
        } else {
            $scheme = "http://";
        }
        $entity['path'] = $scheme . $_SERVER['HTTP_HOST'] . $app['url_generator']->generate('entities_v1_1', array( "entityType"=>$oneEntity ) );

        // Get information about entity in the db, so it's easy for dataset owner to modify or declare entity types
        $sql = "SELECT description FROM entities WHERE entity=?";
        $result = $app['db']->fetchAll( $sql, array( $oneEntity ) );

        $entity['description'] = $result[0]['description'];

        $sql = "SELECT COUNT(*) AS nb FROM " . $oneEntity ;

        $res = $app['db']->fetchAll( $sql );

        $entity["total"] = (int)$res[0]["nb"];
        $entity["max_per_page"] = $app['parameters']['db.options']['max_limit'];

        // Addition of the entity type to the response
        $response[] = $entity;
    }

    // Return of entity types list in json format
    return $app->json( $response );
});


/**
 * Get metadata info about the dataset
 */
$acp->get('/metadata', function(Application $app, Request $request) {
    // Log action
    $app['monolog']->addInfo( "Metadata" );

    // Infos come from the config file
    $meta["title"]                = $app['parameters']['metadata']['dataset'];
    $meta["description"]          = $app['parameters']['metadata']['description'];
    $meta["creationDate"]         = $app['parameters']['metadata']['creationDate'];
    $meta["contact_person"]       = $app['parameters']['metadata']['contact_person'];
    $meta["contact_organisation"] = $app['parameters']['metadata']['contact_organisation'];
    $meta["contact_email"]        = $app['parameters']['metadata']['contact_email'];

    return $app->json( $meta );
});


return $acp;
