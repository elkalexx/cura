<?php

namespace App\Services\MagentoService\Api;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class MagentoBlogService
{
    private string $magentoUrl;

    private string $token;

    private const CREATE_BLOG_URL = '/V1/g5-whc-blog/create-blog';

    private const ACTIVATE_BLOG_URL = '/V1/g5-whc-blog/activate-blog';

    private const DEACTIVATE_BLOG_URL = '/V1/g5-whc-blog/deactivate-blog';

    private const UPDATE_BLOG_URL = '/V1/g5-whc-blog/update-blog';

    public function __construct(
        MagentoTokenService $magentoTokenService
    ) {
        $this->magentoUrl = Config::string('services.magento.apiRequestUrl');
        $this->token = $magentoTokenService->getToken();
    }

    public function createBlog(array $data)
    {
        $blogData = [
            'blogCreateItem' => [
                'status' => $data['status'],
                'name' => $data['name'],
                'short_content' => $data['short_content'],
                'content' => $data['content'],
                'url_key' => $data['url_key'],
            ],
        ];

        $result = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
        ])->post($this->magentoUrl.self::CREATE_BLOG_URL, $blogData);

        if (! $result->successful()) {
            throw new Exception('Could not create blog post '.json_encode($result->body()));
        }

        return json_decode($result->body(), true);
    }

    public function activateBlogPost(array $data)
    {
        $blogData = [
            'blogActivateItem' => [
                'post_id' => $data['post_id'],
            ],
        ];

        $result = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
        ])->post($this->magentoUrl.self::ACTIVATE_BLOG_URL, $blogData);

        if (! $result->successful()) {
            throw new Exception('Could not activate blog post '.json_encode($result->body()));
        }

        return true;
    }

    public function deactivateBlogPost(array $data)
    {
        $blogData = [
            'blogDeactivateItem' => [
                'post_id' => $data['post_id'],
            ],
        ];

        $result = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
        ])->post($this->magentoUrl.self::DEACTIVATE_BLOG_URL, $blogData);

        if (! $result->successful()) {
            throw new Exception('Could not activate blog post '.json_encode($result->body()));
        }

        return true;
    }

    public function updateBlogPost(array $data)
    {
        $blogData = [
            'blogUpdateItem' => [
                'post_id' => $data['post_id'],
                'name' => $data['name'],
                'short_content' => $data['short_content'],
                'content' => $data['content'],
                'url_key' => $data['url_key'],
            ],
        ];

        $result = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
        ])->post($this->magentoUrl.self::UPDATE_BLOG_URL, $blogData);

        if (! $result->successful()) {
            throw new Exception('Could not update blog post '.json_encode($result->body()));
        }

        return true;
    }
}
