<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;
use app\controller\Error;
use \app\middleware\Auth;

$tip405 = true;


//鉴权
Route::group(function () {
    Route::any('/', '/')->name('访问后台');
    Route::any('/log', '/log')->name('查看日志');

// User
    Route::group('user', function () {
        Route::get('', 'user/index')->json()->name('获取用户资源');
        Route::get(':id', 'user/index')->json()->name('获取用户资源');
        Route::get('info', 'user/info')->json()->name('获取用户信息');
        Route::get('initMenu', 'user/initMenu')->json()->name('获取菜单更新权限');
        Route::post('login', 'user/login')->json()->name('登录');
        Route::get('logout', 'user/logout')->json()->name('退出登录');
        Route::delete('logout', 'user/logout')->json()->name('退出登录');
    });
})->middleware(Auth::class);


//请求方法错误提示
$tip405 ? Route::group(function () {
    Route::group('user', function () {
        Route::any('', 'error/jsonMethod')->append(['allow' => 'GET'])->name('405错误');
        Route::any(':id', 'error/jsonMethod')->append(['allow' => 'GET'])->name('405错误');
        Route::any('info', 'error/jsonMethod')->append(['allow' => 'GET'])->name('405错误');
        Route::any('initMenu', 'error/initMenu')->append(['allow' => 'GET'])->name('405错误');
        Route::any('login', 'error/jsonMethod')->append(['allow' => 'POST'])->name('405错误');
        Route::any('logout', 'error/jsonMethod')->append(['allow' => 'DELETE | GET'])->name('405错误');
    });
}) : null;


//404
Route::miss(function () {
    $error = new Error();
    return $error->notFound();
});
