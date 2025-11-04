<?php

namespace App\Http\Controllers;

use App\Models\WhcSupplierOfferBlog;
use App\Models\WhcSupplierOfferBlogMagento;
use App\Services\MagentoService\MagentoBlogService;
use App\Services\WhcSupplierOfferBlogService\WhcSupplierSyncOfferBlogTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WhcSupplierBlogController extends Controller
{
    public function index()
    {
        $blogs = WhcSupplierOfferBlog::with('whcSupplierOfferBlogMagento')->orderBy('id', 'DESC')->get();

        return Inertia::render('whc_supplier_blogs/Index', [
            'whcSupplierBlogs' => $blogs,
            'fileUrlPrefix' => '/whc-files',
        ]);
    }

    public function sync(WhcSupplierSyncOfferBlogTableService $syncOfferBlogTable)
    {
        $wasSuccessful = $syncOfferBlogTable->sync();

        if (! $wasSuccessful) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while syncing the blogs. Please check the logs for more details.',
            ]);
        }

        return redirect()->back();
    }

    public function createInMagento(WhcSupplierOfferBlog $blog, MagentoBlogService $whcSupplierOfferBlogService)
    {
        $urlKey = Str::slug($blog->title);
        $createdBlog = $whcSupplierOfferBlogService->createBlogPost($blog, $urlKey);

        if (! $createdBlog) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while creating the blog in Magento. Please check the logs for more details.',
            ]);
        }

        WhcSupplierOfferBlogMagento::create([
            'whc_supplier_offer_blog_id' => $blog->id,
            'magento_blog_id' => $createdBlog['post_id'],
            'status' => 0,
            'url_key' => $urlKey,
            'created_magento_at' => now(),
        ]);

        return redirect()->back();
    }

    public function activateInMagento(WhcSupplierOfferBlog $blog, MagentoBlogService $whcSupplierOfferBlogService)
    {
        $wasSuccessful = $whcSupplierOfferBlogService->activateBlogPost($blog);

        if (! $wasSuccessful) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while activating the blog in Magento. Please check the logs for more details.',
            ]);
        }

        $blog->whcSupplierOfferBlogMagento->status = 1;
        $blog->whcSupplierOfferBlogMagento->save();

        return redirect()->back();
    }

    public function deactivateInMagento(WhcSupplierOfferBlog $blog, MagentoBlogService $whcSupplierOfferBlogService)
    {
        $wasSuccessful = $whcSupplierOfferBlogService->deactivateBlogPost($blog);

        if (! $wasSuccessful) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while deactivating the blog in Magento. Please check the logs for more details.',
            ]);
        }

        $blog->whcSupplierOfferBlogMagento->status = 0;
        $blog->whcSupplierOfferBlogMagento->save();

        return redirect()->back();
    }

    public function updateInMagento(WhcSupplierOfferBlog $blog, MagentoBlogService $whcSupplierOfferBlogService)
    {
        $wasSuccessful = $whcSupplierOfferBlogService->updateBlogPost($blog);

        if (! $wasSuccessful) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while updating the blog in Magento. Please check the logs for more details.',
            ]);
        }

        $blog->whcSupplierOfferBlogMagento->touch();

        return redirect()->back();
    }

    public function showFile(Request $request)
    {
        $path = $request->query('path');
        if (! $path) {
            abort(404);
        }

        $baseDir = config('services.whc_supplier.file_directory');

        if (! $baseDir) {
            abort(404);
        }

        $fullPath = $baseDir.'/'.ltrim($path, '/');
        dd($fullPath);

        if (! str_starts_with(realpath($fullPath), $baseDir)) {
            abort(403, 'Forbidden');
        }

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
