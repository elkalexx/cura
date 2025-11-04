<?php

namespace App\Console\Commands;

use App\Services\WhcSupplierOfferBlogService\WhcSupplierSyncOfferBlogTableService;
use Illuminate\Console\Command;

class WhcSupplierSyncOfferBlogTableCommand extends Command
{
    protected $signature = 'whc-supplier:sync-offer-blog-table';

    protected $description = 'Syncs the offer blog table from WHC_Supplier';

    public function handle(WhcSupplierSyncOfferBlogTableService $syncOfferBlogTable): int
    {
        $syncOfferBlogTable->sync();

        return self::SUCCESS;
    }
}
