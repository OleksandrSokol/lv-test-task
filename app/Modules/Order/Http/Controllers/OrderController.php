<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Http\Requests\ListOrdersRequest;
use App\Modules\Order\Http\Resources\OrderListItemResource;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Queries\FindOrderByExternalIdQuery;
use App\Modules\Order\Queries\SearchOrdersQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, SearchOrdersQuery $query): AnonymousResourceCollection
    {
        return OrderListItemResource::collection($query->handle($request->filter()));
    }

    public function show(string $id, FindOrderByExternalIdQuery $query): OrderResource
    {
        return new OrderResource($query->handle($id));
    }
}
