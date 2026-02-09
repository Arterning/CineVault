<?php

namespace app\controller;

use support\Request;
use app\model\Movie;

class IndexController
{
    public function index(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $movies = Movie::findAll($page, 20);
        $total = Movie::countAll();
        $totalPages = ceil($total / 20);

        return view('index/index', [
            'movies' => $movies,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
