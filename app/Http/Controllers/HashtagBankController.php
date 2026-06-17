<?php

namespace App\Http\Controllers;

use App\Models\HashtagBank;
use Illuminate\Http\Request;

class HashtagBankController extends Controller
{
    /** Specialties offered (mirrors client industries + common ones). */
    private const SPECIALTIES = ['dermatologist', 'ivf', 'dental', 'cardiology', 'orthopedics', 'general'];

    public function index(Request $request)
    {
        $specialty = $request->get('specialty', self::SPECIALTIES[0]);

        $tags = HashtagBank::where('specialty', $specialty)
            ->orderByRaw("FIELD(performance,'high','medium','low')")
            ->orderBy('category')
            ->orderBy('tag')
            ->get();

        $counts = HashtagBank::selectRaw('specialty, count(*) as c')
            ->groupBy('specialty')->pluck('c', 'specialty');

        $specialties = self::SPECIALTIES;

        return view('hashtags.index', compact('tags', 'specialty', 'specialties', 'counts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'specialty'   => 'required|string|max:80',
            'tags'        => 'required|string',                 // space/newline separated
            'category'    => 'required|in:trending,niche,brand',
            'performance' => 'required|in:high,medium,low',
        ]);

        $added = 0;
        foreach (preg_split('/[\s,]+/', $data['tags']) as $raw) {
            $tag = HashtagBank::normalizeTag((string) $raw);
            if ($tag === '' || $tag === '#') continue;

            HashtagBank::updateOrCreate(
                ['specialty' => $data['specialty'], 'tag' => $tag],
                [
                    'category'         => $data['category'],
                    'performance'      => $data['performance'],
                    'last_reviewed_at' => now(),
                ]
            );
            $added++;
        }

        return back()->with('success', "{$added} hashtag(s) saved to the {$data['specialty']} bank.");
    }

    public function update(Request $request, HashtagBank $hashtag)
    {
        $data = $request->validate([
            'category'    => 'nullable|in:trending,niche,brand',
            'performance' => 'nullable|in:high,medium,low',
            'avg_reach'   => 'nullable|integer|min:0',
            'notes'       => 'nullable|string|max:255',
        ]);
        $data['last_reviewed_at'] = now();
        $hashtag->update(array_filter($data, fn ($v) => $v !== null));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Hashtag updated.');
    }

    public function destroy(HashtagBank $hashtag)
    {
        $hashtag->delete();
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Hashtag removed.');
    }

    /**
     * JSON: suggest the best hashtags for a specialty (used by the post creator).
     * GET /hashtag-bank/suggest?specialty=dermatologist&limit=20
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'specialty' => 'required|string|max:80',
            'limit'     => 'nullable|integer|min:1|max:60',
        ]);

        $limit = (int) ($request->get('limit', 25));

        $tags = HashtagBank::where('specialty', $request->get('specialty'))
            ->orderByRaw("FIELD(performance,'high','medium','low')")
            ->limit($limit)
            ->get(['tag', 'category', 'performance']);

        return response()->json([
            'success'  => true,
            'count'    => $tags->count(),
            'trending' => $tags->where('category', 'trending')->pluck('tag')->values(),
            'niche'    => $tags->where('category', 'niche')->pluck('tag')->values(),
            'brand'    => $tags->where('category', 'brand')->pluck('tag')->values(),
            'all'      => $tags->pluck('tag')->values(),
        ]);
    }
}
