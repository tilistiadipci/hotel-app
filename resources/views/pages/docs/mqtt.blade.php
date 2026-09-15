<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dokumentasi MQTT - {{ config('app.name') }}</title>
    <style>
        :root {
            --background: #f7f9fc;
            --surface: #fff;
            --border: #e3e8f0;
            --muted: #6c7890;
            --text: #263044;
            --primary: #3d68e8;
            --primary-soft: #e8f2ff;
            --code: #111a2e;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--background);
            color: var(--text);
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }
        .docs-header {
            align-items: center;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--border);
            display: flex;
            height: 64px;
            justify-content: space-between;
            left: 0;
            padding: 0 32px;
            position: fixed;
            right: 0;
            top: 0;
            z-index: 20;
        }
        .brand {
            align-items: center;
            color: var(--text);
            display: flex;
            font-size: 16px;
            font-weight: 700;
            gap: 11px;
            text-decoration: none;
        }
        .brand-icon {
            align-items: center;
            background: var(--primary);
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }
        .back-button {
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 7px;
            color: #45516a;
            display: inline-flex;
            font-weight: 600;
            gap: 8px;
            padding: 8px 13px;
            text-decoration: none;
        }
        .back-button:hover {
            background: var(--primary-soft);
            border-color: #cbdafa;
            color: var(--primary);
        }
        .docs-layout {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr) 210px;
            margin: 64px auto 0;
            max-width: 1480px;
            min-height: calc(100vh - 64px);
        }
        .docs-sidebar {
            border-right: 1px solid var(--border);
            height: calc(100vh - 64px);
            overflow-y: auto;
            padding: 32px 22px;
            position: sticky;
            top: 64px;
        }
        .nav-caption, .toc-caption {
            color: #9aa7bc;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .07em;
            margin: 0 0 9px;
            text-transform: uppercase;
        }
        .docs-nav a {
            border-radius: 6px;
            color: #59657a;
            display: block;
            font-size: 13px;
            margin-bottom: 3px;
            padding: 7px 10px;
            text-decoration: none;
        }
        .docs-nav a:hover, .docs-nav a.active {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
        }
        .docs-main {
            min-width: 0;
            padding: 42px 54px 90px;
        }
        .markdown-body { max-width: 920px; }
        .markdown-body h1, .markdown-body h2, .markdown-body h3 {
            color: #131d31;
            line-height: 1.35;
            scroll-margin-top: 88px;
        }
        .markdown-body h1 {
            font-size: 28px;
            margin: 0 0 25px;
        }
        .markdown-body h2 {
            border-top: 1px solid var(--border);
            font-size: 21px;
            margin: 46px 0 14px;
            padding-top: 40px;
        }
        .markdown-body h1 + h2 {
            border-top: 0;
            margin-top: 24px;
            padding-top: 0;
        }
        .markdown-body h3 {
            font-size: 16px;
            margin: 28px 0 10px;
        }
        .markdown-body p { margin: 0 0 15px; }
        .markdown-body ul, .markdown-body ol {
            margin: 0 0 18px;
            padding-left: 22px;
        }
        .markdown-body pre {
            background: var(--code);
            border-radius: 10px;
            color: #f5f7fb;
            margin: 13px 0 21px;
            overflow-x: auto;
            padding: 16px 18px;
        }
        .markdown-body code {
            background: #edf1f7;
            border-radius: 4px;
            color: #24447c;
            font-family: "Cascadia Code", Consolas, monospace;
            font-size: .9em;
            overflow-wrap: anywhere;
            padding: 2px 5px;
        }
        .markdown-body pre code {
            background: transparent;
            color: inherit;
            overflow-wrap: normal;
            padding: 0;
        }
        .markdown-body table {
            border-collapse: collapse;
            display: block;
            margin: 18px 0 25px;
            overflow-x: auto;
            width: 100%;
        }
        .markdown-body th, .markdown-body td {
            border: 1px solid var(--border);
            min-width: 120px;
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }
        .markdown-body th { background: #f0f4f9; }
        .docs-toc {
            height: calc(100vh - 64px);
            padding: 42px 20px;
            position: sticky;
            top: 64px;
        }
        .toc-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
        }
        .toc-links a {
            color: #63708a;
            display: block;
            font-size: 12px;
            line-height: 1.45;
            margin: 8px 0;
            text-decoration: none;
        }
        .toc-links a.level-3 { padding-left: 10px; }
        .toc-links a:hover { color: var(--primary); }
        @media (max-width: 1050px) {
            .docs-layout { grid-template-columns: 220px minmax(0, 1fr); }
            .docs-toc { display: none; }
            .docs-main { padding: 36px 40px 80px; }
        }
        @media (max-width: 720px) {
            .docs-header { height: 58px; padding: 0 16px; }
            .brand-label { display: none; }
            .docs-layout { display: block; margin-top: 58px; }
            .docs-sidebar {
                background: var(--surface);
                border-bottom: 1px solid var(--border);
                border-right: 0;
                height: auto;
                padding: 14px 16px;
                position: static;
            }
            .nav-caption { display: none; }
            .docs-nav { display: flex; gap: 5px; overflow-x: auto; }
            .docs-nav a { flex: 0 0 auto; margin: 0; }
            .docs-main { padding: 28px 18px 60px; }
            .markdown-body h1 { font-size: 24px; }
            .markdown-body h2 { font-size: 19px; }
        }
    </style>
