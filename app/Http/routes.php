<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

/*
 * The main route for the frontpage.
 */
Route::get('/', ['as' => 'homepage', 'uses' => 'HomeController@show']);

/*
 * Routes related to authentication.
 */
Route::group(['as' => 'login::'], function () {
    Route::get('auth/login', ['as' => 'show', 'uses' => 'Auth\AuthController@getLogin']);
    Route::post('auth/login', ['middleware' => 'utwente.auth', 'as' => 'post', 'uses' => 'Auth\AuthController@postLogin']);
    Route::get('auth/logout', ['as' => 'logout', 'uses' => 'Auth\AuthController@getLogout']);

    Route::get('password/reset/{token}', ['as' => 'resetpass::token', 'uses' => 'Auth\PasswordController@getReset']);
    Route::post('password/reset', ['as' => 'resetpass::submit', 'uses' => 'Auth\PasswordController@postReset']);

    Route::get('password/email', ['as' => 'resetpass', 'uses' => 'Auth\PasswordController@getEmail']);
    Route::post('password/email', ['as' => 'resetpass::send', 'uses' => 'Auth\PasswordController@postEmail']);
});

/*
 * Routes related to user profiles.
 */
Route::group(['prefix' => 'user', 'as' => 'user::', 'middleware' => ['auth']], function () {

    /*
     * Routes related to members.
     */
    Route::group(['prefix' => '{id}/member', 'as' => 'member::', 'middleware' => ['auth', 'permission:board']], function () {
        Route::get('nested', ['as' => 'nested::details', 'uses' => 'MemberAdminController@showDetails']);
        Route::get('impersonate', ['as' => 'impersonate', 'middleware' => ['auth', 'permission:admin'], 'uses' => 'MemberAdminController@impersonate']);

        Route::post('add', ['as' => 'add', 'uses' => 'MemberAdminController@addMembership']);
        Route::post('remove', ['as' => 'remove', 'uses' => 'MemberAdminController@endMembership']);
        Route::get('remove', ['as' => 'remove', 'uses' => 'MemberAdminController@endMembership']);
    });

    Route::group(['prefix' => 'admin', 'as' => 'member::', 'middleware' => ['auth', 'permission:board']], function () {
        Route::get('', ['as' => 'list', 'uses' => 'MemberAdminController@index']);
        Route::post('search/nested', ['as' => 'nested::search', 'uses' => 'MemberAdminController@showSearch']);
    });

    Route::get('quit_impersonating', ['as' => 'quitimpersonating', 'uses' => 'MemberAdminController@quitImpersonating']);

    Route::get('dashboard/{id?}', ['as' => 'dashboard', 'uses' => 'UserDashboardController@show']);
    Route::post('dashboard/{id?}', ['as' => 'dashboard', 'uses' => 'UserDashboardController@update']);

    Route::get('{id?}', ['as' => 'profile', 'middleware' => ['member'], 'uses' => 'UserProfileController@show']);

    /*
     * Routes related to addresses.
     */
    Route::group(['prefix' => '{id}/address', 'as' => 'address::'], function () {
        Route::get('add', ['as' => 'add', 'uses' => 'AddressController@addForm']);
        Route::post('add', ['as' => 'add', 'uses' => 'AddressController@add']);

        Route::post('delete/{address_id}', ['as' => 'delete', 'uses' => 'AddressController@delete']);

        Route::post('primary/{address_id}', ['as' => 'primary', 'uses' => 'AddressController@makePrimary']);

        Route::get('edit/{address_id}', ['as' => 'edit', 'uses' => 'AddressController@editForm']);
        Route::post('edit/{address_id}', ['as' => 'edit', 'uses' => 'AddressController@edit']);

        Route::get('togglehidden', ['as' => 'togglehidden', 'uses' => 'AddressController@toggleHidden']);
    });

    /*
     * Routes related to bank accounts
     */
    Route::group(['prefix' => '{id}/bank', 'as' => 'bank::'], function () {
        Route::get('add', ['as' => 'add', 'uses' => 'BankController@addForm']);
        Route::post('add', ['as' => 'add', 'uses' => 'BankController@add']);
        Route::post('delete', ['as' => 'delete', 'uses' => 'BankController@delete']);
    });

    /*
     * Routes related to bank accounts
     */
    Route::group(['prefix' => '{id}/study', 'as' => 'study::'], function () {
        Route::get('link', ['as' => 'add', 'uses' => 'StudyController@linkForm']);
        Route::post('link', ['as' => 'add', 'uses' => 'StudyController@link']);

        Route::post('unlink/{study_id}', ['as' => 'delete', 'uses' => 'StudyController@unlink']);

        Route::get('edit/{study_id}', ['as' => 'edit', 'uses' => 'StudyController@editLinkForm']);
        Route::post('edit/{study_id}', ['as' => 'edit', 'uses' => 'StudyController@editLink']);
    });
});

/*
 * Routes related to authentication.
 */
Route::group(['prefix' => 'committee', 'as' => 'committee::'], function () {
    Route::get('list', ['as' => 'list', 'uses' => 'CommitteeController@overview']);

    Route::get('add', ['as' => 'add', 'middleware' => ['auth', 'permission:board'], 'uses' => 'CommitteeController@addForm']);
    Route::post('add', ['as' => 'add', 'middleware' => ['auth', 'permission:board'], 'uses' => 'CommitteeController@add']);

    Route::get('{id}', ['as' => 'show', 'uses' => 'CommitteeController@show']);

    Route::get('{id}/edit', ['as' => 'edit', 'middleware' => ['auth', 'permission:board'], 'uses' => 'CommitteeController@editForm']);
    Route::post('{id}/edit', ['as' => 'edit', 'middleware' => ['auth', 'permission:board'], 'uses' => 'CommitteeController@edit']);

    Route::get('{id}/delete', ['as' => 'delete', 'middleware' => ['auth', 'permission:board'], 'uses' => 'CommitteeController@delete']);
});