<?php

namespace App\Http\Controllers;

use App\Services\CatalogCsvService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogCsvController extends Controller
{
    public function form(): View
    {
        Gate::authorize('catalog-import');

        return view('admin.csv');
    }

    public function import(Request $r): RedirectResponse
    {
        Gate::authorize('catalog-import');
        $r->validate(['csv' => 'required|file|mimes:csv,txt|max:5120']);
        $count = app(CatalogCsvService::class)->import($r->file('csv')->getRealPath());

        return back()->with('status', "$count variants imported.");
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('catalog-import');

        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'wb');
            app(CatalogCsvService::class)->export($stream);
            fclose($stream);
        }, 'variants.csv', ['Content-Type' => 'text/csv']);
    }
}
