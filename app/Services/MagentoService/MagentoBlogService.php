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

        if ($blog->has_file) {
            $baseDir = config('services.whc_supplier.file_directory');
            $baseDirReal = $baseDir ? realpath((string) $baseDir) : false;

            if (! $baseDirReal) {
                Log::error("WHC Supplier file directory is not configured or invalid: '{$baseDir}'. Cannot attach image for blog ID: {$blog->id}");
            } else {
                $baseDirReal = rtrim($baseDirReal, DIRECTORY_SEPARATOR);

                $candidate = $baseDirReal.DIRECTORY_SEPARATOR.ltrim((string) $blog->file_path, DIRECTORY_SEPARATOR);
                $fullFilePath = realpath($candidate);

                if (! $fullFilePath || ! str_starts_with($fullFilePath, $baseDirReal.DIRECTORY_SEPARATOR)) {
                    Log::warning("Resolved file path escapes base dir or doesn't exist. Blog ID: {$blog->id}, candidate: {$candidate}, resolved: ".var_export($fullFilePath, true));
                } elseif (! is_file($fullFilePath) || ! is_readable($fullFilePath)) {
                    Log::warning("File not a readable regular file for blog ID: {$blog->id}. Path: {$fullFilePath}");
                } else {
                    try {
                        $fileContent = file_get_contents($fullFilePath);
                        if ($fileContent === false) {
                            Log::warning("Failed to read file content for blog post image: {$fullFilePath}");
                        } else {
                            $blogData['featured_image_base64'] = base64_encode($fileContent);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Error reading file for blog ID: {$blog->id}. Path: {$fullFilePath}. Error: {$e->getMessage()}");
                    }
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
