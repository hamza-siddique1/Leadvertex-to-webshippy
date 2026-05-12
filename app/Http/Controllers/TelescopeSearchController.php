<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelescopeSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('telescope_entries');

        if ($request->has('method') && !empty($request->method)) {
            $query->WhereRaw("JSON_EXTRACT(`content`, '$.method') = ?", [$request->method]);
        }

        if ($request->has('resposne_status') && !empty($request->resposne_status)) {
            $query->WhereRaw("JSON_EXTRACT(`content`, '$.response_status') = ?", [(int) $request->resposne_status]);
        } else {
            $query->WhereRaw("JSON_EXTRACT(`content`, '$.response_status') = ?", [200]);
        }

        if ($request->has('uri') && !empty($request->uri)) {
            $query->WhereRaw("JSON_EXTRACT(`content`, '$.uri') = ?", [$request->uri]);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->whereRaw("JSON_EXTRACT(`content`, '$.payload.id') = ?", [$request->search])
                    ->orWhereRaw("JSON_EXTRACT(`content`, '$.payload.variables.orderId') = ?", [$request->search]);
            });
        }

        $results = $query->paginate(50);

        $query= DB::table('api_logs')->WhereRaw("JSON_EXTRACT(`request_body`, '$.tracking') = ?", [$request->search]);

        $api_logs = $query->paginate(50);

        return view('telescope.search', compact('results', 'api_logs'));
    }
}
