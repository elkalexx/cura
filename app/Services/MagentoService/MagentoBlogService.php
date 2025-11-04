<?php

namespace App\Services\MagentoService;

use App\Models\WhcSupplierOfferBlog;
use Illuminate\Support\Facades\Log;

readonly class MagentoBlogService
{
    public function __construct(
        private Api\MagentoBlogService $magentoBlogService
    ) {}

    public function createBlogPost(WhcSupplierOfferBlog $blog, string $urlKey)
    {
        $contentWrapper = '<div class="blog-post-content prose prose-neutral max-w-none">%s</div>';

        $wrappedContent = sprintf($contentWrapper, $blog->description);
        $blogData = [
            'status' => $blog->status,
            'name' => $blog->title,
            'short_content' => '',
            'content' => $wrappedContent,
            'url_key' => $urlKey,
        ];

        if ($blog->has_file && ! empty($blog->file_path)) {

            $baseDir = config('services.whc_supplier.file_directory');

            if (! $baseDir) {
                Log::error('WHC Supplier file directory is not configured. Cannot attach image for blog ID: '.$blog->id);
            } else {
                $fullFilePath = $baseDir.'/'.ltrim($blog->file_path, '/');

                if (file_exists($fullFilePath) && is_readable($fullFilePath)) {
                    try {
                        $fileContent = file_get_contents($fullFilePath);

                        if ($fileContent !== false) {
                            $blogData['featured_image_base64'] = base64_encode($fileContent);
                        } else {
                            Log::warning('Failed to read file content for blog post image: '.$fullFilePath);
                        }
                    } catch (\Exception $e) {
                        Log::error('Something went wrong while reading file for blog ID: '.$blog->id.'. Error: '.$e->getMessage());
                    }
                } else {
                    Log::warning('File does not exist or is not readable for blog ID: '.$blog->id);
                }
            }

        }

        try {
            return $this->magentoBlogService->createBlog($blogData);
        } catch (\Exception $e) {
            Log::error('Something went wrong while creating blog post: '.$e->getMessage());

            return false;
        }
    }

    public function activateBlogPost(WhcSupplierOfferBlog $blog)
    {
        try {
            $this->magentoBlogService->activateBlogPost([
                'post_id' => $blog->whcSupplierOfferBlogMagento->magento_blog_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Something went wrong while activating blog post: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function deactivateBlogPost(WhcSupplierOfferBlog $blog)
    {
        try {
            $this->magentoBlogService->deactivateBlogPost([
                'post_id' => $blog->whcSupplierOfferBlogMagento->magento_blog_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Something went wrong while activating blog post: '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function updateBlogPost(WhcSupplierOfferBlog $blog)
    {
        $blogUpdateData = [
            'post_id' => $blog->whcSupplierOfferBlogMagento->magento_blog_id,
            'name' => $blog->title,
            'short_content' => '',
            'content' => $blog->description,
            'url_key' => $blog->whcSupplierOfferBlogMagento->url_key,
        ];

        try {
            $this->magentoBlogService->updateBlogPost($blogUpdateData);
        } catch (\Exception $e) {
            Log::error('Something went wrong while updating blog post: '.$e->getMessage());

            return false;
        }

        return true;
    }
}
