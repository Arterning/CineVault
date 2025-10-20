<?php

namespace support;

use support\Log;

class MovieParser
{
    /**
     * 从URL解析电影信息
     */
    public static function parse(string $url): array
    {
        Log::info('MovieParser: 开始解析URL', ['url' => $url]);

        $result = [
            'success' => false,
            'title' => '',
            'description' => '',
            'poster_url' => ''
        ];

        try {
            // 获取配置
            $config = config('parser', []);
            $proxy = $config['proxy'] ?? 'http://127.0.0.1:7890';
            $timeout = $config['timeout'] ?? 10;

            // 转换代理地址格式 (http://host:port -> tcp://host:port)
            $proxyUrl = str_replace('http://', 'tcp://', $proxy);

            // 设置超时和用户代理
            $contextOptions = [
                'http' => [
                    'timeout' => $timeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'proxy' => $proxyUrl,
                    'request_fulluri' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];

            Log::info('MovieParser: 正在获取网页内容...', ['使用代理' => $proxy]);
            $context = stream_context_create($contextOptions);
            $html = @file_get_contents($url, false, $context);

            if ($html === false) {
                $error = error_get_last();
                Log::error('MovieParser: 无法获取网页内容', [
                    'error' => $error['message'] ?? 'Unknown error',
                    'url' => $url
                ]);
                // 主解析失败，尝试使用OpenGraph API
                Log::info('MovieParser: 尝试使用OpenGraph API备用解析');
                return self::parseWithOpenGraphApi($url);
            }

            Log::info('MovieParser: 成功获取网页内容', [
                'html_length' => strlen($html),
                'html_preview' => substr($html, 0, 200)
            ]);

            // 解析HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            Log::info('MovieParser: HTML解析完成');

            // 尝试获取标题
            $title = self::extractTitle($dom, $url);
            Log::info('MovieParser: 提取标题', ['title' => $title]);
            if ($title) {
                $result['title'] = $title;
                $result['success'] = true;
            }

            // 尝试获取描述
            $description = self::extractDescription($dom);
            Log::info('MovieParser: 提取描述', ['description' => $description]);
            if ($description) {
                $result['description'] = $description;
            }

            // 尝试获取海报图片
            $posterUrl = self::extractPosterUrl($dom, $url);
            Log::info('MovieParser: 提取海报URL', ['poster_url' => $posterUrl]);
            if ($posterUrl) {
                $result['poster_url'] = $posterUrl;
            }

            Log::info('MovieParser: 解析完成', ['result' => $result]);

            // 如果主解析失败（没有获取到标题），尝试使用OpenGraph API
            if (!$result['success']) {
                Log::info('MovieParser: 主解析未获取到有效数据，尝试使用OpenGraph API备用解析');
                return self::parseWithOpenGraphApi($url);
            }

        } catch (\Exception $e) {
            Log::error('MovieParser: 解析异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // 异常时尝试使用OpenGraph API
            Log::info('MovieParser: 解析异常，尝试使用OpenGraph API备用解析');
            return self::parseWithOpenGraphApi($url);
        }

        return $result;
    }

    /**
     * 使用OpenGraph API解析URL
     */
    private static function parseWithOpenGraphApi(string $url): array
    {
        $result = [
            'success' => false,
            'title' => '',
            'description' => '',
            'poster_url' => ''
        ];

        try {
            // 获取配置
            $config = config('parser', []);
            $apiBaseUrl = $config['opengraph_api_url'] ?? 'http://localhost:8007/opengraph';
            $proxy = $config['proxy'] ?? 'http://127.0.0.1:7890';
            $apiTimeout = $config['api_timeout'] ?? 15;

            // 构建API请求URL
            $apiUrl = $apiBaseUrl . '?url=' . urlencode($url) . '&proxy=' . urlencode($proxy);
            Log::info('MovieParser: 调用OpenGraph API', ['api_url' => $apiUrl]);

            $contextOptions = [
                'http' => [
                    'timeout' => $apiTimeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];

            $context = stream_context_create($contextOptions);
            $response = @file_get_contents($apiUrl, false, $context);

            if ($response === false) {
                $error = error_get_last();
                Log::error('MovieParser: OpenGraph API调用失败', [
                    'error' => $error['message'] ?? 'Unknown error',
                    'api_url' => $apiUrl
                ]);
                return $result;
            }

            $data = json_decode($response, true);
            Log::info('MovieParser: OpenGraph API响应', ['data' => $data]);

            if (!$data || !isset($data['success']) || !$data['success']) {
                Log::warning('MovieParser: OpenGraph API返回失败', ['response' => $response]);
                return $result;
            }

            $ogData = $data['data'] ?? [];

            // 提取标题
            $title = $ogData['title'] ?? $ogData['twitter:title'] ?? $ogData['twitterTitle'] ?? '';
            if ($title) {
                $result['title'] = trim($title);
                $result['success'] = true;
            }

            // 提取描述
            $description = $ogData['description'] ?? $ogData['twitter:description'] ?? $ogData['twitterDescription'] ?? '';
            if ($description) {
                $result['description'] = trim($description);
            }

            // 提取图片
            $posterUrl = $ogData['image'] ?? $ogData['twitter:image'] ?? $ogData['twitterImage'] ?? '';
            if ($posterUrl) {
                $result['poster_url'] = trim($posterUrl);
            }

            Log::info('MovieParser: OpenGraph API解析完成', ['result' => $result]);

        } catch (\Exception $e) {
            Log::error('MovieParser: OpenGraph API解析异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * 提取标题
     */
    private static function extractTitle(\DOMDocument $dom, string $url): string
    {
        // 尝试从 og:title 获取
        $xpath = new \DOMXPath($dom);
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
        Log::info('MovieParser: 查找 og:title', ['found' => $ogTitle->length]);
        if ($ogTitle->length > 0) {
            $title = trim($ogTitle->item(0)->nodeValue);
            Log::info('MovieParser: 从 og:title 获取标题', ['title' => $title]);
            return $title;
        }

        // 尝试从 title 标签获取
        $titleTags = $dom->getElementsByTagName('title');
        Log::info('MovieParser: 查找 title 标签', ['found' => $titleTags->length]);
        if ($titleTags->length > 0) {
            $title = trim($titleTags->item(0)->nodeValue);
            Log::info('MovieParser: 从 title 标签获取原始标题', ['title' => $title]);
            // 清理常见的标题后缀
            $title = preg_replace('/ - .*$/', '', $title);
            $title = preg_replace('/ \| .*$/', '', $title);
            Log::info('MovieParser: 清理后的标题', ['title' => $title]);
            return $title;
        }

        // 从URL中提取标题
        $path = parse_url($url, PHP_URL_PATH);
        Log::info('MovieParser: 从URL提取标题', ['path' => $path]);
        if ($path) {
            $parts = explode('/', trim($path, '/'));
            $lastPart = end($parts);
            $title = ucfirst(str_replace(['-', '_'], ' ', $lastPart));
            Log::info('MovieParser: URL生成的标题', ['title' => $title]);
            return $title;
        }

        Log::warning('MovieParser: 无法提取标题');
        return '';
    }

    /**
     * 提取描述
     */
    private static function extractDescription(\DOMDocument $dom): string
    {
        $xpath = new \DOMXPath($dom);

        // 尝试从 og:description 获取
        $ogDesc = $xpath->query('//meta[@property="og:description"]/@content');
        Log::info('MovieParser: 查找 og:description', ['found' => $ogDesc->length]);
        if ($ogDesc->length > 0) {
            $desc = trim($ogDesc->item(0)->nodeValue);
            Log::info('MovieParser: 从 og:description 获取描述', ['description' => substr($desc, 0, 100)]);
            return $desc;
        }

        // 尝试从 meta description 获取
        $metaDesc = $xpath->query('//meta[@name="description"]/@content');
        Log::info('MovieParser: 查找 meta description', ['found' => $metaDesc->length]);
        if ($metaDesc->length > 0) {
            $desc = trim($metaDesc->item(0)->nodeValue);
            Log::info('MovieParser: 从 meta description 获取描述', ['description' => substr($desc, 0, 100)]);
            return $desc;
        }

        Log::info('MovieParser: 未找到描述');
        return '';
    }

    /**
     * 提取海报URL
     */
    private static function extractPosterUrl(\DOMDocument $dom, string $baseUrl): string
    {
        $xpath = new \DOMXPath($dom);

        // 尝试从 og:image 获取
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
        Log::info('MovieParser: 查找 og:image', ['found' => $ogImage->length]);
        if ($ogImage->length > 0) {
            $imageUrl = trim($ogImage->item(0)->nodeValue);
            $resolvedUrl = self::resolveUrl($imageUrl, $baseUrl);
            Log::info('MovieParser: 从 og:image 获取海报', [
                'original' => $imageUrl,
                'resolved' => $resolvedUrl
            ]);
            return $resolvedUrl;
        }

        // 尝试从第一个大图获取
        $images = $xpath->query('//img[@src]');
        Log::info('MovieParser: 查找 img 标签', ['found' => $images->length]);
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && !str_contains($src, 'icon') && !str_contains($src, 'logo')) {
                $resolvedUrl = self::resolveUrl($src, $baseUrl);
                Log::info('MovieParser: 从 img 标签获取海报', [
                    'original' => $src,
                    'resolved' => $resolvedUrl
                ]);
                return $resolvedUrl;
            }
        }

        Log::info('MovieParser: 未找到海报图片');
        return '';
    }

    /**
     * 解析相对URL为绝对URL
     */
    private static function resolveUrl(string $url, string $baseUrl): string
    {
        // 如果已经是绝对URL，直接返回
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        $base = parse_url($baseUrl);

        // 处理协议相对URL
        if (str_starts_with($url, '//')) {
            return ($base['scheme'] ?? 'http') . ':' . $url;
        }

        // 处理根相对URL
        if (str_starts_with($url, '/')) {
            return ($base['scheme'] ?? 'http') . '://' . ($base['host'] ?? '') . $url;
        }

        // 处理相对URL
        $path = $base['path'] ?? '/';
        $dir = dirname($path);
        return ($base['scheme'] ?? 'http') . '://' . ($base['host'] ?? '') . rtrim($dir, '/') . '/' . $url;
    }
}
