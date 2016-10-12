<?php
/* Access Control Point Functions */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;


/**
 * Return the list of available entities from the table entities
 */
function getAvailableEntities( Application $app ) {

    $entitiesList = array();
    $sql = "SELECT entity FROM entities WHERE active > 0";
    $res = $app['db']->fetchAll($sql);

    foreach( $res as $v )
      $entitiesList[] = $v['entity'];

    return $entitiesList;
}

/**
 * Return false if entityType is defined in the parameters file
 * Ohterwise it returns an array with an error message
 */
function invalidEntityType( Application $app, $entityType ) {

    if ( ! in_array( $entityType, getAvailableEntities( $app ) ) ) {
        // If entity type doesn't exists, return an error
        $error["code"]    = 2;
        $error["message"] = "EntityType unavailable";
        $error["fields"]  = $entityType;
        return $error;
    }
    return false;
}
