<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\Movie;
use support\MovieParser;

class MovieController
{
    /**
     * 电影列表页面
     */
    public function index(Request $request): Response
    {
        $userId = $request->session()->get('user_id');
        $page = max(1, (int) $request->get('page', 1));
        $search = $request->get('search', '');
        $category = $request->get('category', '');

        // 获取所有分类
        $categories = Movie::getCategoriesByUserId($userId);

        if ($search) {
            $movies = Movie::search($userId, $search);
            $total = count($movies);
        } else {
            $movies = Movie::findByUserId($userId, $page, 20, $category ?: null);
            $total = Movie::countByUserId($userId, $category ?: null);
        }

        $totalPages = ceil($total / 20);

        return view('movies/index', [
            'movies' => $movies,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'search' => $search,
            'category' => $category,
            'categories' => $categories
        ]);
    }

    /**
     * 添加电影页面
     */
    public function create(Request $request): Response
    {
        return view('movies/create');
    }

    /**
     * 批量导入页面
     */
    public function batchImport(Request $request): Response
    {
        return view('movies/batch_import');
    }

    /**
     * 解析URL获取电影信息
     */
    public function parseUrl(Request $request): Response
    {
        $url = $request->post('url');

        if (!$url) {
            return json([
                'success' => false,
                'message' => 'URL不能为空'
            ]);
        }

        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return json([
                'success' => false,
                'message' => 'URL格式不正确'
            ]);
        }

        // 解析URL获取电影信息
        $result = MovieParser::parse($url);

        if ($result['success']) {
            return json([
                'success' => true,
                'data' => [
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'poster_url' => $result['poster_url']
                ]
            ]);
        } else {
            return json([
                'success' => false,
                'message' => '无法从URL获取电影信息，请手动填写'
            ]);
        }
    }

    /**
     * 保存电影
     */
    public function store(Request $request): Response
    {
        $userId = $request->session()->get('user_id');
        $title = trim($request->post('title', ''));
        $url = trim($request->post('url', ''));
        $description = trim($request->post('description', ''));
        $posterUrl = trim($request->post('poster_url', ''));
        $category = trim($request->post('category', '未分类'));
        $file = $request->file('video');

        // 处理文件上传
        if ($file) {
            $originalName = $file->getUploadName();
            $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExt;
            $filePath = public_path() . '/videos/' . $fileName;
            
            if ($file->move($filePath)) {
                // 使用文件名作为电影名称
                $title = pathinfo($originalName, PATHINFO_FILENAME);
                // 生成视频文件的访问URL
                $url = '/videos/' . $fileName;
            } else {
                return json([
                    'success' => false,
                    'message' => '文件上传失败'
                ]);
            }
        } else {
            // 验证必填字段
            if (!$title || !$url) {
                return json([
                    'success' => false,
                    'message' => '电影名称和URL不能为空'
                ]);
            }

            // 验证URL格式
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/videos/')) {
                return json([
                    'success' => false,
                    'message' => 'URL格式不正确'
                ]);
            }
        }

        try {
            $movieId = Movie::create([
                'user_id' => $userId,
                'title' => $title,
                'url' => $url,
                'description' => $description,
                'poster_url' => $posterUrl,
                'category' => $category
            ]);

            return json([
                'success' => true,
                'message' => '电影添加成功',
                'redirect' => '/movies'
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '添加失败，请稍后重试'
            ]);
        }
    }

    /**
     * 编辑电影页面
     */
    public function edit(Request $request, $id): Response
    {
        $userId = $request->session()->get('user_id');
        $movie = Movie::find($id);

        if (!$movie || !Movie::belongsToUser($id, $userId)) {
            return view('errors/404');
        }

        return view('movies/edit', ['movie' => $movie]);
    }

    /**
     * 更新电影
     */
    public function update(Request $request, $id): Response
    {
        $userId = $request->session()->get('user_id');

        if (!Movie::belongsToUser($id, $userId)) {
            return json([
                'success' => false,
                'message' => '无权操作此电影'
            ]);
        }

        $title = trim($request->post('title', ''));
        $url = trim($request->post('url', ''));
        $description = trim($request->post('description', ''));
        $posterUrl = trim($request->post('poster_url', ''));
        $category = trim($request->post('category', '未分类'));

        // 验证必填字段
        if (!$title || !$url) {
            return json([
                'success' => false,
                'message' => '电影名称和URL不能为空'
            ]);
        }

        // 验证URL格式
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/videos/')) {
            return json([
                'success' => false,
                'message' => 'URL格式不正确'
            ]);
        }

        try {
            Movie::update($id, [
                'title' => $title,
                'url' => $url,
                'description' => $description,
                'poster_url' => $posterUrl,
                'category' => $category
            ]);

            return json([
                'success' => true,
                'message' => '电影更新成功',
                'redirect' => '/movies'
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '更新失败，请稍后重试'
            ]);
        }
    }

    /**
     * 播放电影
     */
    public function play(Request $request, $id): Response
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return view('errors/404');
        }

        return view('movies/play', ['movie' => $movie]);
    }

    /**
     * 删除电影
     */
    public function delete(Request $request, $id): Response
    {
        $userId = $request->session()->get('user_id');

        if (!Movie::belongsToUser($id, $userId)) {
            return json([
                'success' => false,
                'message' => '无权操作此电影'
            ]);
        }

        try {
            // 获取电影信息
            $movie = Movie::find($id);
            
            // 删除电影记录
            Movie::delete($id, $userId);
            
            // 删除本地视频文件
            if ($movie && str_starts_with($movie['url'], '/videos/')) {
                $filePath = public_path() . $movie['url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return json([
                'success' => true,
                'message' => '删除成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '删除失败，请稍后重试'
            ]);
        }
    }
}
