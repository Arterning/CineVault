<?php
/**
 * 链接解析器配置
 */
return [
    // OpenGraph API 服务地址
    'opengraph_api_url' => getenv('OPENGRAPH_API_URL') ?: 'http://localhost:8007/opengraph',

    // HTTP 代理地址
    'proxy' => getenv('HTTP_PROXY') ?: 'http://127.0.0.1:7890',

    // 请求超时时间（秒）
    'timeout' => getenv('PARSER_TIMEOUT') ?: 10,

    // OpenGraph API 超时时间（秒）
    'api_timeout' => getenv('PARSER_API_TIMEOUT') ?: 15,
];
