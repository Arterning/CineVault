<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// 首页重定向到登录页
Route::get('/', function () {
    return redirect('/login');
});

// 认证路由（无需登录）
Route::get('/login', [app\controller\AuthController::class, 'login']);
Route::post('/login', [app\controller\AuthController::class, 'doLogin']);
Route::get('/register', [app\controller\AuthController::class, 'register']);
Route::post('/register', [app\controller\AuthController::class, 'doRegister']);
Route::get('/logout', [app\controller\AuthController::class, 'logout']);

// 电影管理路由（需要登录）
Route::group('/movies', function () {
    Route::get('', [app\controller\MovieController::class, 'index']);
    Route::get('/create', [app\controller\MovieController::class, 'create']);
    Route::get('/batch-import', [app\controller\MovieController::class, 'batchImport']);
    Route::post('/parse-url', [app\controller\MovieController::class, 'parseUrl']);
    Route::post('/store', [app\controller\MovieController::class, 'store']);
    Route::get('/{id}/edit', [app\controller\MovieController::class, 'edit']);
    Route::post('/{id}/update', [app\controller\MovieController::class, 'update']);
    Route::post('/{id}/delete', [app\controller\MovieController::class, 'delete']);
})->middleware([
    app\middleware\AuthMiddleware::class
]);






