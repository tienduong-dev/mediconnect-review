<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;

/**
 * Shared sorting and page-size handling for admin list screens.
 *
 * The sort column and page size come from the query string, so both are
 * checked against a whitelist before they reach the query builder. Passing a
 * raw request value into orderBy() would be an SQL injection hole.
 */
trait SortsListings
{
    protected function applySort($query, Request $req, array $allowed, string $default, string $defaultDir = 'asc'): void
    {
        $sort = in_array($req->input('sort'), $allowed, true)
            ? $req->input('sort')
            : $default;

        $dir = $req->input('dir') === 'desc' ? 'desc' : 'asc';

        if (!$req->filled('sort')) {
            $dir = $defaultDir;
        }

        $query->orderBy($sort, $dir);
    }

    /** Page size, limited to a few sane values. */
    protected function perPage(Request $req, int $default = 10): int
    {
        $allowed = [10, 25, 50, 100];
        $value   = (int) $req->input('per_page', $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
