<?php

// Mail entries...
Route::get('/telescope-api/mail', 'MailController@index');
Route::get('/telescope-api/mail/{id}', 'MailController@show');
Route::get('/telescope-api/mail/{id}/preview', 'MailController@previewHTML');
Route::get('/telescope-api/mail/{id}/download', 'MailController@downloadEML');

// Log entries...
Route::get('/telescope-api/log', 'LogController@index');
Route::get('/telescope-api/log/{id}', 'LogController@show');

// Notifications entries...
Route::get('/telescope-api/notifications', 'NotificationsController@index');
Route::get('/telescope-api/notifications/{id}', 'NotificationsController@show');

// Queue entries...
Route::get('/telescope-api/queue', 'QueueController@index');
Route::get('/telescope-api/queue/{id}', 'QueueController@show');

// Events entries...
Route::get('/telescope-api/events', 'EventsController@index');
Route::get('/telescope-api/events/{id}', 'EventsController@show');

// Cache entries...
Route::get('/telescope-api/cache', 'CacheController@index');
Route::get('/telescope-api/cache/{id}', 'CacheController@show');

Route::get('/{view?}', function () {
    return view('telescope::layout');
})->where('view', '(.*)');

