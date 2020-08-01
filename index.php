<?php
/*
 * Copyright IBM Corp. 2016,2019
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

 /**
  * This PHP file uses the Slim Framework to construct a REST API.
  * See Cloudant.php for the database functionality
  */
require 'vendor/autoload.php';
require_once('./Cloudant.php');
$app = new \Slim\Slim();
$dotenv = new Dotenv\Dotenv(__DIR__);
try {
  $dotenv->load();
} catch (Exception $e) {
    error_log("No .env file found");
 }
$app->get('/', function () {
  global $app;
    //$app->render('index.html');
    // list all shops and datas
    $app->contentType('application/json');
  $shops = array();
  if(Cloudant::Instance()->isConnected()) {
    $shops = Cloudant::Instance()->getshops();
  }
  echo json_encode($shops);
});

$app->get('/api/visitors', function () {
  global $app;
  $app->contentType('application/json');
  $visitors = array();
  if(Cloudant::Instance()->isConnected()) {
    $visitors = Cloudant::Instance()->get();
  }
  echo json_encode($visitors);
});

$app->post('/api/visitors', function() {
  global $app;
    error_log("POST in /api/visitors");
  $app->contentType('application/json');
  $visitor = $app->request()->getBody();
  #$visitor = json_decode($app->request()->getBody(), true);
  if(Cloudant::Instance()->isConnected()) {
    $doc = Cloudant::Instance()->post($visitor);
    error_log("POST error: $visitor $doc");
    echo $doc;
    #echo json_encode($doc);
  } else {
    error_log("POST error: $visitor");
    echo json_encode($visitor);
  }
});

$app->delete('/api/visitors/:id', function($id) {
	global $app;
	Cloudant::Instance()->delete($id);
    $app->response()->status(204);
});

$app->put('/api/visitors/:id', function($id) {
	global $app;
	$visitor = json_decode($app->request()->getBody(), true);
    echo json_encode(Cloudant::Instance()->put($id, $visitor));
});
/**
 * OWNS ROUTES
 */
$app->get('/api/shop/:shop_own', function($id) {
  global $app;
  $app->contentType('application/json');
  $visitors = array();
  if(Cloudant::Instance()->isConnected()) {
    $visitors = Cloudant::Instance()->getashop($id);
  }
  echo json_encode($visitors);
});
/**
 * POST
 */
$app->post('/api/shop', function() {
  global $app;
    error_log("POST in /api/shop");
  $app->contentType('application/json');
  $shop = $app->request()->getBody();
  #$visitor = json_decode($app->request()->getBody(), true);
  if(Cloudant::Instance()->isConnected()) {
    $doc = Cloudant::Instance()->post($shop);
    error_log("POST error: $shop $doc");
    echo $doc;
    #echo json_encode($doc);
  } else {
    error_log("POST error: $shop");
    echo json_encode($shop);
  }
});

/***
 * PUT TEST
 */

$app->put('/api/shop/status/:id', function($id) {
	global $app;
	$shop = json_decode($app->request()->getBody(), true);
    echo json_encode(Cloudant::Instance()->putStatus($id, $shop));
});


$app->run();