</head>
<body>
    <header class="docs-header">
        <a class="brand" href="{{ route('docs.mqtt') }}">
            <span class="brand-icon" aria-hidden="true">MQ</span>
            <span class="brand-label">Dokumentasi MQTT</span>
        </a>
        <a class="back-button" href="{{ route('platform.dashboard') }}">
            <span aria-hidden="true">&larr;</span> Kembali ke Dashboard
        </a>
    </header>

    <div class="docs-layout">
        <aside class="docs-sidebar" aria-label="Navigasi dokumentasi">
            <p class="nav-caption">Topic MQTT</p>
            <nav id="docs-nav" class="docs-nav"></nav>
        </aside>

        <main class="docs-main">
            <article id="markdown-content" class="markdown-body">
                {!! $content !!}
            </article>
        </main>

        <aside class="docs-toc" aria-label="Daftar isi halaman">
            <div class="toc-box">
                <p class="toc-caption">Pada halaman ini</p>
                <nav id="toc-links" class="toc-links"></nav>
            </div>
        </aside>
    </div>

    <script>
        (() => {
            const content = document.getElementById('markdown-content');
            const mainNav = document.getElementById('docs-nav');
            const toc = document.getElementById('toc-links');
            const headings = [...content.querySelectorAll('h2, h3')];
            const usedIds = new Set();

            const slugify = (text) => {
                const base = text.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '') || 'bagian';
                let id = base;
                let index = 2;

                while (usedIds.has(id)) id = `${base}-${index++}`;
                usedIds.add(id);
                return id;
            };

            headings.forEach((heading) => {
                heading.id = slugify(heading.textContent.trim());

                const tocLink = document.createElement('a');
                tocLink.href = `#${heading.id}`;
                tocLink.textContent = heading.textContent.trim();
                tocLink.className = heading.tagName === 'H3' ? 'level-3' : 'level-2';
                toc.appendChild(tocLink);

                if (heading.tagName === 'H2') {
                    const navLink = document.createElement('a');
                    navLink.href = `#${heading.id}`;
                    navLink.textContent = heading.textContent.replace(/^\d+\.\s*/, '').trim();
                    mainNav.appendChild(navLink);
                }
            });

            const navLinks = [...mainNav.querySelectorAll('a')];
            const sections = headings.filter((heading) => heading.tagName === 'H2');

            const setActiveSection = () => {
                let activeId = sections[0]?.id;
                sections.forEach((section) => {
                    if (section.getBoundingClientRect().top <= 110) activeId = section.id;
                });
                navLinks.forEach((link) => link.classList.toggle('active', link.hash === `#${activeId}`));
            };

            setActiveSection();
            document.addEventListener('scroll', setActiveSection, { passive: true });
        })();
    </script>
</body>
</html>
