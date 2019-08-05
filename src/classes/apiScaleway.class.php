<?php
/**
 * apiScaleway.class.php
 *
 * @package    apiScaleway
 * @subpackage API
 * @author     Jonathan <skutter@imprecision.net>
 * @version    v1.0.0 (25/06/2019)
 * @copyright  Copyright (c) 2019, Jonathan
 */

namespace apiScaleway;

/**
 * apiScaleway
 * 
 * Simple API gateway to Scaleway cloud hosting services. Primariliy to allow remote backup functionality.
 */
class apiScaleway {

    /**
     * Timezone
     * 
     * @var string
     */
    private $timezone = 'Europe/Paris'; // UTC might be better

    /**
     * Activity log messages
     *
     * @var array
     */
    private $log_msgs = [];

    /**
     * Scaleway API root URL
     *
     * @var string
     */
    private $url_root = 'https://api.scaleway.com/instance/v1/zones/';

    /**
     * Scaleway API datacentre zone, e.g. fr-par-1, nl-ams-1
     * 
     * Required
     * See apiScaleway::set_zone()
     *
     * @var string
     */
    private $url_zone = '';

    /**
     * Scaleway user account organization ID
     * 
     * Optional, if set is used to filter returned images, snapshots, etc
     * @see apiScaleway::set_org()
     *
     * @var string
     */
    private $url_org = '';

    /**
     * Scaleway user server/instance ID
     * 
     * Required - if applying actions to specific server, e.g. backups
     * @see apiScaleway::set_server()
     *
     * @var string
     */
    private $url_server = '';

    /**
     * Scaleway user volume ID
     * 
     * Required - if applying actions to specific volume, e.g. image or snapshot
     * @see apiScaleway::set_volume()
     *
     * @var string
     */
    private $url_vol = '';

    /**
     * Scaleway API content-type
     *
     * @var string
     */
    private $url_header_ctype = 'Content-Type: application/json';

    /**
     * Scaleway user account access token
     * 
     * Required
     * @see apiScaleway::set_token()
     *
     * @var string
     */
    private $url_header_token = '';

    /**
    * Class constructor method
    *
    * Set timezone and error reporting
    *
    * @return void
    */
    public function __construct() {
        date_default_timezone_set($this->timezone);
        libxml_use_internal_errors(true);
    }

    /**
    * Set datacentre zone
    *
    * Set Scaleway datacentre zone
    *
    * @param string $val Scaleway zone ID, e.g. fr-par-1, nl-ams-1, etc
    * @return object
    */
    public function set_zone(string $val) {
        $this->url_zone = $val;
        return $this;
    }

    /**
     * Set account API token
     *
     * @param string $val
     * @return object
     */
    public function set_token(string $val) {
        $this->url_header_token = 'X-Auth-Token: ' . $val;
        return $this;
    }

    /**
     * Set "organization" ID
     * 
     * Used for filtering to specific account images, creating images in correct org, etc
     *
     * @param string $val
     * @return object
     */
    public function set_org(string $val) {
        $this->url_org = $val;
        return $this;
    }

    /**
     * Set server ID
     * 
     * Used for creating backups, etc., from the appropriate server
     *
     * @param string $val
     * @return object
     */
    public function set_server(string $val) {
        $this->url_server = $val;
        return $this;
    }

    /**
     * Set volume ID
     * 
     * Used for creating images, etc., from the appropriate volume
     *
     * @param string $val
     * @return object
     */
    public function set_volume(string $val) {
        $this->url_vol = $val;
        return $this;
    }

    /**
     * Record an activity log entry
     *
     * @param string $msg
     * @return object
     */
    private function log(string $msg) {
        $this->log_msgs[] = '[' . date('c') . ']' . $msg;
        return $this;
    }

    /**
     * Get activity log messages
     * 
     * Get list of activity log messages
     *
     * @return array
     */
    public function get_log() {
        return $this->log_msgs;
    }

    /**
     * Get URL
     * 
     * Get URL via curl
     *
     * @param string $url URL to connect to
     * @param string $post_data (optional) POST data as string or assoc array
     * @param string $type POST/DELETE/GET/PUT
     * @return array [HTTP status code, body content], e.g. [200, {some JSON here...}]
     */
    private function get_url(string $url, $post_data = '', string $type = '') {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        if (strlen($type)) {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
        }
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($c, CURLOPT_TIMEOUT, 30);
        if ((\is_array($post_data) && count($post_data)) || (\is_string($post_data) && strlen($post_data))) {
            curl_setopt($c, CURLOPT_POSTFIELDS, $post_data);
        }

        curl_setopt($c, CURLOPT_HTTPHEADER, [$this->url_header_ctype, $this->url_header_token]);

        $this->log("[Info]\tcurl\tconnecting\t" . $url);

        $response = curl_exec($c);
        $httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if (curl_exec($c) === false) {
            $this->log("[Error]\tcurl\t" . curl_error($c));
        }

        curl_close($c);

        $this->log("[Info]\tcurl\tfinished\t" . $httpcode);

        return [$httpcode, $response];
    }

