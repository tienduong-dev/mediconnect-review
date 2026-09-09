<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\LogsAdminActions;
use App\Http\Controllers\Admin\Concerns\SortsListings;
use App\Models\MedicalContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Health articles shown on the public site.
 *
 * A row is only visible to visitors when status = 'Published' AND
 * published_at is set AND published_at <= now(), which is the exact
 * condition MedicalContentController (public) filters on. Everything in
 * this class keeps those three values consistent, so an article can never
 * end up "Published" but invisible, or scheduled but already leaking.
 *
 * Nothing is hard deleted while it is live: Published goes to Archived,
 * and only a Draft or Archived row can be removed for good.
 */
class MedicalContentController extends Controller
{
    use SortsListings;
    use LogsAdminActions;

    /** Mirrors the content_type ENUM in the medical_contents table. */
    public const TYPES = [
        'Disease'          => 'Disease',
        'Prevention'       => 'Prevention',
        'Cure'             => 'Treatment / Cure',
        'MedicalNews'      => 'Medical news',
        'MedicalInvention' => 'Medical invention',
    ];

    /** Mirrors the status ENUM in the medical_contents table. */
    public const STATUSES = ['Draft', 'Published', 'Archived'];

    private const IMAGE_DIR = 'medical-contents';

    public function index(Request $req)
    {
        $query = MedicalContent::with('author:id,name');

        if ($req->filled('keyword')) {
            $keyword = trim($req->keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('summary', 'like', '%' . $keyword . '%');
            });
        }

        if (in_array($req->input('content_type'), array_keys(self::TYPES), true)) {
            $query->where('content_type', $req->input('content_type'));
        }

        if (in_array($req->input('status'), self::STATUSES, true)) {
            $query->where('status', $req->input('status'));
        }

        $this->applySort(
            $query,
            $req,
            ['title', 'content_type', 'status', 'published_at', 'created_at'],
            'created_at',
            'desc'
        );

        $contents = $query->paginate($this->perPage($req))->withQueryString();

