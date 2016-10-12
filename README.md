# Access Control Point API Implementation

This project is an implementation in Silex/PHP of the ACP API defined in the RISIS Project.


## Prerequisites

* Git : https://git-scm.com/
* Composer : https://getcomposer.org/
* A Web server, like Apache : https://httpd.apache.org/
* MySQL : http://www-fr.mysql.com/


## Installation

1. First git clone the project
2. Make your Web server point on web directory of the project
3. Install dependecies by running composer in the root directory of the project
composer update
4. Create configuration file parameters.json from parameters.json.dist in config directory
5. Execute bdd_acp.sql sql script on the database of the project
6. Populate your DB with data. To make experiments, a sample of dummy data is available in the directory scripts_sql. Play the acp_dummy_data.sql script on your database to add his content.


## Configuration

The file parameters.json.dist contains a default configuration. You must adapt the configuration to your environment by copying file parameters.json.dist to parameters.json

* debug : true/false, to activate debugging information from silex
* monolog : configuration of the log file
* db.options : Database access parameters
* default_limit : The default limit (number of record) for queries that retrieve multiple instances of an entity
* max_limit : The maximum limit (number of record) for queries that retrieve multiple instances of an entity, even if the limit value in the request is greater.
* metadata : Meta information about the dataset
* trustedProxies : Not yet used
* accessToken : An object that contains properties like "owner":"token". You can define here access Token. The key name (owner) just here to remember who is the owner of the token. To permit access without token, add a property with an empty token.


## Database Model

There is two tables needed for metadata :

* dictionnary table : for each kind of entity, contains the list of fields and their description
 * entityType : the entity Type
 * name : the name of the field
 * description : the description of the field
 * hasEntityType : if the field defines a relation and his content is another entity, then hasEntityType defines the related entityType, otherwise this field is null

* entities table : contains the description of available entitities
 * entity : the entity type as defined in the parameters file
 * description : the description of the entity
 * active : 1 if this entities is available on the access control point, otherwise 0

For each entity type, there must be a table (or a view) from which the name is the exact entity name. Each table must contain a primary field named id.

* `<entitiy name>` table
 * id : the primary key
 * *record1* : first field of the entity
 * *record2* : ...

If you want to enable / disable one entity, you need to use the active field of the entities table. 1 for enabled, 0 for disabled.


## Migration

To migrate an installation, check if some sql scripts are available, from your actual version to the latest version, and play them.

For example, to migrate from v1.0 to v1.1, you need to play the SQL script `update_from_v1_0_to_v1_1.sql`


## Model example

For example, if you have two entity type : Person and Institution

In the parameters.json file, you will have a line like :
`"entityType" : [ "Person", "Institution" ]`

The dictionnary table will contain the following records :
* Person / name / the name of the person / null
* Person / institution / the institution of the person / Institution
* Institution / name / the name of the institution / null

The entities table will contain the following records :
* Person / A list of person / 1
* Institution / A list of institution / 1

The Person table will contain the following records :
* id / 124AZ (a unique id)
* name / Gandalf
* institution / TS564 (the id of the related institution in the Institution table)

The Institution table will contain the following records :
* id / TS564
* name / The Shire


## API Reference

You can find more information about the API on this page : <http://acp.api.risis.eu>

The API is defined with a swagger yaml file here : <https://github.com/risis-eu/acp-api-documentation>


## Usage example

Get metadata about the dataset :

`http://my-acp.com/v1.1/metadata?accessToken=<AvailableToken>&offset=<2>&limit=<3>`

Get the list of available entity types :

`http://my-acp.com/v1.1/entityTypes?accessToken=<AvailableToken>`

Get a specific instance of an entity :

`http://my-acp.com/v1.1/entities/<Entityname>/<ID>?accessToken=<AvailableToken>`

Get all instances of an entity (in the limit of max_limit records) :

`http://my-acp.com/v1.1/entities/<Entityname>?accessToken=<AvailableToken>&offset=<0>&limit=<3>`


## Release History

v1.0 - 2016/10/01 First version
v1.1 - 2016/10/12 v1.1 of API


## Contributors

[Guillaume Orsal - Développeur Web](https://www.orsal.fr "CV Ingénieur informatique indépendant") at [Cortext Platform](http://www.cortext.net) / [INRA](http://www.inra.fr) / [LISIS](http://www.umr-lisis.org) / [RISIS](http://risis.eu)


## License

To be completed
