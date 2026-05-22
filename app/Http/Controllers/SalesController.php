<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesRequest;
use App\Http\Requests\UpdateSalesRequest;
use App\Http\Resources\SalesCollectionResource;
use App\Http\Resources\SalesResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class SalesController extends Controller
{
    //
    public function __construct(private readonly FileStorageServiceInterface $fileStorageService)
    {
        //
    }
}
