<?php

namespace support;

class MovieParser
{
    /**
     * 从URL解析电影信息
     */
    public static function parse(string $url): array
    {
        $result = [
            'success' => false,
            'title' => '',
            'description' => '',
            'poster_url' => ''
        ];

        try {
            // 设置超时和用户代理
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $html = @file_get_contents($url, false, $context);

            if ($html === false) {
                return $result;
            }

            // 解析HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

            // 尝试获取标题
            $title = self::extractTitle($dom, $url);
            if ($title) {
                $result['title'] = $title;
                $result['success'] = true;
            }

            // 尝试获取描述
            $description = self::extractDescription($dom);
            if ($description) {
                $result['description'] = $description;
            }

            // 尝试获取海报图片
            $posterUrl = self::extractPosterUrl($dom, $url);
            if ($posterUrl) {
                $result['poster_url'] = $posterUrl;
            }

        } catch (\Exception $e) {
            // 解析失败，返回失败结果
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
        if ($ogTitle->length > 0) {
            return trim($ogTitle->item(0)->nodeValue);
        }

        // 尝试从 title 标签获取
        $titleTags = $dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            $title = trim($titleTags->item(0)->nodeValue);
            // 清理常见的标题后缀
            $title = preg_replace('/ - .*$/', '', $title);
            $title = preg_replace('/ \| .*$/', '', $title);
            return $title;
        }

        // 从URL中提取标题
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $parts = explode('/', trim($path, '/'));
            $lastPart = end($parts);
            return ucfirst(str_replace(['-', '_'], ' ', $lastPart));
        }

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
        if ($ogDesc->length > 0) {
            return trim($ogDesc->item(0)->nodeValue);
        }

        // 尝试从 meta description 获取
        $metaDesc = $xpath->query('//meta[@name="description"]/@content');
        if ($metaDesc->length > 0) {
            return trim($metaDesc->item(0)->nodeValue);
        }

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
        if ($ogImage->length > 0) {
            $imageUrl = trim($ogImage->item(0)->nodeValue);
            return self::resolveUrl($imageUrl, $baseUrl);
        }

        // 尝试从第一个大图获取
        $images = $xpath->query('//img[@src]');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && !str_contains($src, 'icon') && !str_contains($src, 'logo')) {
                return self::resolveUrl($src, $baseUrl);
            }
        }

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
