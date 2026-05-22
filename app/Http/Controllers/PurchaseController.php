<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseCollectionResource;
use App\Http\Resources\PurchaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class PurchaseController extends Controller
{
    //
    public function __construct(private readonly FileStorageServiceInterface $fileStorageService)
    {
        //
    }
}
