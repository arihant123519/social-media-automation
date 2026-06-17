<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — {{ config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.65; color: #1f2937; max-width: 820px; margin: 0 auto; padding: 40px 20px; background: #f9fafb; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 36px 40px; }
        h1 { font-size: 26px; margin: 0 0 4px; color: #111827; }
        .updated { color: #6b7280; font-size: 13px; margin-bottom: 28px; }
        h2 { font-size: 17px; margin: 28px 0 8px; color: #111827; }
        p, li { font-size: 14.5px; color: #374151; }
        ul { padding-left: 20px; }
        a { color: #2563eb; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
<div class="card">
    <h1>Terms of Service</h1>
    <div class="updated">{{ config('app.name') }} · Last updated: {{ now()->format('d M Y') }}</div>

    <p>These Terms govern your use of {{ config('app.name') }} ("the Service"). By using the Service you agree to these Terms.</p>

    <h2>1. The Service</h2>
    <p>{{ config('app.name') }} lets teams plan, review, schedule and publish social media content on behalf of their clients to platforms such as Instagram and YouTube, via those platforms' official APIs.</p>

    <h2>2. Your Responsibilities</h2>
    <ul>
        <li>You must have the right and authorization to publish content to any connected social account.</li>
        <li>You are responsible for the content you upload and publish, and for complying with the terms and policies of Instagram, YouTube and other platforms.</li>
        <li>You must not use the Service for spam, misleading content, or any unlawful purpose.</li>
        <li>You are responsible for keeping your account credentials secure.</li>
    </ul>

    <h2>3. Connected Accounts</h2>
    <p>When you connect a social account, you authorize the Service to publish the content you approve on your behalf. You may disconnect any account at any time.</p>

    <h2>4. Third-Party Platforms</h2>
    <p>The Service relies on third-party APIs (Meta, Google). We are not responsible for changes, downtime, or policy decisions made by those platforms. Your use of those platforms is also governed by their respective terms.</p>

    <h2>5. Availability</h2>
    <p>We aim to keep the Service available but do not guarantee uninterrupted operation. Features may change over time.</p>

    <h2>6. Limitation of Liability</h2>
    <p>The Service is provided "as is". To the maximum extent permitted by law, we are not liable for any indirect or consequential damages arising from your use of the Service.</p>

    <h2>7. Termination</h2>
    <p>We may suspend or terminate access for violation of these Terms. You may stop using the Service at any time.</p>

    <h2>8. Changes</h2>
    <p>We may update these Terms; continued use after changes constitutes acceptance.</p>

    <h2>9. Contact</h2>
    <p>Questions about these Terms? Email <a href="mailto:ragani.ichelon@gmail.com">ragani.ichelon@gmail.com</a>.</p>

    <div class="footer">© {{ now()->year }} {{ config('app.name') }}. All rights reserved. · <a href="{{ route('privacy') }}">Privacy Policy</a></div>
</div>
</body>
</html>
