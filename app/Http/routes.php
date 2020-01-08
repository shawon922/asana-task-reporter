<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$app->get('/', function () use ($app) {
    // return view('index');
    $app->abort(404);
});

$app->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});

/* Receives chatwork message */
$app->post('/message-hook', 'ChatworkController@storeMessage');

/* Exports report for today */
// $app->get('/export', 'ChatworkController@export');
$app->get('/export', function () use ($app) {
    $app->abort(404);
});

$app->group(['prefix'=>'api/v1', 'namespace' => 'App\Http\Controllers'], function() use($app){
    /* Sends asana url to chatwork */
    $app->get('/profile', 'ChatworkController@profile');
    $app->post('/messages', 'ChatworkController@postMessage');

    $app->get('/report', 'ReportController@getReport');
});