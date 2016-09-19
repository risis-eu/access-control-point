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

function getPropertiesDescription( $entityType ) {

    if ( $entityType == "Document" ) {

        $property_description["appln_id"] = "Applicant id";
        $property_description["appln_auth"] = "Description of the field";
        $property_description["appln_filing_year"] = "Description of the field";
        $property_description["appln_first_priority_year"] = "Description of the field";
        $property_description["artificial"] = "Description of the field";
        $property_description["appln_nr"] = "Description of the field";
        $property_description["appln_kind"] = "Description of the field";
        $property_description["appln_title"] = "Description of the field";
        $property_description["appln_abstract"] = "Description of the field";

    } else if ( $entityType == "Organisation" ) {

        $property_description["appln_id"] = "Applicant id";
        $property_description["person_id"] = "Description of the field";
        $property_description["org_name_std"] = "Description of the field";
        $property_description["org_type"] = "Description of the field";
        $property_description["ctry_harm"] = "Description of the field";

    } else if ( $entityType == "Country" ) {

        $property_description["ctry_harm"] = "Country";
        $property_description["lib_ctry_harm"] = "Description of the field";
        $property_description["continent"] = "Description of the field";
        $property_description["region"] = "Description of the field";

    } else {

        return false;

    }

    foreach( $property_description as $name => $desc ) {
        $property["name"] = $name;
        $property["description"] = $desc;
        $result[] = $property;
    }

    return $result;
}

$app->get('/v1.0/entity/{entityType}/{id}', function(Application $app, Request $request, $entityType, $id ) {
    // Log of the path access
    $app['monolog']->addInfo( "Entity (".$entityType."/".$id.")" );

    $entity["dataset"] = "nano";
    $entity["version"] = "version_2014_04";
    $entity["entity_type"] = $entityType;
    $entity["property_description"] = getPropertiesDescription( $entityType );

    if ( $entityType == "Document" ) {

        $sql = "SELECT a.appln_id AS id, a.appln_id, a.appln_auth, a.appln_filing_year, a.appln_first_priority_year, a.artificial, a.appln_nr, a.appln_kind, b.appln_title, c.appln_abstract FROM tls201_appln_ifris AS a LEFT JOIN tls202_appln_title_ifris AS b ON a.appln_id = b.appln_id LEFT JOIN tls203_appln_abstr_ifris AS c ON a.appln_id = c.appln_id WHERE a.appln_id=?";

    } else if ( $entityType == "Organisation" ) {

        $sql = "SELECT CONCAT( appln_id, '-', person_id) AS id, appln_id, person_id, org_name_std, org_type, ctry_harm FROM applt_addr_ifris AS a LEFT JOIN nomen_ctry_iso AS b ON a.ctry_final = b.ctry_final WHERE org_type <> 'indiv' AND CONCAT( appln_id, '-', person_id)=?";

    } else if ( $entityType == "Country" ) {

        $sql = "SELECT ctry_harm AS id, ctry_harm, lib_ctry_harm, continent, region FROM nomen_ctry_continent WHERE ctry_harm=?";

    } else {

        // If entity type doesn't exists, return an error
        $error["code"]=2;
        $error["message"]="EntityType unavailable";
        $error["fields"]=$entityType;
        return $app->json( $error );
    }

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

    $entity["dataset"] = "nano";
    $entity["version"] = "version_2014_04";
    $entity["entity_type"] = $entityType;
    $entity["property_description"] = getPropertiesDescription( $entityType );

    if ( $entityType == "Document" ) {

        $sql = "SELECT a.appln_id AS id, a.appln_id, a.appln_auth, a.appln_filing_year, a.appln_first_priority_year, a.artificial, a.appln_nr, a.appln_kind, b.appln_title, c.appln_abstract FROM tls201_appln_ifris AS a LEFT JOIN tls202_appln_title_ifris AS b ON a.appln_id = b.appln_id LEFT JOIN tls203_appln_abstr_ifris AS c ON a.appln_id = c.appln_id";

    } else if ( $entityType == "Organisation" ) {

        $sql = "SELECT CONCAT( appln_id, '-', person_id) AS id, appln_id, person_id, org_name_std, org_type, ctry_harm FROM applt_addr_ifris AS a LEFT JOIN nomen_ctry_iso AS b ON a.ctry_final = b.ctry_final WHERE org_type <> 'indiv'";

    } else if ( $entityType == "Country" ) {

        $sql = "SELECT ctry_harm AS id, ctry_harm, lib_ctry_harm, continent, region FROM nomen_ctry_continent";

    } else {

        // If entity type doesn't exists, return an error
        $error["code"]=2;
        $error["message"]="EntityType unavailable";
        $error["fields"]=$entityType;
        return $app->json( $error );
    }

    $offset = (int)$request->get('offset');
    $limit = (int)$request->get('limit');
    if ( $limit == 0 ) $limit = 10;
    $app['monolog']->addInfo( "offset (".$offset.")" );
    $app['monolog']->addInfo( "limit (".$limit.")" );

    $sql .= " LIMIT ".$offset.",".$limit;

    $results = $app['db']->fetchAll($sql);

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

    if ( $entityType == "Document" ) {

        $sql = "SELECT COUNT(*) AS nb FROM tls201_appln_ifris";

    } else if ( $entityType == "Organisation" ) {

        $sql = "SELECT COUNT(*) AS nb FROM applt_addr_ifris";

    } else if ( $entityType == "Country" ) {

        $sql = "SELECT COUNT(*) AS nb FROM nomen_ctry_continent";

    } else {

        // If entity type doesn't exists, return an error
        $error["code"]=2;
        $error["message"]="EntityType unavailable";
        $error["fields"]=$entityType;
        return $app->json( $error );
    }

    $res = $app['db']->fetchAll( $sql );
    return $app->json( (int)$res[0]["nb"] );
});

$app->get('/v1.0/entityTypes', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "EntityTypes" );

    $entitiesList = array( "Document", "Organisation", "Country" );

    foreach( $entitiesList as $oneEntity ) {
        // Creation of one entity type
        $entity['name']=$oneEntity;
        $entity['path']="http://".$_SERVER['HTTP_HOST']."/v1.0/entities/".$oneEntity;
        $entity['description']="One description of the entity type";
        // Addition of the entity type to the response
        $response[]=$entity;
    }

    // Return of entity types list in json format
    return $app->json( $response );
});

$app->get('/v1.0/metadata', function(Application $app, Request $request) {
    $app['monolog']->addInfo( "Metadata" );
    $meta["title"]="Nano";
    $meta["description"]="Patents on nanotechnologies";
    $meta["creationDate"]="2014-04-01 00:00:00";
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