        return view('admin.medical-contents.index', [
            'contents' => $contents,
            'types'    => self::TYPES,
            'statuses' => self::STATUSES,
            'counts'   => $this->statusCounts(),
        ]);
    }

    public function show($id)
    {
        $content = MedicalContent::with('author:id,name')->findOrFail($id);

        return view('admin.medical-contents.show', [
            'content' => $content,
            'types'   => self::TYPES,
        ]);
    }

    public function create()
    {
        return view('admin.medical-contents.form', [
            'content' => new MedicalContent(['status' => 'Draft', 'content_type' => 'Disease']),
            'types'   => self::TYPES,
        ]);
    }

    public function store(Request $req)
    {
        $data = $this->validated($req);

        $content = new MedicalContent();
        $content->author_id = auth()->id();
        $content->slug      = $this->uniqueSlug($data['title']);

        $this->fill($content, $data, $req);
        $content->save();

        return redirect('Admin/MedicalContents/Show/' . $content->id)
            ->with('success', 'Article created as ' . $content->status . '.');
    }

    public function edit($id)
    {
        $content = MedicalContent::findOrFail($id);

        return view('admin.medical-contents.form', [
            'content' => $content,
            'types'   => self::TYPES,
        ]);
    }

    public function update(Request $req, $id)
    {
        $content = MedicalContent::findOrFail($id);
        $data    = $this->validated($req, $content->id);

        // The slug is part of the public URL, so it only moves when the
        // title actually changes. Otherwise existing links would break.
        if ($data['title'] !== $content->title) {
            $content->slug = $this->uniqueSlug($data['title'], $content->id);
        }

        $this->fill($content, $data, $req);
        $content->save();

        return redirect('Admin/MedicalContents/Show/' . $content->id)
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Draft or Archived -> Published.
     * published_at may be a future date, which schedules the article: the
     * public query compares published_at against now(), so it stays hidden
     * until that moment without any cron job.
     */
    public function publish(Request $req, $id)
    {
        $content = MedicalContent::findOrFail($id);

        if ($content->status === 'Published') {
            return back()->with('error', 'This article is already published.');
        }

        $validated = $req->validate([
            'published_at' => ['nullable', 'date'],
        ]);

        $content->status       = 'Published';
        $content->published_at = !empty($validated['published_at'])
            ? $validated['published_at']
            : now();
        $content->save();

        $message = $content->published_at->isFuture()
            ? 'Scheduled. The article goes live on ' . $content->published_at->format('Y-m-d H:i') . '.'
            : 'Article is now live on the public site.';

        return back()->with('success', $message);
    }

    /**
     * Published -> Archived. The row and its history are kept; the article
     * simply stops matching the public query.
     */
    public function archive($id)
    {
        $content = MedicalContent::findOrFail($id);

        if ($content->status === 'Archived') {
            return back()->with('error', 'This article is already archived.');
        }

        $content->status = 'Archived';
        $content->save();

        return back()->with('success', 'Article archived. It is no longer visible to visitors.');
    }

    /** Archived -> Draft, so it can be edited before going live again. */
    public function restore($id)
    {
        $content = MedicalContent::findOrFail($id);

        if ($content->status !== 'Archived') {
            return back()->with('error', 'Only an archived article can be moved back to draft.');
        }

        $content->status = 'Draft';
        $content->save();

        return back()->with('success', 'Article moved back to Draft.');
    }

    /**
     * Permanent removal, allowed only for a Draft or Archived article.
     * A live article must be archived first, so a single misclick can never
     * erase something visitors are reading.
     */
    public function destroy($id)
    {
        $content = MedicalContent::findOrFail($id);

        if ($content->status === 'Published') {
            return back()->with('error', 'Archive this article before deleting it.');
        }

        $this->deleteImage($content->featured_image);
        $title = $content->title;

        $this->logAdminAction('medical_content.deleted', [
            'content_id' => $content->id,
            'slug'       => $content->slug,
            'title'      => $title,
        ]);

        $content->delete();

        return redirect('Admin/MedicalContents')
            ->with('success', 'Article "' . $title . '" deleted permanently.');
    }

    // ------------------------------------------------------------------

    private function fill(MedicalContent $content, array $data, Request $req): void
    {
        $content->content_type = $data['content_type'];
        $content->title        = $data['title'];
        $content->summary      = $data['summary'] ?? null;
        $content->body         = $data['body'];
        $content->source_url   = $data['source_url'] ?? null;

        if ($req->hasFile('featured_image')) {
            $this->deleteImage($content->featured_image);
            // Stored on the "public" disk, because the patient view builds
            // the URL as asset('storage/' . featured_image).
            $content->featured_image = $req->file('featured_image')
                ->store(self::IMAGE_DIR, 'public');
        } elseif ($req->boolean('remove_image')) {
            $this->deleteImage($content->featured_image);
            $content->featured_image = null;
        }

        $status = $data['status'];

        if ($status === 'Published') {
            $content->published_at = !empty($data['published_at'])
                ? $data['published_at']
                : ($content->published_at ?: now());
        }

        // Leaving Published clears the date, so a later re-publish cannot
        // silently reuse an old timestamp.
        if ($status !== 'Published' && $content->status === 'Published') {
            $content->published_at = null;
        }

        $content->status = $status;
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'title'          => ['required', 'string', 'max:255', Rule::unique('medical_contents', 'title')->ignore($ignoreId)],
            'content_type'   => ['required', Rule::in(array_keys(self::TYPES))],
            'summary'        => ['nullable', 'string', 'max:500'],
            // Rendered by the public view as nl2br(e($body)), so this is
            // plain text on purpose. HTML would be escaped, not executed.
            'body'           => ['required', 'string', 'max:65000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'source_url'     => ['nullable', 'url', 'max:1000'],
            'status'         => ['required', Rule::in(self::STATUSES)],
            'published_at'   => ['nullable', 'date'],
        ], [
            'title.required'      => 'Title is required.',
            'title.unique'        => 'Another article already uses this title.',
            'body.required'       => 'The article body cannot be empty.',
            'featured_image.image' => 'The featured image must be a JPG, PNG or WEBP file.',
            'featured_image.max'  => 'The featured image must be 2 MB or smaller.',
            'source_url.url'      => 'The source must be a full URL, starting with http:// or https://.',
        ]);
    }

    private function uniqueSlug(string $title, $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i    = 2;

        while (
            MedicalContent::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Counts per status, used for the filter tabs on the list screen. */
    private function statusCounts(): array
    {
        $rows = MedicalContent::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts = ['All' => array_sum($rows)];

        foreach (self::STATUSES as $status) {
            $counts[$status] = $rows[$status] ?? 0;
        }

        return $counts;
    }
}
