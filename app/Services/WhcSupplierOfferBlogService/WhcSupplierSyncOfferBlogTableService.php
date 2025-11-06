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
            $selectClause = [
                'ob.id',
                'ob.offer_no',
                'ob.title',
                'ob.status',
                'ob.description',
                DB::raw('ob.created_on as created_at_whc'),
                DB::raw('ob.updated_on as updated_at_whc'),
                DB::raw('f.id IS NOT NULL as has_file'),
                'f.file_name',
                DB::raw('f.path as file_path'),
                DB::raw('false as is_sold'),
                'o.offer as offer_title',
                's.name as supplier',
                'oe.status as offer_ext_status',
                'oe.is_brand',
                'oe.is_b_group_appr',
                'oe.is_approved',
            ];

            $updateColumns = [
                'offer_no',
                'offer_title',
                'title',
                'supplier',
                'status',
                'offer_ext_status',
                'is_brand',
                'is_b_group_appr',
                'is_approved',
                'description',
                'created_at_whc',
                'updated_at_whc',
                'has_file',
                'file_name',
                'file_path',
                'updated_at',
            ];

            $latestFileSubquery = $sourceConnection->table('offer_blog_file as obf_sub')
                ->select(
                    'obf_sub.offer_blog_id',
                    DB::raw('MAX(obf_sub.file_id) as last_file_id')
                )
                ->groupBy('obf_sub.offer_blog_id');

            $targetConnection->transaction(function () use ($sourceConnection, $whcOrgConnection, $selectClause, $updateColumns, $latestFileSubquery) {
                $whcOrgDbName = $whcOrgConnection->getDatabaseName();

                $sourceQuery = $sourceConnection->table('offer_blog as ob')
                    ->leftJoinSub($latestFileSubquery, 'latest_obf', function ($join) {
                        $join->on('ob.id', '=', 'latest_obf.offer_blog_id');
                    })
                    ->leftJoin('offer_blog_file as obf', function ($join) {
                        $join->on('latest_obf.offer_blog_id', '=', 'obf.offer_blog_id')
                            ->on('latest_obf.last_file_id', '=', 'obf.file_id');
                    })
                    ->leftJoin('file as f', function ($join) {
                        $join->on('obf.file_id', '=', 'f.id')
                            ->where('f.type', 'like', 'image%');
                    })
                    ->leftJoin("{$whcOrgDbName}.offers as o", 'ob.offer_no', '=', 'o.offer_sid')
                    ->leftJoin("{$whcOrgDbName}.suppliers as s", 'o.supplier_id', '=', 's.id')
                    ->leftJoin("{$whcOrgDbName}.offer_ext as oe", 'ob.offer_no', '=', 'oe.offer_no');

                $sourceQuery->select($selectClause)
                    ->orderBy('ob.id')
                    ->chunk(1000, function ($rows) use ($updateColumns) {
                        if ($rows->isEmpty()) {
                            return;
                        }

                        $sourceIds = $rows->pluck('id')->toArray();

                        $soldIds = DB::connection('mysql')->table('whc_supplier_offer_blogs')
                            ->whereIn('id', $sourceIds)
                            ->where('is_sold', true)
                            ->pluck('id')
                            ->toArray();

                        $rowsToUpsert = $rows->whereNotIn('id', $soldIds);

                        if ($rowsToUpsert->isEmpty()) {
                            return;
                        }

                        $now = now();

                        $insertData = $rowsToUpsert->map(function ($row) use ($now) {
                            $data = (array) $row;
                            $data['created_at'] = $now;
                            $data['updated_at'] = $now;

                            return $data;
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
            Log::error("Something went wrong while syncing the whc_supplier_offer_blogs table: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
