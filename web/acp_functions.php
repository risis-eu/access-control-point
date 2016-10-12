<?php
/* Access Control Point Functions */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;

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
