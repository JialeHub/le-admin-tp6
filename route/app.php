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
Route::pattern([
    'name' => '\w+',
    'id'   => '\d+',
]);

//鉴权
Route::group(function () {
    Route::any('/', '/')->name('访问后台');
    Route::any('/log', '/log')->name('查看日志');

// User
    Route::group('user', function () {
        Route::get('list', 'user/index')->name('获取用户资源');
        Route::get(':id', 'user/index')->name('获取用户资源');
        Route::put('updateInfo', 'user/updateInfo')->name('修改用户信息(User)');
        Route::put('update/:id', 'user/update')->name('修改用户信息(Admin)');
        Route::get('info', 'user/info')->name('获取用户信息');
        Route::get('initMenu', 'user/initMenu')->name('获取菜单更新权限');
        Route::post('login', 'user/login')->name('登录');
        Route::post('register', 'user/register')->name('注册');
        Route::get('logout', 'user/logout')->name('退出登录');
        Route::delete('logout', 'user/logout')->name('退出登录');
    })->name('用户');

//File
    Route::group(function () {
        Route::resource('file', 'File');
    })->name('文件');

//Apk
    Route::get('/apk/download', '/apk/download')->name('下载安装包');
    Route::get('/apk', '/apk/index')->name('检查更新');

//Publish
    Route::group(function () {
        Route::put('publish/update/:id', 'Publish/update')->name('修改发布消息');
        Route::delete('publish/delete/:id', 'Publish/delete')->name('删除发布消息');
        Route::delete('publish/delete', 'Publish/delete')->name('批量删除发布消息');
        Route::get('publish/downloadFiles/:id', 'Publish/downloadFiles')->name('批量下载发布消息');
        Route::post('publish/downloadFiles', 'Publish/downloadFiles')->name('下载发布消息');
        Route::get('publish/downloadFiles', 'Publish/downloadFiles')->name('下载发布消息');
        Route::get('publish/collectMe', 'Publish/collectMe')->name('获取自己的分数');
        Route::get('publish/collect', 'Publish/collect')->name('汇总用户分数');
        Route::resource('publish', 'Publish');
    })->name('动态发布');
})->middleware(Auth::class)->allowCrossDomain();


//请求方法错误提示
$tip405 ? Route::group(function () {
    Route::group('user', function () {
        Route::any('', 'error/jsonMethod')->append(['allow' => 'GET | POST'])->name('405错误');
        Route::any(':id', 'error/jsonMethod')->append(['allow' => 'GET | DELETE | PUT'])->name('405错误');
        Route::any('info', 'error/jsonMethod')->append(['allow' => 'GET'])->name('405错误');
        Route::any('initMenu', 'error/initMenu')->append(['allow' => 'GET'])->name('405错误');
        Route::any('login', 'error/jsonMethod')->append(['allow' => 'POST'])->name('405错误');
        Route::any('logout', 'error/jsonMethod')->append(['allow' => 'DELETE | GET'])->name('405错误');
    });

    Route::group('file',function () {
        Route::any('', 'error/jsonMethod')->append(['allow' => 'GET | POST'])->name('405错误');
        Route::any(':id', 'error/jsonMethod')->append(['allow' => 'GET | DELETE | PUT'])->name('405错误');
    });

    Route::group('publish',function () {
        Route::any('', 'error/jsonMethod')->append(['allow' => 'GET | POST'])->name('405错误');
        Route::any(':id', 'error/jsonMethod')->append(['allow' => 'GET | DELETE | PUT'])->name('405错误');
    });

})->allowCrossDomain() : null;


//404
Route::miss(function () {
    $error = new Error();
    return $error->notFound();
});
