<?php

// Mail entries...
Route::get('/telescope-api/mail', 'MailController@index');
Route::get('/telescope-api/mail/{id}', 'MailController@show');
Route::get('/telescope-api/mail/{id}/preview', 'MailHtmlController@show');
Route::get('/telescope-api/mail/{id}/download', 'MailEmlController@show');

// Exception entries...
Route::get('/telescope-api/exceptions', 'ExceptionController@index');
Route::get('/telescope-api/exceptions/{id}', 'ExceptionController@show');

// Log entries...
Route::get('/telescope-api/logs', 'LogController@index');
Route::get('/telescope-api/logs/{id}', 'LogController@show');

// Notifications entries...
Route::get('/telescope-api/notifications', 'NotificationsController@index');
Route::get('/telescope-api/notifications/{id}', 'NotificationsController@show');

// Queue entries...
Route::get('/telescope-api/jobs', 'QueueController@index');
Route::get('/telescope-api/jobs/{id}', 'QueueController@show');

// Events entries...
Route::get('/telescope-api/events', 'EventsController@index');
Route::get('/telescope-api/events/{id}', 'EventsController@show');

// Cache entries...
Route::get('/telescope-api/cache', 'CacheController@index');
Route::get('/telescope-api/cache/{id}', 'CacheController@show');

// Queries entries...
Route::get('/telescope-api/queries', 'QueriesController@index');
Route::get('/telescope-api/queries/{id}', 'QueriesController@show');

// Requests entries...
Route::get('/telescope-api/requests', 'RequestsController@index');
Route::get('/telescope-api/requests/{id}', 'RequestsController@show');

// Artisan Commands entries...
Route::get('/telescope-api/commands', 'CommandsController@index');
Route::get('/telescope-api/commands/{id}', 'CommandsController@show');

// Scheduled Commands entries...
Route::get('/telescope-api/schedule', 'ScheduleController@index');
Route::get('/telescope-api/schedule/{id}', 'ScheduleController@show');

// Redis Commands entries...
Route::get('/telescope-api/redis', 'RedisController@index');
Route::get('/telescope-api/redis/{id}', 'RedisController@show');

Route::get('/{view?}', function () {
    return view('telescope::layout');
})->where('view', '(.*)');
