<?php

use Illuminate\Support\Facades\Route;

// Mail entries...
Route::post('/telescope-api/mail', 'MailController@index');
Route::get('/telescope-api/mail/{telescopeEntryId}', 'MailController@show');
Route::get('/telescope-api/mail/{telescopeEntryId}/preview', 'MailHtmlController@show');
Route::get('/telescope-api/mail/{telescopeEntryId}/download', 'MailEmlController@show');

// Exception entries...
Route::post('/telescope-api/exceptions', 'ExceptionController@index');
Route::get('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@show');
Route::put('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@update');

// Dump entries...
Route::post('/telescope-api/dumps', 'DumpController@index');

// Log entries...
Route::post('/telescope-api/logs', 'LogController@index');
Route::get('/telescope-api/logs/{telescopeEntryId}', 'LogController@show');

// Notifications entries...
Route::post('/telescope-api/notifications', 'NotificationsController@index');
Route::get('/telescope-api/notifications/{telescopeEntryId}', 'NotificationsController@show');

// Queue entries...
Route::post('/telescope-api/jobs', 'QueueController@index');
Route::get('/telescope-api/jobs/{telescopeEntryId}', 'QueueController@show');

// Events entries...
Route::post('/telescope-api/events', 'EventsController@index');
Route::get('/telescope-api/events/{telescopeEntryId}', 'EventsController@show');

// Gates entries...
Route::post('/telescope-api/gates', 'GatesController@index');
Route::get('/telescope-api/gates/{telescopeEntryId}', 'GatesController@show');

// Cache entries...
Route::post('/telescope-api/cache', 'CacheController@index');
Route::get('/telescope-api/cache/{telescopeEntryId}', 'CacheController@show');

// Queries entries...
Route::post('/telescope-api/queries', 'QueriesController@index');
Route::get('/telescope-api/queries/{telescopeEntryId}', 'QueriesController@show');

// Eloquent entries...
Route::post('/telescope-api/models', 'ModelsController@index');
Route::get('/telescope-api/models/{telescopeEntryId}', 'ModelsController@show');

// Requests entries...
Route::post('/telescope-api/requests', 'RequestsController@index');
Route::get('/telescope-api/requests/{telescopeEntryId}', 'RequestsController@show');

// View entries...
Route::post('/telescope-api/views', 'ViewsController@index');
Route::get('/telescope-api/views/{telescopeEntryId}', 'ViewsController@show');

// Artisan Commands entries...
Route::post('/telescope-api/commands', 'CommandsController@index');
Route::get('/telescope-api/commands/{telescopeEntryId}', 'CommandsController@show');

// Scheduled Commands entries...
Route::post('/telescope-api/schedule', 'ScheduleController@index');
Route::get('/telescope-api/schedule/{telescopeEntryId}', 'ScheduleController@show');

// Redis Commands entries...
Route::post('/telescope-api/redis', 'RedisController@index');
Route::get('/telescope-api/redis/{telescopeEntryId}', 'RedisController@show');

// Monitored Tags...
Route::get('/telescope-api/monitored-tags', 'MonitoredTagController@index');
Route::post('/telescope-api/monitored-tags/', 'MonitoredTagController@store');
Route::post('/telescope-api/monitored-tags/delete', 'MonitoredTagController@destroy');

// Toggle Recording...
Route::post('/telescope-api/toggle-recording', 'RecordingController@toggle');

Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('telescope');
