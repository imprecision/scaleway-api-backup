<?php
/**
 * cli-api-scaleway-backups.php
 *
 * @package    apiScaleway
 * @subpackage API
 * @author     Jonathan <skutter@imprecision.net>
 * @version    v1.0.0 (25/06/2019)
 * @copyright  Copyright (c) 2019, Jonathan
 */

/**
 * Require the API class
 */
require "classes/apiScaleway.class.php";
require "classes/apiScalewayBackupHelper.class.php";
require "config/backup.config.php";

/**
 * Create instance
 */
$sapi = new apiScaleway\apiScalewayBackupHelper();

$output   = [];
$output[] = $sapi->auto_backup(CONFIG);
$output[] = $sapi->get_log();

print_r($output);
