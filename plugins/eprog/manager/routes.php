<?php  

Route::group(['middleware' => ['web','RainLab\User\Classes\AuthMiddleware']], function () {
    
	Route::any('client/thumb/{code}/{width}/{height}', 'Eprog\Manager\Controllers\PublicFiles@thumb');  
	Route::any('client/download/{code}', 'Eprog\Manager\Controllers\PublicFiles@get');
	Route::any('client/feed', 'Eprog\Manager\Controllers\Feed@client'); 
	Route::any('client/invoicepdf/{id}', 'Eprog\Manager\Controllers\Printer@invoicepdf');  
	Route::any('client/orderpdf/{id}', 'Eprog\Manager\Controllers\Printer@orderpdf');
	Route::any('client/proformapdf/{id}', 'Eprog\Manager\Controllers\Printer@proformapdf');

});


Route::group(['middleware' => ['web','Eprog\Manager\Classes\BackendAuthMiddleware']], function () {
    
	Route::any('backend/download/{code}', 'Eprog\Manager\Controllers\PublicFiles@get');  

});

                                                                                                                                              
Route::any('/', function (){

	return Redirect::to(config('cms.backendUri'));

});