    /**
     * Parse array
     *
     * Turn an array into a JSON string
     * 
     * @param [type] $data Data to encode to JSON
     * @return string JSON
     */
    private function parse($data) {
        $set = json_decode($data, true);
        return $set;
    }

    /**
     * Action a change
     * 
     * Given action to change - connect to Scaleway API and send
     *
     * @param string $url API URL to use
     * @param string $actions_json JSON action instrcutions (see Scaleway API docs)
     * @param string $type POST/DELETE/GET/PUT
     * @return array Assoc array of results from API
     */
    private function action(string $url, string $actions_json = '', string $type = '') {
        list($http_code, $raw) = $this->get_url($url, $actions_json, $type);

        $results = [];

        $http_code = (string)$http_code;
        if ($http_code{0} == 2) {
            // 200 range response - OK
            $results[$http_code] = $raw;
        } else {
            $this->log("[Error]\t" . $type . "\tAction failed\t" . $http_code . $actions_json . "\t" . $raw);
        }

        return $results;
    }

    /**
     * List results
     * 
     * Given type, get and return parsed API results, e.g. return a list of servers in account
     *
     * @param string $type Scaleway API type to list, e.g. servers/volumes/images/snapshots
     * @return array Assoc array of results from API
     */
    private function lists(string $type) {
        $url = $this->url_root . $this->url_zone . '/' . $type;
        $raw = $this->action($url);
        $results = [];
        if (count($raw)) {
            $raw_data = array_values($raw);
            $items = $this->parse($raw_data[0]);
            foreach ($items[$type] as $item) {
                if (strlen($this->url_org) && ($item['organization'] != $this->url_org)) {
                    continue;
                }
                $results[$item['id']] = $item['name'];
            }
        }
        return $results;
    }

    /**
     * List servers
     *
     * @return array List of servers in account (matching organization, if set)
     */
    public function list_servers() {
        return $this->lists('servers');
    }

    /**
     * List volumes
     * 
     * Volumes are mountable drives to any instance
     *
     * @return array List of volumes in account (matching organization, if set)
     */
    public function list_volumes() {
        return $this->lists('volumes');
    }

    /**
     * List images
     * 
     * Images are mirrors of any volume
     *
     * @return array List of images in account (matching organization, if set)
     */
    public function list_images() {
        return $this->lists('images');
    }

    /**
     * List snapshots
     * 
     * Snapshots are a snapshot in time of any volume/image
     *
     * @return array List of snapshots in account (matching organization, if set)
     */
    public function list_snapshots() {
        return $this->lists('snapshots');
    }

    /**
     * Create backup
     * 
     * Create a full backup of a server/instance, i.e. all volumes attached to instance
     *
     * @param string $name Optional, name of backup to create, if set then Scaleway image will use this name
     * @return array Assoc array of results from API
     */
    public function create_backup(string $name = '') {
        $url = $this->url_root . $this->url_zone . '/servers/' . $this->url_server . '/action';
        $jsn = ["action" => "backup"];
        if (strlen($name)) {
            $jsn["name"] = $name;
        }
        return $this->action($url, json_encode($jsn), 'POST');
    }

    /**
     * Create snapshot
     * 
     * Create a snapshot of a volume
     * Note set_volume() must be set with a valid volume ID
     *
     * @param string $name Name to set snapshot to
     * @return array Assoc array of results from API
     */
    public function create_snapshot(string $name) {
        $url = $this->url_root . $this->url_zone . '/snapshots';
        return $this->action($url, json_encode(["volume_id"=>$this->url_vol, "organization"=>$this->url_org, "name"=>$name]), 'POST');
    }

    /**
     * Delete snapshot
     * 
     * Delete a snapshot (a backup of a specific volume/image)
     *
     * @param string $snapshot_id Snapshot ID
     * @return array Assoc array of results from API
     */
    public function delete_snapshot(string $snapshot_id) {
        $url = $this->url_root . $this->url_zone . '/snapshots/' . $snapshot_id;
        return $this->action($url, '', 'DELETE');
    }

    /**
     * Delete image
     * 
     * Delete an image (a backup of an instance, including all attached volumes)
     *
     * @param string $image_id Image ID
     * @return array Assoc array of results from API
     */
    public function delete_image(string $image_id) {
        $url = $this->url_root . $this->url_zone . '/images/' . $image_id;
        return $this->action($url, '', 'DELETE');
    }

}
