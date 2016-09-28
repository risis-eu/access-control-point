<?php
/* Access Control Point to Nano */
/* V1.0 of the ACP API */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

// Initialisation of controller
$acp = $app['controllers_factory'];

/**
 * Get dictionnary of properties of entities from DB
 */
function getPropertiesDescription( Application $app, $entityType ) {

    /* entityType is already checked on the route */
    $sql = "SELECT name, description, hasEntityType FROM dictionnary WHERE entityType=?";
    return $app['db']->fetchAll($sql, array( $entityType ) );
}


/**
 * Return false if entityType is defined in the parameters file
 * Ohterwise it returns an array with an error message
 */
function invalidEntityType( Application $app, $entityType ) {
    if ( ! in_array( $entityType, $app['parameters']['metadata']['entityType'] ) ) {
        // If entity type doesn't exists, return an error
        $error["code"]    = 2;
        $error["message"] = "EntityType unavailable";
        $error["fields"]  = $entityType;
        return $error;
    }
    return false;
}


/**
 * Get the number of entities for a given entityType
 */
$acp->get('/entities/{entityType}/count', function(Application $app, Request $request, $entityType) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities count (".$entityType.")" );

    // Validation of entityType
    if ( $error = invalidEntityType( $app, $entityType ) ) { return $app->json( $error, 404 ); }

    $sql = "SELECT COUNT(*) AS nb FROM " . $entityType ;

    $res = $app['db']->fetchAll( $sql );

    $response["total"] = (int)$res[0]["nb"];
    $response["max_per_page"] = $app['parameters']['db.options']['max_limit'];

    return $app->json( $response );
});


/**
 * Retrieve a specific id of a given entityType
 * id is a string that corresponds to the primary key of the entityType
 */
$acp->get('/entities/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType."/".$id.")" );

    // Validation of entityType
    if ( $error = invalidEntityType( $app, $entityType ) ) { return $app->json( $error, 404 ); }

    // Some metadata
    $entity["dataset"]     = $app['parameters']['metadata']['dataset'];
    $entity["version"]     = $app['parameters']['metadata']['version'];
    $entity["entity_type"] = $entityType;
    $entity["offset"] = 0; // In case of a specific id, this field is forced
    $entity["limit"] = 1; // In case of a specific id, this field is forced

    // Get dictionnary of properties
    $entity["property_description"] = getPropertiesDescription( $app, $entityType );

    // Each entity table (or view) must have an id field, that is a primary key
    $sql = "SELECT * FROM " . $entityType . " WHERE id=?";

    $results = $app['db']->fetchAll($sql, array( $id ) );

    // Preparing the results
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

    // Error if instance id doesn't exist
    if ( count($entity["instances"]) == 0 ) {
        $error["code"]    = 3;
        $error["message"] = "No instance found";
        $error["field"]   = $entityType . (isset($id)?" $id":"");
        return $app->json( $error, 404 );
    }

    return $app->json($entity);

});

/**
 * Get all entities of a given entityType, in the limit of the max number of entities in one time as defined in config
 */
$acp->get('/entities/{entityType}', function(Application $app, Request $request, $entityType ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entities (".$entityType.")" );

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

    // Get the dictionnary
    $entity["property_description"] = getPropertiesDescription( $app, $entityType );

    $sql = "SELECT * FROM " . $entityType . " LIMIT " . $offset . "," . $limit;

    $results = $app['db']->fetchAll( $sql );

    // Preparation des resultats
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

    // No instance found ? then error.
    if ( count($entity["instances"]) == 0 ) {
        $error["code"]    = 3;
        $error["message"] = "No instance found";
        $error["field"]   = $entityType . (isset($id)?" $id":"");
        return $app->json( $error, 404 );
    }

    return $app->json($entity);
})->bind('entities');


/**
 * Get the list of available entity types. List is defined in config, so it's easy to deactivate one type.
 */
$acp->get('/entityTypes', function(Application $app, Request $request) {
    // Log of action
    $app['monolog']->addInfo( "EntityTypes" );

    // Get the available entity types
    $entitiesList = $app['parameters']['metadata']['entityType'];

    // Construction of information for each entity type
    foreach( $entitiesList as $oneEntity ) {
        // Creation of one entity type
        $entity['name'] = $oneEntity;
        $entity['path'] = "http://".$_SERVER['HTTP_HOST']."/v1.0/entities/".$oneEntity;

        // Get information about entity in the db, so it's easy for dataset owner to modify or declare entity types
        $sql = "SELECT description FROM entities WHERE entity=?";
        $result = $app['db']->fetchAll( $sql, array( $oneEntity ) );

        $entity['description'] = $result[0]['description'];
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
