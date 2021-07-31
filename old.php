<?php
use function MongoDB\BSON\toJSON;
    class WISP_Module extends ServerModule
    {
        public $module = "WISP";
        public $module_type = 'Server';
        function wisp_GetServerID(array $params, $raw = false) {
            $serverResult = wisp_call('servers/external/' . $data['serviceid'], [], 'GET',true);
            if($serverResult['status_code'] === 200) {
                if($raw) return $serverResult;
                else return $serverResult['attributes']['id'];
            } else if($serverResult['status_code'] === 500) {
                throw new Exception('Failed to get server, panel errored. Check panel logs for more info.');
            }
        }
        public function GetHostname()
        {
            if (Validation::NSCheck($this->server["name"])) {
                $hostname = $this->server["name"];
            } else {
                $hostname = $this->server["ip"];
            }
            $hostname =
                ($this->server["secure"] ? "https://" : "http://") . $hostname;
            return rtrim($hostname, "/");
        }
        public function wisp_call($endpoint, array $body = [], $method = "GET", $log = false)
        {
            $url = $this->GetHostname() . "/api/application/" . $endpoint;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($curl, CURLOPT_USERAGENT, "WISP-WISECP");
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_301);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            $headers = [
                "Authorization: Bearer " . $this->server["password"],
                "Accept: Application/vnd.wisp.v1+json",
            ];

            if ($method === "POST" || $method === "PATCH") {
                $jsonData = json_encode($body);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
                array_push($headers, "Content-Type: application/json");
                array_push($headers, "Content-Length: " . strlen($jsonData));
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($curl);
            $responseData = json_decode($response, true);
            $responseData["status_code"] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if($log){
                self::save_log($this->module_type,$this->module,$url,$url,$responseData,'json');
            }
            curl_close($curl);
            return $responseData;
        }
        private $api;
        function __construct($server,$options=[])
        {
            $this->_name = __CLASS__;
            parent::__construct($server,$options);
        }

        protected function define_server_info($server=[])
        {
            /*
            if(!class_exists("SampleApi")) include __DIR__.DS."api.class.php";
            $this->api = new SampleApi(
                $server["name"],
                $server["ip"],
                $server["username"],
                $server["password"],
                $server["access_hash"],
                $server["port"],
                $server["secure"],
                $server["port"]
            );
            */

        }

        public function testConnect(){
            $connect = false;
            try
            {
                $response = $this->wisp_call('locations');
                if($response["status_code"] === 200){
                $connect    = 'OK';  #$this->api->checkConnect();
                }
            }
            catch(Exception $e){
                $this->error = $e->getMessage();
                return false;
            }

            if($connect != 'OK'){
                $this->error = $connect ."Error Code: ". $response["status_code"] . " Check Log for more information";
                return false;
            }
            return true;
        }
        private function getEggs($data=[]){
         array_push($egg,$this->wisp_call('nests/'.$nestID[$key].'/eggs')['attributes']['id']);
            return 1;
        }
        public function config_options($data=[])
        {

            // REWORK THIS IN NEXT UPDATE(to function)
            //get list of locations nests and eggs
            $location = (array)$this->wisp_call('locations')['data'];
            $nests = (array)$this->wisp_call('nests')['data'];
            $egg = [];

            //array of location IDs
            $locationID = [];
            //array of location names/shorts
            $locationName = [];
            //combined array of location IDs and names
            $locationList = [];

            //NESTS
            $nestID = [];
            $nestName = [];
            $nestList = [];

            //EGGS
            $eggID = [];
            $eggName = [];
            $eggList = [];

            foreach($location as $key => $value)
             {
                 array_push($locationID,$value['attributes']['id']);
                 array_push($locationName,$locationID[$key]. " | " .$value['attributes']['short']);
             }
             foreach($nests as $key => $value)
             {
                 array_push($nestID,$value['attributes']['id']);
                 array_push($nestName,$nestID[$key]. " | " .$value['attributes']['name']);
                 $egg = $this->wisp_call('nests/'.$nestID[$key].'/eggs')['data'];
                 foreach ($egg as $key1 => $value1)
                 {
                     array_push($eggID,$value1['attributes']['id']);
                     if($nestID[$key] == $data["Nest-ID"])
                     {
                         //makes current nest on top of the list
                         array_unshift($eggName,"(Nest ".$nestID[$key].") " .$eggID[$key1]. " | " .$value1['attributes']['name']);

                     }else{
                         array_push($eggName,"(Nest ".$nestID[$key].") " .$eggID[$key1]. " | " .$value1['attributes']['name']);

                     }

                 }


             }



             $locationList = array_combine($locationID, $locationName);
             $nestList = array_combine($nestID, $nestName);
             $eggList = array_combine($eggID, $eggName);
             //             <<REWORK END>>
            return [
                'Memory'          => [
                    'name'              => "Memory (MB)",
                    'description'       => "The maximum amount of memory allowed for this container. Setting this to 0 will allow unlimited memory in a container.",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["Memory"]) ? $data["Memory"] : "0",
                    'placeholder'       => "insert Memory here",
                ],
                'Swap'          => [
                    'name'              => "Swap (MB)",
                    'description'       => "If you do not want to assign swap space to a server, simply put 0 for the value, or -1 to allow unlimited swap space.",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["Swap"]) ? $data["Swap"] : "0",
                    'placeholder'       => "insert swap here",
                ],
                'CPU'          => [
                    'name'              => "CPU %",
                    'description'       => "If you do not want to limit CPU usage, set the value to 0. To determine a value, take the number of physical cores and multiply it by 100. For example, on a quad core system (4 * 100 = 400) there is 400% available. To limit a server to using half of a single core, you would set the value to 50.",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["CPU"]) ? $data["CPU"] : "0",
                    'placeholder'       => "insert CPU limit here",
                ],
                'DiskIO'          => [
                    'name'              => "Block IO Weight	",
                    'description'       => "Block IO Adjustment number (10-1000).<br>The IO performance of this server relative to other running containers on the system.",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["DiskIO"]) ? $data["DiskIO"] : "500",
                    'placeholder'       => "insert Block IO Weight limit here",
                ],
                'Disk-space'          => [
                    'name'              => "Disk Space (MB)",
                    'description'       => "This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available. Set to 0 to allow unlimited disk usage.",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["Disk-space"]) ? $data["Disk-space"] : "0",
                    'placeholder'       => "insert Disk Space here",
                ],
                'Location-ID'          => [
                    'name'              => "Location ID",
                    'description'       => "ID of the Location to automatically deploy to.",
                    'type'              => "dropdown",
                    'options'           => $locationList,
                    'value'             => isset($data["Location-ID"]) ? $data["Location-ID"] : "1",
                ],
                'Nest-ID'          => [
                    'name'              => "Nest ID",
                    'description'       => "ID of the Nest for the server to use.",
                    'type'              => "dropdown",
                    'options'           => $nestList,
                    'value'             => isset($data["Nest-ID"]) ? $data["Nest-ID"] : "1",
                ],
                'Egg-ID'          => [
                    'name'              => "Egg ID",
                    'description'       => " ID of the Egg for the server to use.",
                    'type'              => "dropdown",
                    'options'           =>  $eggList,
                    'value'             => isset($data["Egg-ID"]) ? $data["Egg-ID"] : "1",
                ],
                'portrange'          => [
                    'name'              => "Port Range",
                    'description'       => "Port ranges seperated by comma to assign to the server (Example: 25565-25570,25580-25590) (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["portrange"]) ? $data["portrange"] : "",
                    'placeholder'       => "",
                ],
                'portarray'          => [
                    'name'              => "Port Array",
                    'description'       => "COMES IN NEXT UPDATE(does nothing)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => "",
                    'placeholder'       => "",
                ],
                'startup'          => [
                    'name'              => "Startup",
                    'description'       => "Custom startup command to assign to the created server (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["startup"]) ? $data["startup"] : "",
                    'placeholder'       => "",
                ],
                'image'          => [
                    'name'              => "Image",
                    'description'       => "Custom Docker image to assign to the created server (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["image"]) ? $data["image"] : "",
                    'placeholder'       => "",
                ],
                'databases'          => [
                    'name'              => "Databases",
                    'description'       => "Client will be able to create this amount of databases for their server (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["databases"]) ? $data["databases"] : "",
                    'placeholder'       => "",
                ],
                'backup'          => [
                    'name'              => "Backup Size Limit",
                    'description'       => "Amount in megabytes the server can use for backups (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["backup"]) ? $data["backup"] : "",
                    'placeholder'       => "",
                ],
                'servername'          => [
                    'name'              => "Server Name",
                    'description'       => "The name of the server as shown on the panel (optional)",
                    'type'              => "text",
                    'width'             => "50",
                    'value'             => isset($data["servername"]) ? $data["servername"] : "",
                    'placeholder'       => "",
                ],
                'oom'          => [
                    'name'              => "Disable OOM Killer",
                    'description'       => "Should the Out Of Memory Killer be disabled?(optional)",
                    'type'              => "approval",
                    'checked'           => isset($data["oom"]) && $data["oom"] ? true : false,
                ],
                'dedIP'          => [
                    'name'              => "Dedicated IP",
                    'description'       => "Assign dedicated ip to the server (optional)",
                    'type'              => "approval",
                    'checked'           => isset($data["dedIP"]) && $data["dedIP"] ? true : false,
                ],
            ];
        }

        public function create(array $order_options=[])
        {

            try
            {
                /*
                 * $order_options or $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */

               $serviceid = $this->["entity_id_name"];
               $serverId = wisp_GetServerID($params);
               if(isset($serverId)) throw new Exception('Failed to create server because it is already created.');
                $userResult = wisp_call('users?search=' . urlencode($this->user["email"]));
                if($userResult['meta']['pagination']['total'] === 0) {
                     $userResult = wisp_call('users',
                    'email' => $this->user["email"],
                    'first_name' => $this->user["name"],
                    'last_name' => $this->user["surname"],
                    'external_id' => $this->user["id"],
                ], 'POST',true);
                }

            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Failed to create server, something went wrong.";
            */
            if(substr($result,0,2) == 'OK')
                return [
                    'ip'                => '192.168.1.1',
                    'assigned_ips'      => ['192.168.1.2','192.168.1.3'],
                    'login' => [
                        'username' => 'root',
                        'password' => 'test123',
                    ],
                    'config' => [$this->entity_id_name => substr($result,3)],
                ];
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function suspend()
        {
            try
            {
                /*
                 * $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */
                $result             = "OK"; #$this->api->suspend();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function unsuspend()
        {
            try
            {
                /*
                 * $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */
                $result = "OK"; #$this->api->unsuspend();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function terminate()
        {
            try
            {
                /*
                 * $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */
                $result = "OK"; # $this->api->terminate();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function apply_updowngrade($params=[])
        {
            /*
            parent::udgrade(); // You can use it to delete the previous virtual server and create the virtual server with new features.
            */

            try
            {
                /*
                 * $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */
                $result             = "OK"; #$this->api->upgrade();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
                return true;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function get_status()
        {
            try
            {
                /*
                 * $this->order["options"]
                * for parameters: https://docs.wisecp.com/en/kb/parameters
                * Here are the codes to be sent to the API...
                */
                $result = "running"; # $this->api->status();
                //$result = "stopped"; # $this->api->status();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'running')
                return true;
            elseif($result == 'stopped')
                return false;
            else
            {
                $this->error = $result;
                return false;
            }
        }

        public function list_vps()
        {

            $list = [];

            try
            {
                $data = [
                    [
                        'hostname'      => "server2e4.example.com",
                        'ip'            => '192.168.1.1',
                        'assigned_ips'  => ['192.168.1.2','192.168.1.3','192.168.1.4'],
                        'username'      => "root",

                    ],
                ]; #$this->api->accounts();
            }
            catch(Exception $e)
            {
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                return false;
            }

            if($data['status'] != 'OK'){
                $this->error = $data['message'];
                return false;
            }

            if(isset($data['result']) && $data['result']){
                foreach($data['result'] AS $account){
                    $hostname       = $account['hostname'];
                    $primary_ip     = $account["ip"];

                    $list[$hostname."|".$primary_ip] = [
                        'cdate'             => $data["created"], # Format: Y-m-d
                        'hostname'          => $hostname,
                        'ip'                => $primary_ip,
                        'assigned_ips'      => explode(",",$data["ip_addresses"]),
                        'login'             => ['username' => $data["username"]],
                        'sync_terms'        => self::sync_terms($primary_ip,$hostname),
                        'access_data'       => [$this->entity_id_name => $data["vps_id"]],
                    ];
                }
            }
            return $list;
        }

        public function clientArea()
        {
            $content    = '';
            $_page      = $this->page;
            $_data      = [];

            if(!$_page) $_page = 'home';

            if($_page == "home")
            {
                $_data = ['test1' => 'hello world', 'test2' => 'sample var'];
            }

            $content .= $this->clientArea_buttons_output();

            $content .= $this->get_page('clientArea-'.$_page,$_data);
            return  $content;
        }

        public function clientArea_buttons()
        {
            $buttons    = [];

            if($this->page && $this->page != "home")
            {
                $buttons['home'] = [
                    'text' => $this->lang["turn-back"],
                    'type' => 'page-loader',
                ];
            }
            else
            {
                $status     = 'running'; # With API, status information is obtained from the server.

                if($status == 'running')
                {
                    $buttons['restart']     = [
                        'text'  => $this->lang["restart"],
                        'type'  => 'transaction',
                    ];

                    $buttons['reboot']      = [
                        'text'  => $this->lang["reboot"],
                        'type'  => 'transaction',
                    ];
                    $buttons['stop']      = [
                        'text'  => $this->lang["stop"],
                        'type'  => 'transaction',
                    ];
                }
                elseif($status == 'stop')
                {
                    $buttons['start']      = [
                        'text'  => $this->lang["start"],
                        'type'  => 'transaction',
                    ];
                }

                $buttons['change-password'] = [
                    'text'  => $this->lang["change-password"],
                    'type'  => 'page-loader',
                ];

                $buttons['another-page'] = [
                    'text'  => 'Another Page',
                    'type'  => 'page',
                ];

                $buttons['custom_function'] = [
                    'text'  => 'Open Function',
                    'type'  => 'function',
                    'target_blank' => true,
                ];

                $buttons['another-link'] = [
                    'text'      => 'Another link',
                    'type'      => 'link',
                    'url'       => 'https://www.google.com',
                    'target_blank' => true,
                ];
            }

            return $buttons;
        }

        public function use_clientArea_SingleSignOn()
        {
            $api_result     = 'OK|bmd5d0p384ax7t26zr9wlwo4f62cf8g6z0ld';

            if(substr($api_result,0,2) != 'OK'){
                echo "An error has occurred, unable to access.";
                return false;
            }

            $token          = substr($api_result,2);
            $url            = 'https://modulewebsite.com/api/access/'.$token;

            Utility::redirect($url);

            echo "Redirecting...";
        }

        public function use_adminArea_SingleSignOn()
        {
            $api_result     = 'OK|bmd5d0p384ax7t26zr9wlwo4f62cf8g6z0ld';

            if(substr($api_result,0,2) != 'OK'){
                echo "An error has occurred, unable to access.";
                return false;
            }

            $token          = substr($api_result,2);
            $url            = 'https://modulewebsite.com/api/access/'.$token;

            Utility::redirect($url);

            echo "Redirecting...";
        }

        public function use_adminArea_root_SingleSignOn()
        {
            $api_result     = 'OK|bmd5d0p384ax7t26zr9wlwo4f62cf8g6z0ld';

            if(substr($api_result,0,2) != 'OK'){
                echo "An error has occurred, unable to access.";
                return false;
            }

            $token          = substr($api_result,2);
            $url            = 'https://modulewebsite.com/api/access/'.$token;

            Utility::redirect($url);

            echo "Redirecting...";
        }


        public function use_clientArea_change_password()
        {
            if(!Filter::isPOST()) return false;
            $password       = Filter::init("POST/password","password");

            if(!$password){
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->lang["error"],
                ]);
                return false;
            }

            $result = 'OK';  /* API request result */
            if($result != 'OK'){
                $this->error = $result;
                return false;
            }

            $password_e         = $this->encode_str($password);

            if(!isset($this->options["login"])) $this->options["login"] = [];

            $this->options["login"]["password"] = $password_e;

            # users_products.options save data
            Orders::set($this->order["id"],['options' => Utility::jencode($this->options)]);

            // Save Action Log
            $u_data     = UserManager::LoginData("member");
            $user_id    = $u_data["id"];
            User::addAction($user_id,'transaction','The server password for service #'.$this->order["id"].' has been changed.');
            Orders::add_history($user_id,$this->order["id"],'server-order-password-changed');

            // Save Module Log
            self::save_log('Servers',$this->_name,__FUNCTION__,['order' => $this->order],['api_result' => $result]);

            echo Utility::jencode([
                'status'    => "successful",
                'message'   => $this->lang["successful"],
                'timeRedirect' => ['url' => $this->area_link, 'duration' => 3000],
            ]);

            return true;
        }

        public function use_clientArea_start()
        {
            if($this->start()){
                $u_data     = UserManager::LoginData('member');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "start" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-start');
                return true;
            }
            return false;
        }
        public function use_clientArea_stop()
        {
            if($this->stop()){
                $u_data     = UserManager::LoginData('member');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "stop" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-stop');
                return true;
            }
            return false;
        }
        public function use_clientArea_restart()
        {
            if($this->restart()){
                $u_data     = UserManager::LoginData('member');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "restart" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-restart');
                return true;
            }
            return false;
        }
        public function use_clientArea_reboot()
        {
            if($this->reboot()){
                $u_data     = UserManager::LoginData('member');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "reboot" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-reboot');
                return true;
            }
            return false;
        }

        public function use_clientArea_custom_function()
        {
            if(Filter::POST("var2"))
            {
                echo  Utility::jencode([
                    'status' => "successful",
                    'message' => 'Successful message',
                ]);
            }
            else
            {
                echo "Content Here...";
            }

            return true;
        }

        public function use_adminArea_custom_function()
        {
            if(Filter::POST("var2"))
            {
                echo  Utility::jencode([
                    'status' => "successful",
                    'message' => 'Successful message',
                ]);
            }
            else
            {
                echo "Content Here...";
            }

            return true;
        }

        public function adminArea_service_fields(){
            $c_info                 = $this->options["creation_info"];
            $field1                 = isset($c_info["field1"]) ? $c_info["field1"] : NULL;
            $field2                 = isset($c_info["field2"]) ? $c_info["field2"] : NULL;

            return [
                'field1'                => [
                    'name'              => "Field 1",
                    'description'       => "Field 1 Description",
                    'type'              => "text",
                    'value'             => $field1,
                    'placeholder'       => "sample placeholder",
                ],
                'field2'                => [
                    'wrap_width'        => 100,
                    'name'              => "Field 2",
                    'type'              => "output",
                    'value'             => '<input type="text" name="creation_info[field2]" value="'.$field2.'">',
                ],
            ];
        }


        public function save_adminArea_service_fields($data=[])
        {
            $login          = $this->options["login"];
            $c_info         = $data['creation_info'];
            $config         = $data['config'];

            if(isset($c_info["new_password"]) && $c_info["new_password"] != '')
            {
                $new_password = $c_info["new_password"];

                unset($c_info["new_password"]);

                if(strlen($new_password) < 5)
                {
                    $this->error = 'Password is too short!';
                    return false;
                }
                /*
                *  Place the codes to be transmitted to the api here.
                */

                $login["password"] = $this->encode_str($new_password);
            }

            return [
                'creation_info'     => $c_info,
                'config'            => $config,
                'login'             => $login,
            ];
        }

        public function adminArea_buttons()
        {
            $buttons = [];

            $status     = 'running'; # With API, status information is obtained from the server.

            if($status == 'running')
            {
                $buttons['restart']     = [
                    'text'  => $this->lang["restart"],
                    'type'  => 'transaction',
                ];

                $buttons['reboot']      = [
                    'text'  => $this->lang["reboot"],
                    'type'  => 'transaction',
                ];
                $buttons['stop']      = [
                    'text'  => $this->lang["stop"],
                    'type'  => 'transaction',
                ];
            }
            elseif($status == 'stop')
            {
                $buttons['start']      = [
                    'text'  => $this->lang["start"],
                    'type'  => 'transaction',
                ];
            }

            $buttons['custom_function'] = [
                'text'  => 'Open Function',
                'type'  => 'function',
                'target_blank' => true,
            ];

            $buttons['another-link'] = [
                'text'      => 'Another link',
                'type'      => 'link',
                'url'       => 'https://www.google.com',
                'target_blank' => true,
            ];

            return $buttons;
        }

        public function use_adminArea_start()
        {
            $this->area_link .= '?content=automation';
            if($this->start()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "start" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-start');
                return true;
            }
            return false;
        }
        public function use_adminArea_stop()
        {
            $this->area_link .= '?content=automation';
            if($this->stop()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "stop" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-stop');
                return true;
            }
            return false;
        }
        public function use_adminArea_restart()
        {
            $this->area_link .= '?content=automation';
            if($this->restart()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "restart" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-restart');
                return true;
            }
            return false;
        }
        public function use_adminArea_reboot()
        {
            $this->area_link .= '?content=automation';
            if($this->reboot()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','The command "reboot" has been sent for service #'.$this->order["id"].' on the module.');
                Orders::add_history($user_id,$this->order["id"],'server-order-reboot');
                return true;
            }
            return false;
        }


        public function start()
        {
            try
            {
                $result             = "OK"; #$this->api->start();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
            {
                echo Utility::jencode([
                    'status' => "successful",
                    'message' => $this->lang["successful"],
                    'timeRedirect' => [
                        'url' => $this->area_link,
                        'duration' => 1000
                    ],
                ]);
                return true;
            }
            else
            {
                $this->error = $result;
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }

        }

        public function stop()
        {
            try
            {
                $result             = "OK"; #$this->api->stop();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
            {
                echo Utility::jencode([
                    'status' => "successful",
                    'message' => $this->lang["successful"],
                    'timeRedirect' => [
                        'url' => $this->area_link,
                        'duration' => 1000
                    ],
                ]);
                return true;
            }
            else
            {
                $this->error = $result;
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
        }

        public function restart()
        {
            try
            {
                $result             = "OK"; #$this->api->restart();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
            {
                echo Utility::jencode([
                    'status' => "successful",
                    'message' => $this->lang["successful"],
                    'timeRedirect' => [
                        'url' => $this->area_link,
                        'duration' => 1000
                    ],
                ]);
                return true;
            }
            else
            {
                $this->error = $result;
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }

        }

        public function reboot()
        {
            try
            {
                $result             = "OK"; #$this->api->reboot();
            }
            catch (Exception $e){
                $this->error = $e->getMessage();
                self::save_log(
                    'Servers',
                    $this->_name,
                    __FUNCTION__,
                    ['order' => $this->order],
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
            /*
            * Error Result:
            * $result             = "Error Message";
            */

            if($result == 'OK')
            {
                echo Utility::jencode([
                    'status' => "successful",
                    'message' => $this->lang["successful"],
                    'timeRedirect' => [
                        'url' => $this->area_link,
                        'duration' => 1000
                    ],
                ]);
                return true;
            }
            else
            {
                $this->error = $result;
                echo Utility::jencode([
                    'status' => "error",
                    'message' => $this->error,
                ]);
                return false;
            }
        }

    }
