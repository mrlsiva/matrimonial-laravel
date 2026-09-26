<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\MemberImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberImportController extends Controller
{
    public function __construct(private MemberImportService $members) {}

    public function create(): View
    {
        return view('admin.users.import', [
            'columns' => MemberImportService::COLUMNS,
            'maxRows' => MemberImportService::MAX_ROWS,
            'result' => session('import_result'),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
            foreach ($this->members->templateRows() as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'member-upload-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'update_existing' => ['boolean'],
            'mark_verified' => ['boolean'],
            'default_profile_status' => ['required', Rule::in([Profile::STATUS_APPROVED, Profile::STATUS_PENDING])],
        ], ['file.mimes' => 'Please upload a .csv file. In Excel use "Save As" → "CSV UTF-8 (Comma delimited)".']);

        @set_time_limit(300);
        $result = $this->members->import($request->file('file'), $request->user(), [
            'update_existing' => $request->boolean('update_existing'),
            'mark_verified' => $request->boolean('mark_verified'),
            'default_profile_status' => $data['default_profile_status'],
        ]);

        $summary = "{$result['created']} created, {$result['updated']} updated, {$result['payments']} payments recorded";
        $summary .= $result['errors'] ? ', '.count($result['errors']).' rows had errors (see below).' : '.';

        return redirect()->route('admin.users.import')
            ->with($result['errors'] ? 'warning' : 'success', 'Import finished: '.$summary)
            ->with('import_result', $result);
    }
}
