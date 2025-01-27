<?php
/*
 * Copyright IBM Corp. 2017,2019
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once('./sag/Sag.php');

/**
 * Class to handle performing basic CRUD operations on a Couch DB.
 * This class uses the Sag library to talk to the Couch DB.
 */
final class Cloudant {
	private static $inst = null;
    private $sag;
		private $db_exists = false;

    public static function Instance() {
        if (self::$inst === null) {
            self::$inst = new Cloudant();
        }
        return self::$inst;
    }

		public function isConnected() {
      error_log("is_connected: $this->db_exists");
			return $this->db_exists;
		}


    private function __construct() {
		#If running locally enter your own host, port, username and password


		$host = getenv('CLOUDANT_HOST');
		$port = '443';
		$username = getenv('CLOUDANT_USERNAME');
		$password = getenv('CLOUDANT_PASSWORD');
		if($vcapStr = getenv('VCAP_SERVICES')) {
			$vcap = json_decode($vcapStr, true);
			foreach ($vcap as $serviceTypes) {
				foreach ($serviceTypes as $service) {
					if($service['label'] == 'cloudantNoSQLDB') {
						$credentials = $service['credentials'];
						$username = $credentials['username'];
						$password = $credentials['password'];
						$parsedUrl = parse_url($credentials['url']);
						$host = $parsedUrl['host'];
						$port = isset($parsedUrl['port']) ?
						$parsedUrl['port'] : $parsedUrl['scheme'] == 'http' ?
						'80' : '443';
						break;
					}
				}
			}
		}
		$this->sag = new Sag($host, $port);
                $this->sag->decode(false);
		$this->sag->useSSL(true);
		$dbsession = $this->sag->login($username, $password);
	  $this->db_exists = true;
	try {
			$this->sag->setDatabase("mydb", true);
	
	} catch (Exception $e) {
                error_log("Error creating DB $e ");
		$this->db_exists = false;
        }
        try {
			$this->createView();
	} catch (Exception $e) {
                error_log("Error creating view $e ");
        }
    }

    /**
	 * Transforms the Visitor JSON from the DB to the JSON
	 * the client will expect.
	 */
    private function toClientVisitor($couchVisitor) {
		$clientVisitor = array('id' => $couchVisitor->id);
		$clientVisitor['name'] = $couchVisitor->value->name;
		return $clientVisitor;
	}

	/**
	 * Creates a view to use in the DB if one does not already exist.
	 */
	private function createView() {
		$allshops = array('reduce' => '_count',
		'map' => 'function(doc){if(doc.shop_name != null){
			emit(doc.order,{
				shop_own: doc.shop_own,
				shop_name: doc.shop_name,
				shop_cat: doc.shop_cat,
				shop_desc: doc.shop_desc,
				shop_location: doc.shop_location,
				shop_status: doc.shop_status
			})}}');
		$views = array('allshops' => $allshops
					);
		$designDoc = array('views' => $views);
		$this->sag->put('_design/shops', $designDoc);
	}

	/**
	 * Gets all visitors from the DB.
	 */
	public function get() {
		$visitors = array();
		$obj = $this->sag->get('_design/visitors/_view/allvisitors?reduce=false')->body;
		#error_log("OBJ is $obj->body");
		$docs = json_decode($obj);
		foreach ($docs->rows as $row) {
			$visitors[] = $row->value->name;;
		}
		return $visitors;
	}

	/**
	 * Creates a new Visitor in the DB.
	 */
	public function post($visitor) {
#                $this->sag->decode(true);
		$resp = $this->sag->post($visitor);
#                $this->sag->decode(false);
# error_log("$resp $resp->body[0]\n");
		#$visitor['id'] = $resp->body->id;
                # Why can't we get at the ID here?
		return $visitor;
	}

	/**
	 * Updates a Visitor in the DB.
	 */
	public function put($id, $visitor) {
		$couchTodo = $this->sag->get($id)->body;
    	$couchTodo->name = $visitor['name'];
    	$this->sag->put($id, $couchTodo);
    	$couchTodo->id = $id;
    	unset($couchTodo->_id);
    	unset($couchTodo->_rev);
    	return $couchTodo;
	}

	/**
	 * Deletes a Visitor from the DB.
	 */
	public function delete($id) {
		$rev = $this->sag->get($id)->body->_rev;
		$this->sag->delete($id, $rev);
	}

	/**
	 * My first api with IBM ADDs
	 * MArco
	 */
	/**
	 * Gets all visitors from the DB.
	 */
	public function getshops() {
		$shops = array();
		$obj = $this->sag->get('_design/shops/_view/allshops?reduce=false')->body;
		#error_log("OBJ is $obj->body");
		$docs = json_decode($obj);
		foreach ($docs->rows as $row) {
			// bring all data here 
			$shops[]=array(
				//'shop_own'=>$row->value->shop_own,
				'name'=>$row->value->shop_name,
				'category'=>$row->value->shop_cat,
				'description' => $row->value->shop_desc,
				'location'	=>$row->value->shop_location,
				'status'	=>$row->value->shop_status
			) ;;
		}
		return $shops;
	}
	/**
	 * This gest one shop by id
	 */
	public function getashop($id) {
		$shops = array();
		$obj = $this->sag->get('_design/get_a_shop/_search/serachShop?q=shop_own:'.$id)->body;
		#error_log("OBJ is $obj->body");
		$docs = json_decode($obj);
		foreach ($docs->rows as $row) {
		$shops = $row->id;
		}
		return $shops	;
	}
	/**
	 * POST SHOPS
	 * Correct post format
     * {"shop_name":"verduras juanita",
	 * "shop_cat":"frutas y verduras",
	 * "shop_desc":"Loremi ipsum",
	 * "shop_location":"19.296727, -99.137933",
	 * "shop_status":"close",
	 * "shop_user_id":"12893s"}
	 */
	public function postshop($shop) {
		#                $this->sag->decode(true);
				$resp = $this->sag->post($shop);
		#                $this->sag->decode(false);
		# error_log("$resp $resp->body[0]\n");
				#$visitor['id'] = $resp->body->id;
						# Why can't we get at the ID here?
				return $shop;
	}

	/**
	 * PUT
	 */

	public function putStatus($id, $visitor) {
		
		$couchTodo = $this->sag->get($id)->body;
		$obj = json_decode($couchTodo,true);
    	$obj['shop_status'] = $visitor['status'];
    	$this->sag->put($id, $obj);
    	$obj['id'] = $id;
    	unset($couchTodo->_id);
    	unset($couchTodo->_rev);
    	return $couchTodo;
	}
}
