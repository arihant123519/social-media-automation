<?php

namespace App\Support;

/**
 * Canonical catalogue of every AI prompt the app sends to Gemini.
 *
 * These are the packaged defaults. When a matching row exists in the `prompts`
 * table it overrides the default (see {@see \App\Models\Prompt::render()}), so
 * the team can edit wording from the "Prompts" screen without code changes.
 *
 * Templates use {{ placeholder }} tokens. The `variables` list documents the
 * inputs each template expects, purely so the editor UI can show them — the
 * actual values are supplied by the calling controller/service at render time.
 */
class PromptRegistry
{
    public static function defaults(): array
    {
        return [

            // ───────────────────────── Post Creator ─────────────────────────
            'post.inspiration' => [
                'name'        => 'Post Creator — Inspiration ideas',
                'group'       => 'Post Creator',
                'description' => 'Generates hooks, hashtags, a script outline and a sample caption for a topic.',
                'variables'   => [
                    ['name' => 'brandBlock', 'description' => "Client brand-voice block (may be empty)."],
                    ['name' => 'typeLabel',  'description' => 'e.g. "Instagram Reels", "YouTube Shorts".'],
                    ['name' => 'keyword',    'description' => 'The topic / keyword the user entered.'],
                    ['name' => 'platform',   'description' => '"Instagram" or "YouTube".'],
                ],
                'template'    => <<<'TPL'
You are a top social media content strategist for Indian creators.
{{ brandBlock }}
Generate fresh inspiration ideas for {{ typeLabel }} on the topic: "{{ keyword }}".

⚠️ QUALITY MANDATE — read carefully before generating ⚠️

Write ALL your output (hooks, hashtags, script lines, sample_caption) in **CLEAN STANDARD ENGLISH ONLY** (with at most 1-2 widely-understood Hindi loanwords like "namaste" if natural — otherwise full English).

WHY: The user often copies your hooks/captions and submits them back. Your output goes through another spelling-check round. Hinglish/romanized-Hindi gets flagged as typos there. Clean English avoids this loop entirely.

EXAMPLES:
✅ "Try these 3 morning skincare steps for instant glow"
✅ "What 9 out of 10 dermatologists do every night — but never tell you"
❌ "Ye 3 cheezein try kro morning me" (romanized — high typo risk on re-check)
❌ "Skincare ki proper rutein follow karo" (will be flagged later)

PROOFREAD before finalizing: re-read every hook, hashtag, script line. Spell-check against standard English dictionary. Fix anything that looks off. Then output.

Return ONLY a JSON object with this exact shape (no markdown, no extra text):
{
  "keyword": "{{ keyword }}",
  "platform": "{{ platform }}",
  "hooks": [
    { "text": "Hook line 1", "type": "question" },
    { "text": "Hook line 2", "type": "shocking" },
    { "text": "Hook line 3", "type": "relatable" },
    { "text": "Hook line 4", "type": "curiosity" },
    { "text": "Hook line 5", "type": "challenge" }
  ],
  "hashtags": {
    "trending": ["#tag1", "#tag2", "#tag3", "#tag4", "#tag5"],
    "niche":    ["#tag6", "#tag7", "#tag8", "#tag9", "#tag10"],
    "brand":    ["#tag11", "#tag12", "#tag13"]
  },
  "script_outline": {
    "hook":   "First 3 seconds — strong opening line",
    "body":   "Middle 15-20 seconds — 3 punchy beats",
    "cta":    "Last 3 seconds — clear call to action"
  },
  "best_time_to_post": "Day and time window in IST, e.g. 'Wed/Thu 7-9 PM IST'",
  "sample_caption": "Full caption with emojis, 2-3 lines, compelling"
}
TPL,
            ],

            'post.trending' => [
                'name'        => 'Post Creator — Trending ideas grid',
                'group'       => 'Post Creator',
                'description' => 'Generates 10 trending post ideas with realistic engagement numbers.',
                'variables'   => [
                    ['name' => 'label',     'description' => 'e.g. "Instagram Reels", "Instagram photo posts".'],
                    ['name' => 'keyword',   'description' => 'The topic / keyword the user entered.'],
                    ['name' => 'mediaWord', 'description' => '"photo/image" or "short video/reel".'],
                    ['name' => 'mediaType', 'description' => '"IMAGE" or "VIDEO".'],
                ],
                'template'    => <<<'TPL'
Generate 10 trending {{ label }} ideas for the keyword "{{ keyword }}" targeting Indian creators.
These are {{ mediaWord }} ideas — the visual content is a {{ mediaWord }}, NOT any other format.

Write captions in CLEAN STANDARD ENGLISH ONLY (no romanized Hindi like "kro", "h", "rutein").
The user may copy these and re-submit them for scoring, so they must pass spell-check.

Return ONLY a JSON object with an "items" array (no markdown, no extra text):
{
  "items": [
    {
      "ref_id": "ai-1",
      "title": "compelling post title (<= 70 chars)",
      "caption": "full sample caption with emojis",
      "thumbnail": null,
      "likes": 12000,
      "comments": 350,
      "hashtags": ["#tag1", "#tag2", "#tag3", "#tag4", "#tag5"],
      "url": null,
      "media_type": "{{ mediaType }}"
    }
  ]
}
Use realistic engagement numbers for the niche. IDs ai-1 through ai-10.
TPL,
            ],

            'post.scorer' => [
                'name'        => 'Post Scorer — Health score & feedback',
                'group'       => 'Post Creator',
                'description' => 'Scores an uploaded post out of 100 with per-parameter feedback. Several inputs are pre-composed blocks built in code.',
                'variables'   => [
                    ['name' => 'brandVoice',      'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'taskLine',        'description' => 'Standalone vs. comparison task sentence (built in code).'],
                    ['name' => 'mediaLine',       'description' => 'What media is attached (video/image/none).'],
                    ['name' => 'previousBlock',   'description' => 'Previous-attempts context block (may be empty).'],
                    ['name' => 'bothSidesBlock',  'description' => 'Per-parameter "both sides" instructions (built in code).'],
                    ['name' => 'referenceBlock',  'description' => 'Trending reference details or standalone note.'],
                    ['name' => 'caption',         'description' => "The user's submitted caption."],
                    ['name' => 'hashtags',        'description' => "The user's submitted hashtags."],
                    ['name' => 'typeLabel',       'description' => 'Human label for the post type.'],
                ],
                'template'    => <<<'TPL'
You are a senior social media content analyst.
{{ brandVoice }}
{{ taskLine }}

MEDIA AVAILABLE: {{ mediaLine }}
{{ previousBlock }}

GLOBAL ANTI-GENERIC RULE: Every piece of feedback must reference a SPECIFIC element of the user's actual upload — a phrase from their caption, a visible thing in the frame, a specific hashtag they used or didn't use. Banned generic phrases: "add a strong hook", "incorporate background music", "improve pacing", "use trending hashtags" — these are useless unless tied to specifics like "your caption opens with 'today I want to' which is weak; try 'You won't believe what 3 days of X did to my skin'".

⚠️ QUALITY MANDATE FOR YOUR OWN GENERATED CONTENT (this is critical) ⚠️

CRITICAL: When you generate caption_variants, alternative_hooks, sample_caption, and new_reel_ideas — write them in **CLEAN STANDARD ENGLISH ONLY** (with maximum 1-2 well-known Hindi loanwords like "namaste", "yaar" — if and only if they're widely understood).

WHY: The user often copies your captions/hooks and submits them back as their own. Your output goes into another scoring round. If your output has typos or unusual Hinglish, it gets flagged as a spelling error in their next attempt — they get stuck in a loop. Clean English avoids this entirely.

EXAMPLES:
✅ GOOD: "Healthy glowing skin in just 7 days — try these 3 morning steps."
✅ GOOD: "Did you know? 9 out of 10 dermatologists recommend this one habit."
❌ AVOID: "Skincare ki rutein 7 din me change kar do" (romanized Hindi — high typo risk on re-scoring)
❌ AVOID: "Try kro ye 3 cheezein" (text-speak — flagged later)

The USER'S submitted caption can be Hinglish (that's fine, you score it leniently — see spelling_grammar rules). But YOUR GENERATED captions stay in clean English for safety.

PROOFREAD PROTOCOL — before finalizing JSON:
1. Re-read EVERY caption, hook, hashtag, reel-idea you wrote.
2. Spell-check every word against standard English dictionary.
3. Check grammar: subject-verb agreement, tense consistency, punctuation.
4. Make sure each item reads professionally — no auto-translation artifacts.
5. If anything looks off, FIX it before output.

This rule applies to: caption_variants, alternative_hooks, sample_caption, new_reel_ideas.title, new_reel_ideas.angle, new_reel_ideas.script_outline, quick_wins, strengths, suggestions, feedback. ALL English. ALL clean.

{{ bothSidesBlock }}

{{ referenceBlock }}

USER UPLOAD
- Caption: "{{ caption }}"
- Hashtags: "{{ hashtags }}"
- Attached media: {{ typeLabel }} (see attached file if present)

Be critical and concrete — do NOT inflate scores. Quote actual words from both sides when comparing.

Return ONLY a JSON object with this exact shape (no markdown, no extra text). Scores MUST sum to total_score and each MUST be within its max.

{
  "total_score": 0-100,
  "grade": "A+|A|B+|B|C|D|F",
  "summary": "2-3 sentence honest comparison summary",
  "parameters": {
    "hook_strength": {
      "score": 0-20, "max": 20, "label": "Hook Strength",
      "reference_says": "What the trending video does in its first 3 seconds / opening line",
      "your_post_says": "What the user's caption/visual does at the open",
      "feedback": "Comparison + what's weak",
      "suggestions": ["..."]
    },
    "caption_quality": {
      "score": 0-15, "max": 15, "label": "Caption Quality",
      "reference_says": "Tone/length/structure of trending caption-equivalent",
      "your_post_says": "Tone/length/structure of user's caption",
      "feedback": "...", "suggestions": ["..."]
    },
    "hashtag_relevance": {
      "score": 0-10, "max": 10, "label": "Hashtag Relevance",
      "reference_says": "Which hashtags the trending ref uses and why they work",
      "your_post_says": "Which hashtags the user uses",
      "feedback": "Missing tags / irrelevant tags",
      "suggestions": ["#tag1", "#tag2"]
    },
    "spelling_grammar": {
      "score": 0-10, "max": 10, "label": "Spelling & Grammar",
      "reference_says": "Caption/title polish in the trending ref",
      "your_post_says": "Grammar/spelling state of user's caption",
      "errors": ["Only CLEAR English typos: 'recieve' → 'receive'. Or missing critical punctuation. NOT Hinglish style choices."],
      "auto_fix": "Fully corrected version of the user's caption (preserves their tone and language mix — Hindi, English, or Hinglish)",
      "feedback": "STRICT LENIENCY RULES — only flag if confidently wrong:\n• Hinglish romanized spellings are ALL valid: 'krna', 'krne', 'krke', 'h', 'hai', 'kya', 'aap', 'apke', 'rutein', 'rutine', 'routine', 'fir', 'phir' — DO NOT flag any of these as errors.\n• Short forms valid: 'rn', 'btw', 'fr', 'idk', 'lol'.\n• Devanagari Hindi: always valid.\n• Multiple spelling variants of same Hindi-romanized word: accept all (e.g., 'rutein'/'routine'/'rutine' — accept).\n• ONLY flag CLEAR English typos with no ambiguity (e.g., 'beutiful' → 'beautiful', 'recieve' → 'receive').\n• If unsure, ACCEPT — don't flag. Empty errors array is fine.",
      "suggestions": ["..."]
    },
    "video_pacing": {
      "score": 0-15, "max": 15, "label": "Video Pacing",
      "reference_says": "Cuts/transitions/speed in trending ref",
      "your_post_says": "Cuts/transitions/speed in user's video",
      "feedback": "If image upload, note pacing isn't applicable and score from composition instead.",
      "suggestions": ["..."]
    },
    "audio_quality": {
      "score": 0-10, "max": 10, "label": "Audio Quality",
      "reference_says": "BGM, voice clarity, sync of trending ref",
      "your_post_says": "BGM, voice clarity of user's upload",
      "feedback": "If no audio (e.g., photo upload or muted), say so and score from caption strength instead.",
      "suggestions": ["..."]
    },
    "cta_strength": {
      "score": 0-10, "max": 10, "label": "Call to Action",
      "reference_says": "CTA in trending ref (follow/like/save/comment prompt)",
      "your_post_says": "CTA in user's caption (or lack of one)",
      "feedback": "...", "suggestions": ["..."]
    },
    "trend_alignment": {
      "score": 0-10, "max": 10, "label": "Trending Alignment",
      "reference_says": "Trend/format the reference is riding",
      "your_post_says": "Whether the user's post follows the same format",
      "feedback": "...", "suggestions": ["..."]
    }
  },
  "quick_wins": ["Top 3 specific fixes that would most improve the score"],
  "strengths":  ["What is already working well"],
  "recommendations": {
    "alternative_hooks": [
      { "text": "Hook idea 1", "type": "question|shocking|relatable|curiosity|challenge" },
      { "text": "Hook idea 2", "type": "..." },
      { "text": "Hook idea 3", "type": "..." }
    ],
    "caption_variants": [
      "Caption variant 1 — slightly different angle, same language style as user",
      "Caption variant 2 — alternate tone",
      "Caption variant 3 — punchy short version"
    ],
    "suggested_hashtags": {
      "trending": ["#tag1", "#tag2", "#tag3", "#tag4", "#tag5"],
      "niche":    ["#tag6", "#tag7", "#tag8", "#tag9", "#tag10"],
      "brand":    ["#tag11", "#tag12"]
    },
    "best_time_to_post": "Day + time window in IST, e.g. 'Wed/Thu 7–9 PM IST'. Base on niche conventions.",
    "new_reel_ideas": [
      { "title": "Reel idea 1 title", "angle": "unique angle", "script_outline": "Hook (3s) → Body (15s) → CTA (3s)", "estimated_views": "10K-50K" },
      { "title": "Reel idea 2 title", "angle": "...", "script_outline": "...", "estimated_views": "..." },
      { "title": "Reel idea 3 title", "angle": "...", "script_outline": "...", "estimated_views": "..." },
      { "title": "Reel idea 4 title", "angle": "...", "script_outline": "...", "estimated_views": "..." },
      { "title": "Reel idea 5 title", "angle": "...", "script_outline": "...", "estimated_views": "..." }
    ]
  },
  "verdict": "Approved if score >= 60 AND spelling_grammar.errors is empty, else Needs improvement"
}
TPL,
            ],

            // ───────────────────────── AI Studio ─────────────────────────
            'script.generate' => [
                'name'        => 'AI Studio — Video script generator',
                'group'       => 'AI Studio',
                'description' => 'Writes a 30/60-second Reel script (hook, body, CTA) in the brand voice.',
                'variables'   => [
                    ['name' => 'brandBlock', 'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'seconds',    'description' => '30 or 60.'],
                    ['name' => 'platform',   'description' => '"Instagram" or "YouTube".'],
                    ['name' => 'topic',      'description' => 'The video topic.'],
                    ['name' => 'specialty',  'description' => "The doctor's specialty."],
                    ['name' => 'words',      'description' => 'Spoken-word budget, e.g. "70-90".'],
                    ['name' => 'beats',      'description' => 'Number of body beats, e.g. "3-4".'],
                ],
                'template'    => <<<'TPL'
You are an award-winning video scriptwriter for medical / healthcare creators in India.
{{ brandBlock }}
Write a {{ seconds }}-second {{ platform }} video script for a doctor.
- Topic: "{{ topic }}"
- Doctor's specialty: "{{ specialty }}"
- For videos of 3 minutes or longer, treat each body beat as a chapter/segment that develops a distinct sub-point, so the full runtime is filled with substantive, well-paced content — never filler or repetition.

QUALITY MANDATE:
- Write in CLEAN STANDARD ENGLISH ONLY (no romanized Hindi like "kro", " rutein"). The doctor reads this on camera and may re-use the caption, so it must pass spell-check.
- Match the brand voice above when present; otherwise sound warm, credible and authoritative — never fear-mongering or making unverifiable medical claims.
- The script must realistically fit {{ seconds }} seconds: about {{ words }} spoken words across {{ beats }} body beats.
- The HOOK must stop the scroll in the first 3 seconds.
- The CTA must be a single, clear action (follow / book / comment / save).

Return ONLY this JSON (no markdown, no commentary):
{
  "title": "Short internal title for this script",
  "duration_seconds": {{ seconds }},
  "platform": "{{ platform }}",
  "hook": {
    "spoken": "The exact words the doctor says in the first 3 seconds",
    "on_screen_text": "Bold text overlay for the hook",
    "why_it_works": "1 sentence on the psychology of this hook"
  },
  "body": [
    { "beat": 1, "spoken": "What the doctor says", "on_screen_text": "Overlay text", "b_roll": "Suggested visual / b-roll" }
  ],
  "cta": {
    "spoken": "The closing call to action",
    "on_screen_text": "CTA overlay text"
  },
  "caption": "Ready-to-post caption with tasteful emojis, 2-3 lines",
  "hashtags": "#tag1 #tag2 #tag3 ... (12-15 mixed trending + niche + specialty tags)",
  "suggested_audio": "Type of trending audio / music that fits (describe, no copyrighted track names)",
  "shot_list": ["Quick bullet shot 1", "Quick bullet shot 2", "Quick bullet shot 3"]
}
The "body" array MUST contain {{ beats }} beats.
TPL,
            ],

            'captions.multi' => [
                'name'        => 'AI Studio — Multi-format caption engine',
                'group'       => 'AI Studio',
                'description' => 'Writes three distinct caption variants (long-form, short, question-hook) for one post.',
                'variables'   => [
                    ['name' => 'brandBlock', 'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'topic',      'description' => 'The post topic.'],
                    ['name' => 'postType',   'description' => 'Post format, e.g. "reel".'],
                ],
                'template'    => <<<'TPL'
You are a senior Instagram copywriter for Indian healthcare / lifestyle brands.
{{ brandBlock }}
Write THREE caption variants for the SAME post in ONE response.
- Post topic: "{{ topic }}"
- Post format: {{ postType }}

QUALITY MANDATE:
- CLEAN STANDARD ENGLISH ONLY (no romanized Hindi). These get copied and re-used, so they must pass spell-check.
- Match the brand voice above when present.
- Each variant serves a different job (see below). Make them genuinely distinct, not reworded copies of each other.

Return ONLY this JSON (no markdown):
{
  "topic": "{{ topic }}",
  "variants": {
    "long_form": {
      "label": "Long-form (Instagram feed)",
      "caption": "4-8 lines, storytelling or value-packed, tasteful emojis, strong CTA at the end",
      "best_for": "Feed posts where reach + saves matter"
    },
    "short_punchy": {
      "label": "Short punchy (Stories)",
      "caption": "1-2 punchy lines, high energy, made for a Story sticker / swipe-up vibe",
      "best_for": "Stories and quick scroll-stoppers"
    },
    "question_hook": {
      "label": "Question hook (max comments)",
      "caption": "Opens with an irresistible question that begs a reply, ends by explicitly asking people to comment",
      "best_for": "Driving comments + algorithm engagement"
    }
  },
  "hashtags": "#tag1 #tag2 ... (12-15 mixed trending + niche + brand hashtags shared by all variants)"
}
TPL,
            ],

            'reel.analyzer' => [
                'name'        => 'AI Studio — Reel analyzer',
                'group'       => 'AI Studio',
                'description' => 'Reverse-engineers a reference reel and (optionally) scores the user\'s own video against it.',
                'variables'   => [
                    ['name' => 'brandBlock',   'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'reelUrl',      'description' => 'The reference reel URL.'],
                    ['name' => 'notesClause',  'description' => 'Optional user context line (may be empty).'],
                    ['name' => 'videoClause',  'description' => 'Whether the user attached their own video (built in code).'],
                ],
                'template'    => <<<'TPL'
You are an elite short-form video strategist who reverse-engineers viral Instagram Reels.
{{ brandBlock }}
A user wants to learn from this reference reel: {{ reelUrl }}
{{ notesClause }}

Infer the reel's strategy from the URL, handle/slug, any context provided, and your knowledge of what performs in this niche. Be concrete and specific — never generic.

{{ videoClause }}

QUALITY MANDATE:
- Write ALL generated captions / hooks / hashtags in CLEAN STANDARD ENGLISH ONLY (no romanized Hindi) — they get copied and re-used.
- Optimized captions/hashtags must match the brand voice above when present.

Return ONLY this JSON (no markdown):
{
  "reference": {
    "topic": "What the reel is about",
    "target_audience": "Who it is made for (age, interests, intent)",
    "hook": "The likely opening hook + why it works",
    "caption_style": "Tone, length and structure of its caption",
    "hashtag_strategy": "How it likely uses hashtags (mix of broad/niche/branded)",
    "format": "Format/edit style (talking head, b-roll, text-on-screen, trend, etc.)",
    "why_it_works": "2-3 sentence breakdown of the winning formula"
  },
  "your_video": {
    "observed_topic": "What the user's attached video is about",
    "observed_hook": "How the user's video opens",
    "strengths": ["..."],
    "gaps_vs_reference": ["Specific ways it differs from the winning reel"]
  },
  "match": {
    "score": 0,
    "verdict": "1-line verdict on audience fit",
    "breakdown": [
      { "label": "Hook alignment", "score": 0, "max": 25, "note": "..." },
      { "label": "Topic / audience fit", "score": 0, "max": 25, "note": "..." },
      { "label": "Format & pacing", "score": 0, "max": 25, "note": "..." },
      { "label": "Caption & hashtag fit", "score": 0, "max": 25, "note": "..." }
    ]
  },
  "optimized": {
    "captions": ["Optimized caption variant 1", "Optimized caption variant 2", "Optimized caption variant 3"],
    "hashtags": {
      "trending": ["#tag1", "#tag2", "#tag3", "#tag4", "#tag5"],
      "niche":    ["#tag6", "#tag7", "#tag8", "#tag9", "#tag10"],
      "branded":  ["#tag11", "#tag12"]
    },
    "hooks": ["Stronger hook idea 1", "Stronger hook idea 2", "Stronger hook idea 3"],
    "action_items": ["Concrete change 1 to better match the audience", "Concrete change 2", "Concrete change 3"]
  }
}
The "match.score" MUST equal the sum of the four breakdown scores (max 100). If no user video was attached, "your_video" and "match" MUST be null.
TPL,
            ],

            'profile.auditor' => [
                'name'        => 'AI Studio — Competitor profile auditor',
                'group'       => 'AI Studio',
                'description' => 'Audits a competitor Instagram profile and turns it into a "steal their playbook" brief.',
                'variables'   => [
                    ['name' => 'brandBlock', 'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'handle',     'description' => 'The Instagram handle (no @).'],
                    ['name' => 'nicheLine',  'description' => 'Optional niche/industry context line (may be empty).'],
                    ['name' => 'clientName', 'description' => 'The client the playbook is for.'],
                ],
                'template'    => <<<'TPL'
You are a competitive-intelligence analyst for social media agencies.
{{ brandBlock }}
Audit the Instagram profile @{{ handle }}. {{ nicheLine }}

Based on the handle, niche and your knowledge of how successful accounts in this space operate, infer the patterns across their recent ~12 posts. Be specific and actionable — never vague. If you are uncertain about an exact number, give a realistic, clearly-labelled estimate rather than refusing.

Then translate the findings into a "steal their playbook" brief for {{ clientName }}.

QUALITY MANDATE:
- CLEAN STANDARD ENGLISH ONLY in every generated caption/hook/hashtag (they get copied and re-used).
- The playbook must respect the brand voice above when present — adapt, do not copy their exact words.

Return ONLY this JSON (no markdown):
{
  "handle": "{{ handle }}",
  "snapshot": {
    "estimated_niche": "...",
    "posting_frequency": "e.g. ~4-5 posts/week, mostly Reels",
    "content_mix": [
      { "format": "Reels", "share": "~60%", "note": "..." },
      { "format": "Carousels", "share": "~25%", "note": "..." },
      { "format": "Single image", "share": "~15%", "note": "..." }
    ],
    "winning_format": "Which format clearly performs best and why"
  },
  "caption_style": {
    "tone": "...",
    "length": "...",
    "structure": "Hook → value → CTA pattern they tend to use",
    "examples": ["Short paraphrased example of their caption style 1", "Example 2"]
  },
  "hashtag_strategy": {
    "approach": "How many + what mix they use",
    "common_tags": ["#tag1", "#tag2", "#tag3", "#tag4", "#tag5"]
  },
  "what_works": ["Specific thing that drives their engagement 1", "2", "3"],
  "gaps_to_exploit": ["Where they are weak / what they ignore that {{ clientName }} can own 1", "2"],
  "playbook": {
    "summary": "2-3 sentence strategy to beat them",
    "post_ideas": [
      { "title": "Post idea 1 for {{ clientName }}", "format": "Reel", "hook": "...", "why": "..." },
      { "title": "Post idea 2", "format": "Carousel", "hook": "...", "why": "..." },
      { "title": "Post idea 3", "format": "Reel", "hook": "...", "why": "..." }
    ],
    "recommended_cadence": "Suggested weekly posting plan for {{ clientName }}",
    "recommended_hashtags": "#tag1 #tag2 #tag3 ... (12-15 tags {{ clientName }} should use)"
  }
}
TPL,
            ],

            'competitor.brief' => [
                'name'        => 'AI Studio — Competitor intelligence brief',
                'group'       => 'AI Studio',
                'description' => 'Weekly competitor feed: infers top competitor posts and recommends actions for the client.',
                'variables'   => [
                    ['name' => 'brandBlock', 'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'clientName', 'description' => 'The client the brief is for.'],
                    ['name' => 'nicheLine',  'description' => 'Optional niche/industry line (may be empty).'],
                    ['name' => 'handleList', 'description' => 'Comma-separated list of @handles being tracked.'],
                ],
                'template'    => <<<'TPL'
You are a social media competitive-intelligence analyst.
{{ brandBlock }}
Produce this week's competitor intelligence brief for {{ clientName }}. {{ nicheLine }}
Competitor Instagram pages being tracked: {{ handleList }}.

For each competitor, infer their strongest-performing posts from THIS WEEK based on the handle, niche and how accounts like these typically perform. Identify the format, topic and likely engagement driver. Then synthesise a single "what's working for competitors this week" brief that {{ clientName }} can act on immediately.

Be specific and actionable. Where you estimate engagement, label it as an estimate. Never refuse for lack of live data — infer realistically.

QUALITY MANDATE:
- CLEAN STANDARD ENGLISH ONLY in every generated idea/caption/hashtag (they get copied and re-used).
- Recommendations must respect the brand voice above when present.

Return ONLY this JSON (no markdown):
{
  "week_summary": "2-3 sentence headline of the biggest competitor trend this week",
  "competitors": [
    {
      "handle": "competitor_handle",
      "top_posts": [
        { "topic": "...", "format": "Reel|Carousel|Image|Story", "why_it_worked": "...", "est_engagement": "e.g. ~12K likes / high (estimate)" }
      ],
      "takeaway": "1-line lesson from this competitor this week"
    }
  ],
  "whats_working": ["Cross-competitor pattern 1", "Pattern 2", "Pattern 3"],
  "content_gaps": ["Topic/angle none of them are covering that {{ clientName }} can own 1", "2"],
  "recommended_posts": [
    { "title": "Post idea for {{ clientName }} 1", "format": "Reel", "hook": "...", "rationale": "Based on which competitor trend" },
    { "title": "Post idea 2", "format": "Carousel", "hook": "...", "rationale": "..." },
    { "title": "Post idea 3", "format": "Reel", "hook": "...", "rationale": "..." }
  ],
  "recommended_hashtags": "#tag1 #tag2 #tag3 ... (10-15 tags trending in this niche this week)"
}
The "competitors" array MUST contain one entry per tracked handle above.
TPL,
            ],

            // ──────────────────── Content Tools / Growth ────────────────────
            'captions.weekly' => [
                'name'        => 'Content Tools — Weekly caption draft',
                'group'       => 'Content Tools',
                'description' => 'Generates one ready-to-post caption + hashtags for a scheduled content slot.',
                'variables'   => [
                    ['name' => 'brand',     'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'platform',  'description' => 'Target platform for the slot.'],
                    ['name' => 'theme',     'description' => 'Theme of the post.'],
                    ['name' => 'date',      'description' => 'Scheduled date, e.g. "Monday, 5 May 2026".'],
                    ['name' => 'industry',  'description' => "The client's industry."],
                    ['name' => 'post_type', 'description' => 'The post format.'],
                ],
                'template'    => <<<'TPL'
You are a senior social media copywriter for an Indian healthcare brand.
{{ brand }}
Write ONE ready-to-post {{ platform }} caption for a "{{ theme }}" themed post scheduled for {{ date }}.
Industry: {{ industry }}. Post format: {{ post_type }}.

Rules:
- Clean STANDARD ENGLISH only (no romanized Hindi). Professional, scroll-stopping.
- 2-4 short lines, tasteful emojis, end with a clear CTA.
- Provide 12-15 relevant, mixed (trending + niche + brand) hashtags.

Return ONLY this JSON (no markdown):
{ "caption": "the full caption with line breaks and emojis", "hashtags": "#tag1 #tag2 #tag3 ..." }
TPL,
            ],

            'command.insight' => [
                'name'        => 'Growth — Command Center insight',
                'group'       => 'Growth Intelligence',
                'description' => 'One-sentence "what is working + highest-leverage move" insight over the top posts.',
                'variables'   => [
                    ['name' => 'clientName', 'description' => 'The client name.'],
                    ['name' => 'sort',       'description' => 'Metric the posts are ranked by.'],
                    ['name' => 'brand',      'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'list',       'description' => 'Numbered list of the top posts with metrics.'],
                ],
                'template'    => <<<'TPL'
You are a content strategist reviewing the top posts for "{{ clientName }}", ranked by {{ sort }}.
{{ brand }}
Top posts:
{{ list }}

In ONE punchy sentence (max ~22 words), tell the team what's working and the single highest-leverage move to double down on it. Plain English, specific, no fluff.
Return ONLY JSON: { "insight": "..." }
TPL,
            ],

            'scorecard.comments' => [
                'name'        => 'Growth — Scorecard KPI comments',
                'group'       => 'Growth Intelligence',
                'description' => 'Writes one short plain-English insight per KPI for the monthly Content Health Scorecard.',
                'variables'   => [
                    ['name' => 'clientName',  'description' => 'The client name.'],
                    ['name' => 'industry',    'description' => "The client's industry."],
                    ['name' => 'monthLabel',  'description' => 'Report month, e.g. "May 2026".'],
                    ['name' => 'brand',       'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'kpiCount',    'description' => 'Number of KPIs.'],
                    ['name' => 'metricLines', 'description' => 'Numbered KPI lines with this/last month numbers.'],
                    ['name' => 'exampleJson', 'description' => 'Example JSON keys for the response shape (built in code).'],
                ],
                'template'    => <<<'TPL'
You are a social-media strategist writing a monthly Content Health Scorecard for "{{ clientName }}" ({{ industry }}) for {{ monthLabel }}.
{{ brand }}
Here are {{ kpiCount }} KPIs with this-month-vs-last-month numbers:
{{ metricLines }}

For EACH KPI write ONE short, specific, plain-English insight sentence (max ~14 words) that explains what the movement means and, where useful, a concrete lever to pull. Examples of the style:
- "Saves up 34% — educational content is resonating, keep it up."
- "Shares down 12% — add stronger CTAs to prompt resharing."
When a KPI has "no data", say it's not tracked yet and what connecting unlocks.

Return ONLY this JSON (no markdown):
{ "comments": { {{ exampleJson }} } }
TPL,
            ],

            'viral.predictor' => [
                'name'        => 'Growth — Viral probability predictor',
                'group'       => 'Growth Intelligence',
                'description' => 'Scores a planned post against the client\'s "viral DNA" and predicts reach.',
                'variables'   => [
                    ['name' => 'clientName',  'description' => 'The client name.'],
                    ['name' => 'industry',    'description' => "The client's industry."],
                    ['name' => 'brand',       'description' => 'Client brand-voice block (may be empty).'],
                    ['name' => 'dna',         'description' => 'Top-performing content lines (the "viral DNA").'],
                    ['name' => 'reachCtx',    'description' => 'Reach context block.'],
                    ['name' => 'fmtLabel',    'description' => 'Human label for the planned format.'],
                    ['name' => 'topic',       'description' => 'Planned post topic.'],
                    ['name' => 'hook',        'description' => 'Planned post hook.'],
                    ['name' => 'caption',     'description' => 'Planned post caption.'],
                    ['name' => 'scriptBlock', 'description' => 'Optional full-script block (may be empty).'],
                ],
                'template'    => <<<'TPL'
You are a viral-content predictor for "{{ clientName }}" ({{ industry }}).
{{ brand }}
THIS CLIENT'S TOP-PERFORMING CONTENT (their "viral DNA", best first):
{{ dna }}

REACH CONTEXT:
{{ reachCtx }}

THE PLANNED POST TO EVALUATE:
- Format: {{ fmtLabel }}
- Topic: {{ topic }}
- Hook: {{ hook }}
- Caption: {{ caption }}{{ scriptBlock }}

Compare the planned post against this client's viral DNA. Judge hook strength, topic resonance vs what worked before, format fit, and caption/CTA quality. Be honest and specific — most posts are NOT viral.

Return ONLY this JSON (no markdown):
{
  "match_score": 0,
  "verdict": "go" | "improve" | "no_go",
  "verdict_line": "One-line plain-English call, e.g. '78% match to your viral DNA — strong go.'",
  "predicted_reach": { "low": 0, "high": 0, "basis": "1 short sentence on how this range was derived" },
  "breakdown": [
    { "label": "Hook strength", "score": 0, "max": 25, "note": "..." },
    { "label": "Topic resonance", "score": 0, "max": 25, "note": "..." },
    { "label": "Format fit", "score": 0, "max": 25, "note": "..." },
    { "label": "Caption & CTA", "score": 0, "max": 25, "note": "..." }
  ],
  "tweaks": ["Specific change to raise the score 1", "...2", "...3"],
  "rewritten_hook": "A stronger hook the client could use instead"
}
RULES: match_score MUST equal the sum of the 4 breakdown scores (0-100). verdict: "go" if >=70, "improve" if 45-69, "no_go" if <45. predicted_reach.high >= predicted_reach.low.
TPL,
            ],

        ];
    }
}
