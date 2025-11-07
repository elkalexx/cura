<?php

namespace App\Http\Controllers;

use App\Models\WhcSupplierOfferBlog;
use App\Models\WhcSupplierOfferBlogLastSync;
use App\Models\WhcSupplierOfferBlogMagento;
use App\Services\MagentoService\MagentoBlogService;
use App\Services\WhcSupplierOfferBlogService\WhcSupplierSyncOfferBlogTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WhcSupplierBlogController extends Controller
{
    public function index(Request $request)
    {
        $whcNotActiveButMagentoActive = $request->boolean('whc_not_active_but_magento_active');
        $whcActiveButWhcNotApproved = $request->boolean('whc_active_but_whc_not_approved');
        $whcActiveAndWhcApproved = $request->boolean('whc_active_and_whc_approved');

        $query = WhcSupplierOfferBlog::with('whcSupplierOfferBlogMagento')->orderBy('id', 'DESC');

        $lastSyncRecord = WhcSupplierOfferBlogLastSync::first();

        $lastSynced = $lastSyncRecord?->last_synced
            ?->toIso8601String();

        if ($whcNotActiveButMagentoActive) {
            $query->where('offer_ext_status', '!=', 1)
                ->where('is_sold', 0)
                ->whereHas('whcSupplierOfferBlogMagento', function ($subQuery) {
                    $subQuery->where('status', 1);
                });
        } elseif ($whcActiveButWhcNotApproved) {
            $query->where('offer_ext_status', 1)
                ->where('is_approved', 0);
        } elseif ($whcActiveAndWhcApproved) {
            $query->where('offer_ext_status', 1)
                ->where('is_approved', 1);
        } else {
            $query->when($request->filled('statuses'), function ($q) use ($request) {
                $q->whereIn('offer_ext_status', $request->statuses);
            });
        }

        $blogs = $query->get();

        $whcSupplierUrl = Config::string('services.whc_supplier.url');

        return Inertia::render('whc_supplier_blogs/Index', [
            'whcSupplierBlogs' => $blogs,
            'fileUrlPrefix' => '/whc-files',
            'filters' => $request->only(['whc_not_active_but_magento_active', 'statuses', 'whc_active_but_whc_not_approved', 'whc_active_and_whc_approved']),
            'filterOptions' => ['statuses', []],
            'lastSynced' => $lastSynced,
            'whcSupplierUrl' => $whcSupplierUrl,
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
            'status' => 1,
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

        $blog->is_sold = false;
        $blog->save();

        return redirect()->back();
    }

    public function updateBlogAsSold(WhcSupplierOfferBlog $blog, MagentoBlogService $magentoBlogService)
    {
        $wasSuccessful = $magentoBlogService->updateBlogPostAsSold($blog);

        if (! $wasSuccessful) {
            throw ValidationException::withMessages([
                'service' => 'Something went wrong while updating the blog in Magento. Please check the logs for more details.',
            ]);
        }

        $blog->is_sold = true;
        $blog->save();

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

        if (! str_starts_with(realpath($fullPath), $baseDir)) {
            abort(403, 'Forbidden');
        }

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
