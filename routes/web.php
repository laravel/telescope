<?php

use Illuminate\Support\Facades\Route;

// Mail entries...
Route::post('/telescope-api/mail', 'MailController@index');
Route::get('/telescope-api/mail/{telescopeEntryId}', 'MailController@show');
Route::get('/telescope-api/mail/{telescopeEntryId}/preview', 'MailHtmlController@show');
Route::get('/telescope-api/mail/{telescopeEntryId}/download', 'MailEmlController@show');
Route::get('/telescope-api/mail/{telescopeEntryId}/markdown', 'MailController@markdown');

// Exception entries...
Route::post('/telescope-api/exceptions', 'ExceptionController@index');
Route::get('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@show');
Route::put('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@update');
Route::get('/telescope-api/exceptions/{telescopeEntryId}/markdown', 'ExceptionController@markdown');

// Dump entries...
Route::post('/telescope-api/dumps', 'DumpController@index');

// Log entries...
Route::post('/telescope-api/logs', 'LogController@index');
Route::get('/telescope-api/logs/{telescopeEntryId}', 'LogController@show');
Route::get('/telescope-api/logs/{telescopeEntryId}/markdown', 'LogController@markdown');

// Notifications entries...
Route::post('/telescope-api/notifications', 'NotificationsController@index');
Route::get('/telescope-api/notifications/{telescopeEntryId}', 'NotificationsController@show');
Route::get('/telescope-api/notifications/{telescopeEntryId}/markdown', 'NotificationsController@markdown');

// Queue entries...
Route::post('/telescope-api/jobs', 'QueueController@index');
Route::get('/telescope-api/jobs/{telescopeEntryId}', 'QueueController@show');
Route::get('/telescope-api/jobs/{telescopeEntryId}/markdown', 'QueueController@markdown');

// Queue Batches entries...
Route::post('/telescope-api/batches', 'QueueBatchesController@index');
Route::get('/telescope-api/batches/{telescopeEntryId}', 'QueueBatchesController@show');
Route::get('/telescope-api/batches/{telescopeEntryId}/markdown', 'QueueBatchesController@markdown');

// Events entries...
Route::post('/telescope-api/events', 'EventsController@index');
Route::get('/telescope-api/events/{telescopeEntryId}', 'EventsController@show');
Route::get('/telescope-api/events/{telescopeEntryId}/markdown', 'EventsController@markdown');

// Gates entries...
Route::post('/telescope-api/gates', 'GatesController@index');
Route::get('/telescope-api/gates/{telescopeEntryId}', 'GatesController@show');
Route::get('/telescope-api/gates/{telescopeEntryId}/markdown', 'GatesController@markdown');

// Cache entries...
Route::post('/telescope-api/cache', 'CacheController@index');
Route::get('/telescope-api/cache/{telescopeEntryId}', 'CacheController@show');
Route::get('/telescope-api/cache/{telescopeEntryId}/markdown', 'CacheController@markdown');

// Queries entries...
Route::post('/telescope-api/queries', 'QueriesController@index');
Route::get('/telescope-api/queries/{telescopeEntryId}', 'QueriesController@show');
Route::get('/telescope-api/queries/{telescopeEntryId}/markdown', 'QueriesController@markdown');

// Eloquent entries...
Route::post('/telescope-api/models', 'ModelsController@index');
Route::get('/telescope-api/models/{telescopeEntryId}', 'ModelsController@show');
Route::get('/telescope-api/models/{telescopeEntryId}/markdown', 'ModelsController@markdown');

// Requests entries...
Route::post('/telescope-api/requests', 'RequestsController@index');
Route::get('/telescope-api/requests/{telescopeEntryId}', 'RequestsController@show');
Route::get('/telescope-api/requests/{telescopeEntryId}/markdown', 'RequestsController@markdown');

// View entries...
Route::post('/telescope-api/views', 'ViewsController@index');
Route::get('/telescope-api/views/{telescopeEntryId}', 'ViewsController@show');
Route::get('/telescope-api/views/{telescopeEntryId}/markdown', 'ViewsController@markdown');

// Artisan Commands entries...
Route::post('/telescope-api/commands', 'CommandsController@index');
Route::get('/telescope-api/commands/{telescopeEntryId}', 'CommandsController@show');
Route::get('/telescope-api/commands/{telescopeEntryId}/markdown', 'CommandsController@markdown');

// Scheduled Commands entries...
Route::post('/telescope-api/schedule', 'ScheduleController@index');
Route::get('/telescope-api/schedule/{telescopeEntryId}', 'ScheduleController@show');
Route::get('/telescope-api/schedule/{telescopeEntryId}/markdown', 'ScheduleController@markdown');

// Redis Commands entries...
Route::post('/telescope-api/redis', 'RedisController@index');
Route::get('/telescope-api/redis/{telescopeEntryId}', 'RedisController@show');
Route::get('/telescope-api/redis/{telescopeEntryId}/markdown', 'RedisController@markdown');

// Client Requests entries...
Route::post('/telescope-api/client-requests', 'ClientRequestController@index');
Route::get('/telescope-api/client-requests/{telescopeEntryId}', 'ClientRequestController@show');
Route::get('/telescope-api/client-requests/{telescopeEntryId}/markdown', 'ClientRequestController@markdown');

// Monitored Tags...
Route::get('/telescope-api/monitored-tags', 'MonitoredTagController@index');
Route::post('/telescope-api/monitored-tags/', 'MonitoredTagController@store');
Route::post('/telescope-api/monitored-tags/delete', 'MonitoredTagController@destroy');

// Toggle Recording...
Route::post('/telescope-api/toggle-recording', 'RecordingController@toggle');

// Clear Entries...
Route::delete('/telescope-api/entries', 'EntriesController@destroy');

Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('telescope');
