<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 检查用户是否已登录
        if (!$request->session()->get('user_id')) {
            // 如果是AJAX请求，返回JSON
            if ($request->expectsJson()) {
                return json([
                    'success' => false,
                    'message' => '请先登录',
                    'redirect' => '/login'
                ], 401);
            }
            // 否则重定向到登录页
            return redirect('/login');
        }

        return $handler($request);
    }
}
