<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Member;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminLog::query();

        if ($kw = trim((string) $request->input('kw'))) {
            $query->where(function ($q) use ($kw) {
                $q->where('description', 'like', "%{$kw}%")
                    ->orWhere('admin_name', 'like', "%{$kw}%")
                    ->orWhere('action', 'like', "%{$kw}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }

    /** 推荐关系树 */
    public function team(Request $request)
    {
        $rootId = (int) $request->input('root', 0);

        $roots = Member::query()->where(function ($q) {
            $q->where('pid', 0)->orWhereNull('pid');
        })->orderBy('id')->get();

        $root = $rootId ? Member::find($rootId) : $roots->first();

        $tree = $root ? $this->buildTree($root->id, 0) : [];

        return view('admin.logs.team', compact('roots', 'root', 'tree'));
    }

    private function buildTree(int $pid, int $depth): array
    {
        if ($depth > 4) {
            return [];
        }

        $nodes = [];
        foreach (Member::query()->where('pid', $pid)->orderBy('id')->get() as $m) {
            $nodes[] = [
                'member' => $m,
                'depth' => $depth,
                'children' => $this->buildTree($m->id, $depth + 1),
            ];
        }

        return $nodes;
    }
}
