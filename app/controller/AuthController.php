<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\User;

class AuthController
{
    /**
     * 显示登录页面
     */
    public function login(Request $request): Response
    {
        if ($request->session()->get('user_id')) {
            return redirect('/movies');
        }

        return view('auth/login');
    }

    /**
     * 处理登录请求
     */
    public function doLogin(Request $request): Response
    {
        $username = $request->post('username');
        $password = $request->post('password');

        if (!$username || !$password) {
            return json([
                'success' => false,
                'message' => '用户名和密码不能为空'
            ]);
        }

        $user = User::findByUsername($username);

        if (!$user || !User::verifyPassword($user, $password)) {
            return json([
                'success' => false,
                'message' => '用户名或密码错误'
            ]);
        }

        // 设置session
        $request->session()->set('user_id', $user['id']);
        $request->session()->set('username', $user['username']);

        return json([
            'success' => true,
            'message' => '登录成功',
            'redirect' => '/movies'
        ]);
    }

    /**
     * 显示注册页面
     */
    public function register(Request $request): Response
    {
        if ($request->session()->get('user_id')) {
            return redirect('/movies');
        }

        return view('auth/register');
    }

    /**
     * 处理注册请求
     */
    public function doRegister(Request $request): Response
    {
        $username = $request->post('username');
        $password = $request->post('password');
        $confirmPassword = $request->post('confirm_password');
        $email = $request->post('email');

        // 验证输入
        if (!$username || !$password) {
            return json([
                'success' => false,
                'message' => '用户名和密码不能为空'
            ]);
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            return json([
                'success' => false,
                'message' => '用户名长度必须在3-50个字符之间'
            ]);
        }

        if (strlen($password) < 6) {
            return json([
                'success' => false,
                'message' => '密码长度至少为6个字符'
            ]);
        }

        if ($password !== $confirmPassword) {
            return json([
                'success' => false,
                'message' => '两次输入的密码不一致'
            ]);
        }

        // 检查用户名是否已存在
        if (User::findByUsername($username)) {
            return json([
                'success' => false,
                'message' => '用户名已存在'
            ]);
        }

        // 检查邮箱是否已存在
        if ($email && User::findByEmail($email)) {
            return json([
                'success' => false,
                'message' => '邮箱已被注册'
            ]);
        }

        // 创建用户
        try {
            $userId = User::create([
                'username' => $username,
                'password' => $password,
                'email' => $email
            ]);

            // 自动登录
            $request->session()->set('user_id', $userId);
            $request->session()->set('username', $username);

            return json([
                'success' => true,
                'message' => '注册成功',
                'redirect' => '/movies'
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '注册失败，请稍后重试'
            ]);
        }
    }

    /**
     * 退出登录
     */
    public function logout(Request $request): Response
    {
        $request->session()->forget('user_id');
        $request->session()->forget('username');
        return redirect('/login');
    }
}
