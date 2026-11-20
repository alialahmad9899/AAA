<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FDFBF7">
    <meta name="description" content="@yield('document_description')">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>@yield('document_title') | وصال</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --legal-teal: #0F4C5C;
            --legal-teal-deep: #103E4A;
            --legal-coral: #D97960;
            --legal-ink: #1D2D44;
            --legal-muted: #596B79;
            --legal-paper: #FDFBF7;
            --legal-border: #E8E5DE;
            --legal-soft: #F3F8F7;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { min-width: 320px; scroll-behavior: smooth; background: var(--legal-paper); }
        body {
            min-width: 320px; margin: 0; color: var(--legal-ink); background: var(--legal-paper);
            font-family: 'Cairo', Tahoma, Arial, sans-serif; -webkit-text-size-adjust: 100%;
            text-rendering: optimizeLegibility;
        }
        .legal-page {
            min-height: 100vh; min-height: 100svh;
            padding: calc(22px + env(safe-area-inset-top)) 16px calc(38px + env(safe-area-inset-bottom));
            background:
                radial-gradient(ellipse at 94% 0%, rgba(217,121,96,.12), transparent 34%),
                radial-gradient(ellipse at 0% 24%, rgba(15,76,92,.075), transparent 31%),
                var(--legal-paper);
        }
        .legal-shell { width: 100%; max-width: 920px; margin: 0 auto; }
        .legal-brand { display: flex; justify-content: center; align-items: center; margin: 0 auto 18px; }
        .legal-brand img { display: block; width: auto; max-width: min(190px, 55vw); max-height: 84px; object-fit: contain; }
        .legal-hero {
            position: relative; overflow: hidden; padding: 28px 30px 26px;
            border: 1px solid rgba(15,76,92,.12); border-radius: 24px;
            background: rgba(255,255,255,.94); box-shadow: 0 12px 34px rgba(29,45,68,.065);
        }
        .legal-hero::after {
            content: ""; position: absolute; inset-inline-end: 0; bottom: 0;
            width: 86px; height: 4px; border-radius: 4px 0 0 0; background: var(--legal-coral);
        }
        .legal-kicker {
            display: inline-flex; align-items: center; gap: 9px; margin: 0 0 10px;
            color: var(--legal-teal); font-size: 11px; font-weight: 800; letter-spacing: .15px;
        }
        .legal-kicker::before {
            content: ""; width: 8px; height: 8px; border-radius: 50%;
            background: var(--legal-coral); box-shadow: 0 0 0 4px rgba(217,121,96,.13);
        }
        .legal-hero h1 {
            margin: 0; color: var(--legal-teal); font-size: clamp(25px, 4vw, 35px);
            line-height: 1.55; font-weight: 900; letter-spacing: -.3px;
        }
        .legal-hero p { max-width: 760px; margin: 9px 0 0; color: var(--legal-muted); font-size: 14px; line-height: 2; font-weight: 500; }
        .legal-document {
            margin-top: 16px; padding: 30px 34px; border: 1px solid var(--legal-border);
            border-radius: 22px; background: #fff; box-shadow: 0 9px 28px rgba(29,45,68,.045);
            color: var(--legal-ink); font-size: 15px; line-height: 2.08; overflow-wrap: anywhere;
        }
        .legal-document > :first-child { margin-top: 0 !important; }
        .legal-document > :last-child { margin-bottom: 0 !important; }
        .legal-document .document-label {
            display: inline-flex; margin: 0 0 12px; padding: 5px 12px;
            border-radius: 999px; color: var(--legal-teal); background: var(--legal-soft);
            font-size: 11px; font-weight: 800;
        }
        .legal-document .document-lead {
            margin: 0 0 22px; padding: 16px 18px; border-inline-start: 3px solid var(--legal-coral);
            border-radius: 0 13px 13px 0; color: #354B5C; background: #FFF8F4; font-weight: 600;
        }
        .legal-section { margin: 30px 0 0; padding-top: 25px; border-top: 1px solid #EEF0ED; }
        .legal-section:first-of-type { margin-top: 0; padding-top: 0; border-top: 0; }
        .legal-section h2 {
            display: flex; align-items: flex-start; gap: 10px; margin: 0 0 13px;
            padding: 9px 14px; border-inline-start: 3px solid var(--legal-coral);
            border-radius: 0 12px 12px 0; color: var(--legal-teal); background: #F4F8F7;
            font-size: 18px; line-height: 1.8; font-weight: 900; scroll-margin-top: 18px;
        }
        .legal-section p { margin: 0 0 12px; color: #405263; font-weight: 500; }
        .legal-section p:last-child { margin-bottom: 0; }
        .legal-section strong, .legal-section b { color: var(--legal-ink); font-weight: 800; }
        .legal-section ul, .legal-section ol { margin: 8px 0 15px; padding-inline-start: 26px; color: #405263; }
        .legal-section li { margin: 0 0 7px; padding-inline-start: 3px; }
        .legal-section li::marker { color: var(--legal-coral); font-weight: 900; }
        .legal-section a { color: #14596A; font-weight: 800; text-decoration: underline; text-decoration-color: rgba(20,89,106,.35); text-underline-offset: 3px; overflow-wrap: anywhere; }
        .legal-section a:hover { color: #A94E38; }
        .legal-table-wrap { width: 100%; margin: 14px 0 18px; }
        .legal-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; color: #405263; font-size: 13px; line-height: 1.9; }
        .legal-table th, .legal-table td { padding: 11px 12px; border-inline-start: 1px solid #E4EAE7; border-bottom: 1px solid #E4EAE7; text-align: start; vertical-align: top; overflow-wrap: anywhere; }
        .legal-table th { color: var(--legal-teal); background: #EDF5F3; font-weight: 900; }
        .legal-table th:first-child, .legal-table td:first-child { border-inline-end: 1px solid #E4EAE7; }
        .legal-table thead th { border-top: 1px solid #E4EAE7; }
        .legal-table thead th:first-child { border-top-right-radius: 10px; }
        .legal-table thead th:last-child { border-top-left-radius: 10px; }
        .legal-table tbody tr:nth-child(even) td { background: #FBFCFB; }
        .legal-table tbody tr:last-child td:first-child { border-bottom-right-radius: 10px; }
        .legal-table tbody tr:last-child td:last-child { border-bottom-left-radius: 10px; }
        .legal-closing { margin-top: 26px; padding-top: 18px; border-top: 1px solid #EEF0ED; color: var(--legal-muted); text-align: center; font-size: 12px; font-weight: 700; }
        @media (max-width: 680px) {
            .legal-page { padding: calc(15px + env(safe-area-inset-top)) 10px calc(24px + env(safe-area-inset-bottom)); }
            .legal-brand { margin-bottom: 13px; }
            .legal-brand img { max-height: 68px; max-width: min(165px, 58vw); }
            .legal-hero { padding: 21px 18px 20px; border-radius: 18px; }
            .legal-hero h1 { font-size: 24px; line-height: 1.55; }
            .legal-hero p { font-size: 12.5px; line-height: 1.95; }
            .legal-document { margin-top: 11px; padding: 20px 15px; border-radius: 17px; font-size: 14px; line-height: 2.02; }
            .legal-document .document-lead { padding: 13px 13px; }
            .legal-section { margin-top: 23px; padding-top: 19px; }
            .legal-section h2 { margin-bottom: 10px; padding: 7px 10px; font-size: 16px; }
            .legal-section p { margin-bottom: 10px; }
            .legal-section ul, .legal-section ol { padding-inline-start: 22px; }
            .legal-table-wrap { margin: 12px 0 16px; }
            .legal-table, .legal-table tbody, .legal-table tr, .legal-table td { display: block; width: 100%; }
            .legal-table { font-size: 12px; }
            .legal-table thead { display: none; }
            .legal-table tbody tr {
                margin: 0 0 11px; overflow: hidden; border: 1px solid #E4EAE7;
                border-radius: 12px; background: #fff; box-shadow: 0 3px 10px rgba(29,45,68,.035);
            }
            .legal-table tbody tr:nth-child(even) td { background: #fff; }
            .legal-table td {
                display: grid; grid-template-columns: minmax(84px, 31%) minmax(0, 1fr);
                gap: 9px; padding: 9px 10px; border: 0; border-bottom: 1px solid #EEF1EF;
                text-align: start;
            }
            .legal-table td:first-child { border-inline: 0; }
            .legal-table td:last-child { border-bottom: 0; }
            .legal-table td::before {
                content: attr(data-label); color: var(--legal-teal); font-weight: 900;
                font-size: 11px; line-height: 1.8;
            }
        }
        @media (max-width: 360px) {
            .legal-document { padding-inline: 12px; font-size: 13px; }
            .legal-table td { grid-template-columns: minmax(76px, 30%) minmax(0, 1fr); gap: 6px; padding-inline: 8px; }
            .legal-table td::before { font-size: 10px; }
        }
        @media print {
            body, .legal-page { background: #fff !important; }
            .legal-page { padding: 0; }
            .legal-brand img { max-height: 55px; }
            .legal-hero, .legal-document { box-shadow: none; }
            .legal-document { border: 0; padding: 0; }
            .legal-section { break-inside: avoid; }
            .legal-table tbody tr { break-inside: avoid; }
        }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>
</head>
<body>
    <main class="legal-page">
        <div class="legal-shell">
            <div class="legal-brand">
                <img src="{{ static_asset('assets/img/wesal-legal-logo.png') }}" alt="وصال Wesal">
            </div>
            <header class="legal-hero">
                <div class="legal-kicker">وصال · الوثائق الرسمية</div>
                <h1>@yield('document_title')</h1>
                <p>@yield('document_description')</p>
            </header>
            <article class="legal-document">
                @yield('content')
            </article>
        </div>
    </main>
</body>
</html>
