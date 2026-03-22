<?php

use think\facade\Route;

Route::group('show', function () {
    Route::rule('news/:id', 'show/news');
})->pattern(['id' => '\d+']);
