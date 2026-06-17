<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — {{ config('app.name') }}</title>
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
    <h1>Privacy Policy</h1>
    <div class="updated">{{ config('app.name') }} · Last updated: {{ now()->format('d M Y') }}</div>

    <p>{{ config('app.name') }} ("we", "our", "the Service") helps marketing teams plan, review, schedule and publish social media content on behalf of their clients to platforms such as Instagram and YouTube. This Privacy Policy explains what information we handle and how.</p>

    <h2>1. Information We Collect</h2>
    <ul>
        <li><strong>Account &amp; client data:</strong> client names, contact details, and the content plan you configure.</li>
        <li><strong>Social account connections:</strong> when you connect a client's Instagram or YouTube account, we receive and securely store access tokens issued by Meta or Google so we can publish content you approve.</li>
        <li><strong>Content you upload:</strong> videos, images, captions and hashtags you submit for review and publishing.</li>
        <li><strong>Usage data:</strong> basic logs needed to operate and troubleshoot the Service.</li>
    </ul>

    <h2>2. How We Use Information</h2>
    <ul>
        <li>To publish or schedule the content you explicitly approve to the connected social accounts.</li>
        <li>To analyse content quality and provide improvement suggestions.</li>
        <li>To send operational emails (publish confirmations and schedule reminders).</li>
        <li>To operate, secure and improve the Service.</li>
    </ul>

    <h2>3. Platform Data (Meta &amp; Google)</h2>
    <p>We access Instagram and YouTube data only through their official APIs and only with your authorization. We use this access solely to publish content and read the connected account's basic information. We do not sell platform data, and we use it strictly in accordance with the Meta Platform Terms and Google API Services User Data Policy.</p>

    <h2>4. Data Storage &amp; Security</h2>
    <p>Access tokens are stored encrypted. We retain data only as long as needed to provide the Service. Reasonable technical and organizational measures are used to protect your information.</p>

    <h2>5. Data Deletion</h2>
    <p>You can disconnect any social account at any time, which removes its stored tokens. To request deletion of your data, contact us at the address below and we will remove it within 30 days.</p>

    <h2>6. Sharing</h2>
    <p>We do not sell your data. We share data only with the social platforms (Meta, Google) as required to fulfil your publishing requests, and with service providers strictly necessary to operate the Service.</p>

    <h2>7. Changes</h2>
    <p>We may update this policy from time to time. The "Last updated" date above reflects the latest revision.</p>

    <h2>8. Contact</h2>
    <p>For privacy questions or data deletion requests, email <a href="mailto:ragani.ichelon@gmail.com">ragani.ichelon@gmail.com</a>.</p>

    <div class="footer">© {{ now()->year }} {{ config('app.name') }}. All rights reserved. · <a href="{{ route('terms') }}">Terms of Service</a></div>
</div>
</body>
</html>
