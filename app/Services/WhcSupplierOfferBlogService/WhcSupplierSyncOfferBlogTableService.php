<?php

namespace App\Services\WhcSupplierOfferBlogService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhcSupplierSyncOfferBlogTableService
{
    public function sync(): bool
    {
        $targetConnection = DB::connection('mysql');
        $sourceConnection = DB::connection('whc_supplier');

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
            ];

            $updateColumns = [
                'offer_no',
                'title',
                'status',
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

            $targetConnection->transaction(function () use ($sourceConnection, $selectClause, $updateColumns, $latestFileSubquery) {
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
                    });

                $sourceQuery->select($selectClause)
                    ->orderBy('ob.id')
                    ->chunk(1000, function ($rows) use ($updateColumns) {
                        if ($rows->isEmpty()) {
                            return;
                        }

                        $now = now();

                        $insertData = $rows->map(function ($row) use ($now) {
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

            return true;
        } catch (Throwable $e) {
            Log::error("Something went wrong while syncing the whc_supplier_offer_blogs table: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
