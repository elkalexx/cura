<?php

namespace App\Services\WhcSupplierOfferBlogService;

use App\Models\WhcSupplierOfferBlogLastSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhcSupplierSyncOfferBlogTableService
{
    public function sync(): bool
    {
        $targetConnection = DB::connection('mysql');
        $sourceConnection = DB::connection('whc_supplier');
        $whcOrgConnection = DB::connection('whc_org');

        try {
            $updateColumns = [
                'offer_no', 'offer_title', 'title', 'supplier', 'status',
                'offer_ext_status', 'is_brand', 'is_b_group_appr', 'is_approved',
                'description', 'created_at_whc', 'updated_at_whc', 'has_file',
                'file_name', 'file_path', 'updated_at',
            ];

            $latestFileSubquery = $sourceConnection->table('offer_blog_file as obf_sub')
                ->select(
                    'obf_sub.offer_blog_id',
                    DB::raw('MAX(obf_sub.file_id) as last_file_id')
                )
                ->groupBy('obf_sub.offer_blog_id');

            $targetConnection->transaction(function () use ($sourceConnection, $whcOrgConnection, $updateColumns, $latestFileSubquery) {

                $sourceConnection->table('offer_blog as ob')
                    ->leftJoinSub($latestFileSubquery, 'latest_obf', 'ob.id', '=', 'latest_obf.offer_blog_id')
                    ->leftJoin('offer_blog_file as obf', function ($join) {
                        $join->on('latest_obf.offer_blog_id', '=', 'obf.offer_blog_id')
                            ->on('latest_obf.last_file_id', '=', 'obf.file_id');
                    })
                    ->leftJoin('file as f', function ($join) {
                        $join->on('obf.file_id', '=', 'f.id')
                            ->where('f.type', 'like', 'image%');
                    })
                    ->leftJoin('offer_ext as oe', 'ob.offer_no', '=', 'oe.offer_no')
                    ->select(
                        'ob.id', 'ob.offer_no', 'ob.title', 'ob.status', 'ob.description',
                        'ob.created_on', 'ob.updated_on',
                        DB::raw('f.id IS NOT NULL as has_file'),
                        'f.file_name', DB::raw('f.path as file_path'),
                        'oe.status as offer_ext_status', 'oe.is_brand',
                        'oe.is_b_group_appr', 'oe.is_approved'
                    )
                    ->orderBy('ob.id')
                    ->chunk(1000, function ($offerBlogs) use ($whcOrgConnection, $updateColumns) {
                        if ($offerBlogs->isEmpty()) {
                            return;
                        }

                        $offerNumbers = $offerBlogs->pluck('offer_no')->unique()->filter()->toArray();
                        $orgData = collect();

                        if (! empty($offerNumbers)) {
                            $orgData = $whcOrgConnection->table('offers as o')
                                ->whereIn('o.offer_sid', $offerNumbers)
                                ->leftJoin('suppliers as s', 'o.supplier_id', '=', 's.id')
                                ->select('o.offer_sid', 'o.offer as offer_title', 's.name as supplier')
                                ->get()
                                ->keyBy('offer_sid');
                        }

                        $mergedData = $offerBlogs->map(function ($blog) use ($orgData) {
                            $matchingOrgData = $orgData->get($blog->offer_no);

                            return [
                                'id' => $blog->id,
                                'offer_no' => $blog->offer_no,
                                'title' => $blog->title,
                                'status' => $blog->status,
                                'description' => $blog->description,
                                'created_at_whc' => $blog->created_on,
                                'updated_at_whc' => $blog->updated_on,
                                'has_file' => (bool) $blog->has_file,
                                'file_name' => $blog->file_name,
                                'file_path' => $blog->file_path,
                                'is_sold' => false, // Default to false
                                'offer_ext_status' => $blog->offer_ext_status,
                                'is_brand' => $blog->is_brand,
                                'is_b_group_appr' => $blog->is_b_group_appr,
                                'is_approved' => $blog->is_approved,
                                // Data from the second server (whc_org)
                                'offer_title' => $matchingOrgData->offer_title ?? null,
                                'supplier' => $matchingOrgData->supplier ?? null,
                            ];
                        });

                        $sourceIds = $mergedData->pluck('id')->toArray();
                        $soldIds = DB::connection('mysql')->table('whc_supplier_offer_blogs')
                            ->whereIn('id', $sourceIds)
                            ->where('is_sold', true)
                            ->pluck('id')
                            ->toArray();

                        $rowsToUpsert = $mergedData->whereNotIn('id', $soldIds);

                        if ($rowsToUpsert->isEmpty()) {
                            return;
                        }

                        $now = now();
                        $insertData = $rowsToUpsert->map(function ($row) use ($now) {
                            $row['created_at'] = $now;
                            $row['updated_at'] = $now;

                            return $row;
                        })->toArray();

                        DB::connection('mysql')->table('whc_supplier_offer_blogs')->upsert(
                            $insertData,
                            ['id'],
                            $updateColumns
                        );
                    });
            });

            WhcSupplierOfferBlogLastSync::updateOrCreate(
                ['id' => 1],
                ['last_synced' => now()]
            );

            return true;
        } catch (Throwable $e) {
            Log::error("Something went wrong while syncing whc_supplier_offer_blogs: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
