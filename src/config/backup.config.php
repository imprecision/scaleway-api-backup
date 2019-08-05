<?php
/**
 * backup.config.php
 *
 * @package    apiScaleway
 * @subpackage API
 * @author     Jonathan <skutter@imprecision.net>
 * @version    v1.0.0 (25/06/2019)
 * @copyright  Copyright (c) 2019, Jonathan
 */

namespace apiScaleway;

/**
 * Config
 * 
 * @var array Assoc array containing core config necessary 
 */

define('CONFIG', [
    "zone"         => "fr-par-1", // fr-par-1, nl-ams-1, etc
    "token"        => "xxx-xxx-xxx-xxx", // generate a unique token from Scaleway account credentials page
    "organization" => "xxx-xxx-xxx-xxx", // get from  from Scaleway account credentials page
    "backup" => [
        "snapshots" => [ // Comment section out / delete if no "snapshots" backups required
            [
                "id"    => "xxx-your-scaleway-volume-id",
                "name"  => "xxx-a-good-name",
                "purge" => 2, // -1 no purging, >=0 keep previous n backups
            ],
        ],
        "servers" => [ // Comment section out / delete if no "servers" backups required (images)
            [
                "id"    => "xxx-your-scaleway-server-id",
                "name"  => "xxx-imp-appsrv-serverbackup",
                "purge" => 1,
            ],
        ],
    ],
]);
