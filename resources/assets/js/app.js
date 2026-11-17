// SarafBook shell script, from the fida-admin template of Fida Technologies (as used in Rahnuma).
// The sidebar, theme, toasts, confirm boxes, page search, number boxes and tables on phones.
// Drop-downs, pop-ups and tabs are Bootstrap's own (public/js/vendor/bootstrap.bundle.min.js).
// Source file. public/js/app.js is generated from it by: php tools/build-assets.php
// Write comments on their own lines, starting with two slashes.
(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var ERP = (window.ERP = {});
    var SPRITE = root.getAttribute('data-icons') || '/icons.svg';
    var T = window.ERP_TEXT || {};

    var $ = function (sel, scope) { return (scope || document).querySelector(sel); };
    var $$ = function (sel, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(sel)); };
    var mq = function (q) { return window.matchMedia(q).matches; };
    var appMode = function () { return mq('(max-width: 991.98px)'); };
    var phone = function () { return mq('(max-width: 767.98px)'); };

    ERP.escape = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    ERP.icon = function (id, cls) {
        return '<i class="fi ' + (cls || '') + '"><svg aria-hidden="true"><use href="' + SPRITE + '#' + id + '"/></svg></i>';
    };
    function setIcon(el, id) {
        var use = el && $('use', el);
        if (use) use.setAttribute('href', SPRITE + '#' + id);
    }

    // ---------------------------------------------------------------- settings
    var settings = {};
    try { settings = JSON.parse(localStorage.getItem('erp.ui') || '{}'); } catch (e) { settings = {}; }
    function save() {
        try { localStorage.setItem('erp.ui', JSON.stringify(settings)); } catch (e) { return; }
    }
    function theme() { return root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light'; }
    function syncTheme() {
        $$('[data-theme-toggle]').forEach(function (b) { setIcon(b, theme() === 'dark' ? 's-sun' : 's-moon'); });
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-theme-toggle]')) return;
        settings.theme = theme() === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-bs-theme', settings.theme);
        save();
        syncTheme();
    });

    // ----------------------------------------------------------------- sidebar
    function toggleLayout() {
        settings.layout = root.getAttribute('data-layout') === 'collapsed' ? 'vertical' : 'collapsed';
        root.setAttribute('data-layout', settings.layout);
        save();
    }
    function closeDrawer() { body.classList.remove('sidebar-open'); }
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t.closest('#sidebarToggle')) {
            if (appMode()) body.classList.toggle('sidebar-open'); else toggleLayout();
        } else if (t.closest('#sidebarPin')) {
            toggleLayout();
        } else if (t.closest('#tabMenu')) {
            body.classList.add('sidebar-open');
        } else if (t.closest('#sidebarBackdrop')) {
            closeDrawer();
        } else if (t.closest('#appBack')) {
            var same = false;
            try { same = !!document.referrer && new URL(document.referrer).origin === location.origin; } catch (err) { same = false; }
            if (history.length > 1 && same) history.back(); else location.href = root.getAttribute('data-home') || '/';
        }
    });

    // ------------------------------------------------- Bootstrap's own script
    // Drop-downs, pop-ups, tabs and the sidebar groups are opened by Bootstrap (js/vendor), so the
    // pages written for the earlier SarafBook keep working. These are the few calls the shell needs.
    var BS = function () { return window.bootstrap || null; };
    function closeDropdowns() {
        if (!BS()) return;
        $$('[data-bs-toggle="dropdown"].show').forEach(function (t) {
            var d = BS().Dropdown.getInstance(t);
            if (d) d.hide();
        });
    }
    ERP.showModal = function (modal) {
        if (typeof modal === 'string') modal = $(modal);
        if (!modal || !BS()) return;
        closeDrawer();
        BS().Modal.getOrCreateInstance(modal).show();
    };
    ERP.hideModal = function (modal) {
        if (typeof modal === 'string') modal = $(modal);
        if (!modal || !BS()) return;
        var m = BS().Modal.getInstance(modal);
        if (m) m.hide();
    };
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !$('.modal.show')) closeDrawer();
    });

    // ------------------------------------------------------------------ toasts
    ERP.toast = function (message, type) {
        var box = $('#toasts');
        if (!box) { box = document.createElement('div'); box.id = 'toasts'; box.className = 'erp-toasts'; body.appendChild(box); }
        type = type || 'success';
        var ico = { success: 's-circle-check', danger: 's-circle-xmark', warning: 's-triangle-exclamation', info: 's-circle-info' }[type] || 's-circle-info';
        var el = document.createElement('div');
        el.className = 'erp-toast is-' + type;
        el.setAttribute('role', 'status');
        el.innerHTML = ERP.icon(ico) + '<span>' + ERP.escape(message) + '</span>';
        box.appendChild(el);
        setTimeout(function () { el.classList.add('is-out'); setTimeout(function () { el.remove(); }, 250); }, type === 'danger' ? 6000 : 3200);
    };
    ERP.flush = function () {
        $$('[data-toast]').forEach(function (el) { ERP.toast(el.textContent.trim(), el.getAttribute('data-toast')); el.remove(); });
    };
    ERP.flush();

    // ----------------------------------------------------------------- confirm
    // <form data-confirm="Sign out?" data-confirm-text="..." data-confirm-ok="Sign out" data-confirm-style="primary">
    ERP.confirm = function (o, onOk) {
        var style = o.style || 'danger';
        var modal = document.createElement('div');
        modal.className = 'modal fade erp-confirm';
        modal.tabIndex = -1;
        modal.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body text-center">'
            + '<div class="erp-confirm-icon text-' + style + '">' + ERP.icon(style === 'danger' ? 's-triangle-exclamation' : 's-circle-question') + '</div>'
            + '<h5 class="mb-1">' + ERP.escape(o.title || T.confirmTitle || 'Are you sure?') + '</h5>'
            + (o.text ? '<p class="text-muted mb-0">' + ERP.escape(o.text) + '</p>' : '')
            + '<div class="d-flex gap-2 mt-4"><button type="button" class="btn btn-light-secondary flex-fill" data-bs-dismiss="modal">' + ERP.escape(T.cancel || 'Cancel') + '</button>'
            + '<button type="button" class="btn btn-' + style + ' flex-fill" data-ok>' + ERP.escape(o.ok || T.confirmOk || 'Yes') + '</button></div>'
            + '</div></div></div>';
        body.appendChild(modal);
        modal.addEventListener('hidden.bs.modal', function () { modal.remove(); });
        $('[data-ok]', modal).addEventListener('click', function () { ERP.hideModal(modal); onOk(); });
        modal.addEventListener('shown.bs.modal', function () { $('[data-bs-dismiss]', modal).focus(); });
        ERP.showModal(modal);
    };
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || form.hasAttribute('data-quick-form')) return;
        if (form.hasAttribute('data-confirm') && !form._confirmed) {
            e.preventDefault();
            ERP.confirm({
                title: form.getAttribute('data-confirm'), text: form.getAttribute('data-confirm-text'),
                ok: form.getAttribute('data-confirm-ok'), style: form.getAttribute('data-confirm-style'),
            }, function () { form._confirmed = true; if (form.requestSubmit) form.requestSubmit(); else form.submit(); });
            return;
        }
        // a form can be sent only once: stops double posting on slow connections
        if (form._sent) { e.preventDefault(); return; }
        form._sent = true;
        $$('button[type="submit"], button:not([type])', form).forEach(function (b) { b.classList.add('is-busy'); b.setAttribute('aria-disabled', 'true'); });
    });
    // coming back with the browser back button re-enables the form
    window.addEventListener('pageshow', function () {
        $$('form').forEach(function (f) { f._sent = false; f._confirmed = false; });
        $$('.is-busy').forEach(function (b) { b.classList.remove('is-busy'); b.removeAttribute('aria-disabled'); });
    });

    // ------------------------------------------------------- password show/hide
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        var input = $('input', btn.closest('.auth-field, .input-group, .input-icon'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        setIcon(btn, show ? 'r-eye-slash' : 'r-eye');
    });

    // --------------------------------------------------------- command search
    var results = $('#searchResults');
    var input = $('#searchInput');
    if (results && input) {
        var index = $$('#sidebarMenu a[href]').filter(function (a) { return a.getAttribute('href').charAt(0) !== '#'; }).map(function (a) {
            var group = a.closest('.has-sub');
            var text = $('.menu-text', a);
            var use = $('.menu-icon use', group || a);
            return {
                label: (text ? text.textContent : a.textContent).trim(),
                parent: group ? $('.menu-text', group).textContent.trim() : '',
                href: a.getAttribute('href'),
                icon: use ? use.getAttribute('href').split('#')[1] : 's-file',
            };
        });
        var focus = 0;
        var render = function (q) {
            q = (q || '').trim().toLowerCase();
            var hits = index.filter(function (i) { return !q || (i.label + ' ' + i.parent).toLowerCase().indexOf(q) > -1; }).slice(0, 30);
            focus = 0;
            results.innerHTML = hits.length ? hits.map(function (h, n) {
                return '<a class="search-item ' + (n === 0 ? 'focus' : '') + '" href="' + ERP.escape(h.href) + '"><span class="si-icon">' + ERP.icon(h.icon) + '</span>'
                    + '<span><strong>' + ERP.escape(h.label) + '</strong><small>' + ERP.escape(h.parent) + '</small></span>' + ERP.icon('s-arrow-right', 'si-go') + '</a>';
            }).join('') : '<div class="search-empty">' + ERP.escape(T.noResults || 'No results') + '</div>';
        };
        $('#searchModal').addEventListener('shown.bs.modal', function () { input.value = ''; input.focus(); render(''); });
        input.addEventListener('input', function () { render(input.value); });
        input.addEventListener('keydown', function (e) {
            var items = $$('.search-item', results);
            if (!items.length) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                focus = (focus + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items.forEach(function (a, n) { a.classList.toggle('focus', n === focus); });
                items[focus].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                items[focus].click();
            }
        });
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); ERP.showModal('#searchModal'); }
        });
    }


    // ------------------------------------------------------- number boxes
    // Digits typed with a Dari, Pashto or Arabic keyboard become 0 to 9 in every box that holds a
    // phone or a number written left to right.
    ERP.latinDigits = function (value) {
        return String(value).replace(/[۰-۹]/g, function (d) { return String(d.charCodeAt(0) - 0x06f0); })
            .replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 0x0660); })
            .replace(/٫/g, '.').replace(/٬/g, ',');
    };
    var DIGITS = 'input[inputmode="decimal"], input[inputmode="numeric"], input[inputmode="tel"], input[type="tel"], input[data-digits]';
    document.addEventListener('input', function (e) {
        if (!e.target.matches || !e.target.matches(DIGITS)) return;
        var before = e.target.value;
        var after = ERP.latinDigits(before);
        if (after === before) return;
        var caret = e.target.selectionStart;
        e.target.value = after;
        try { if (caret != null) e.target.setSelectionRange(caret, caret); } catch (err) { return; }
    }, true);

    // ------------------------------------------- tables become cards on phones
    var SKIP = '.keep-table, .table-sm, .dataTable, .no-cards, [data-no-cards]';
    ERP.cardify = function (table) {
        if (table.matches(SKIP)) return;
        if (!phone()) { table.classList.remove('table-cards'); return; }
        var heads = $$('thead tr:first-child th', table).map(function (th) { return th.textContent.trim(); });
        $$('tbody > tr', table).forEach(function (tr) {
            var cells = Array.prototype.slice.call(tr.children);
            cells.forEach(function (td, i) {
                if (td.tagName !== 'TD') return;
                td.setAttribute('data-label', heads[i] || '');
                td.classList.toggle('cell-primary', i === 0);
                td.classList.toggle('cell-actions', i === cells.length - 1 && !!$('.btn, .btn-act, .dropdown', td));
            });
        });
        table.classList.add('table-cards');
    };
    var cardifyAll = function () { $$('.app-content table.table').forEach(ERP.cardify); };

    // ------------------------------------------- wide tables scroll, long texts wrap
    ERP.fitTable = function (table) {
        if (table._fit || table.matches(SKIP) || table.closest('.dataTables_wrapper, .dt-container')) return;
        table._fit = true;
        if (!table.closest('.table-responsive, .table-scroll')) {
            var box = document.createElement('div');
            box.className = 'table-scroll';
            table.parentNode.insertBefore(box, table);
            box.appendChild(table);
        } else {
            var old = table.closest('.table-responsive');
            if (old) old.classList.add('table-scroll');
        }
        $$('tbody > tr > td', table).forEach(function (td) {
            if (td.children.length > 1 || $('.btn, input, select, textarea, .badge', td)) return;
            if (td.textContent.trim().length > 42) td.classList.add('cell-long');
        });
    };
    var fitAll = function () { $$('.app-content table.table').forEach(ERP.fitTable); };
    var resizeTimer;
    window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(cardifyAll, 200); });

    ERP.rehydrate = function () {
        body.classList.toggle('no-large-title', !$('.app-content h1'));
        syncTheme();
        ERP.flush();
    };

    // ------------------------------------------- moving between pages, loaded early
    // Each page is an ordinary page, but the next ones are ready before they are opened
    // (docs/SPEED.md):
    //  1. once this page has fully loaded and the browser is idle, the pages this user opens most
    //     (learned on this device), then the menu, are fetched in the background: HTML only, none
    //     of their scripts run, a few at a time, fewer on slow connections, none with Data Saver;
    //  2. the page under the pointer is fetched on hover (speculation rules in partials/scripts);
    //  3. libraries other pages need are downloaded once (public/warm.json);
    //  4. anything saved drops every early page (and the server says the same with
    //     Clear-Site-Data), and a page shown again by Back is reloaded if something was saved
    //     since, so a figure on screen is never older than the last save.
    var progress = $('#navProgress');
    var cfgNode = $('#prefetchConfig');
    var cfg = { eager: 0, never: [], warm: [] };
    try { if (cfgNode) cfg = JSON.parse(cfgNode.textContent); } catch (e) {}
    var store = {
        get: function (k, d) { try { var v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    };
    var loadedAt = Date.now();
    var neverRe = (cfg.never || []).map(function (p) {
        return new RegExp('^' + p.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$', 'i');
    });
    var isNever = function (path) { return neverRe.some(function (re) { return re.test(path); }); };
    var usable = function (a) {
        if (!a || !a.href || a.origin !== location.origin || a.hasAttribute('download') || a.target === '_blank'
            || a.hasAttribute('data-no-prefetch') || a.closest('form') || (a.getAttribute('href') || '').charAt(0) === '#') return false;
        return !isNever(a.pathname);
    };
    var here = location.pathname + location.search;

    // what this user opens, kept on this device and slowly forgotten
    var shown = function (fn) {
        if (document.prerendering) document.addEventListener('prerenderingchange', fn, { once: true }); else fn();
    };
    shown(function learn() {
        if (isNever(location.pathname)) return;
        var seen = store.get('sb.nav', {});
        Object.keys(seen).forEach(function (k) { seen[k] *= 0.97; if (seen[k] < 0.3) delete seen[k]; });
        seen[here] = (seen[here] || 0) + 1;
        var top = Object.keys(seen).sort(function (x, y) { return seen[y] - seen[x]; }).slice(0, 40);
        var keep = {}; top.forEach(function (k) { keep[k] = Math.round(seen[k] * 100) / 100; });
        store.set('sb.nav', keep);
    });
    // a page drawn ahead and shown only now: if something was saved since it was drawn, load it fresh
    if (document.prerendering) document.addEventListener('prerenderingchange', function () {
        if (store.get('sb.saved', 0) > loadedAt) location.reload();
        loadedAt = Date.now();
    }, { once: true });

    // how many pages this device and connection can take without slowing anything
    var budget = function () {
        var c = navigator.connection || {};
        if (c.saveData || /(^|-)2g$/.test(c.effectiveType || '')) return 0;
        var n = cfg.eager || 0;
        if (c.effectiveType === '3g' || (navigator.deviceMemory && navigator.deviceMemory < 2)) n = Math.min(n, 2);
        return n;
    };

    var candidates = function () {
        var list = [], add = function (url) {
            var u; try { u = new URL(url, location.href); } catch (e) { return; }
            var key = u.pathname + u.search;
            if (u.origin !== location.origin || key === here || isNever(u.pathname) || list.indexOf(key) !== -1) return;
            list.push(key);
        };
        // pages linked from this page that this user opens most …
        var seen = store.get('sb.nav', {});
        var linked = {};
        $$('a[href]').forEach(function (a) { if (usable(a)) linked[a.pathname + a.search] = true; });
        Object.keys(seen).sort(function (x, y) { return seen[y] - seen[x]; }).forEach(function (k) { if (linked[k] && seen[k] >= 1.5) add(k); });
        // … then the menu in its order
        $$('#sidebarMenu a.menu-link[href], #sidebarMenu a.menu-sublink[href], .app-tabbar a[href], a[data-prefetch]').forEach(function (a) { if (usable(a)) add(a.href); });
        return list;
    };

    var canSpeculate = !!(window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules'));
    var early = null, earlyUrls = [];
    var fetchEarly = function () {
        if (!canSpeculate || document.visibilityState !== 'visible') return;
        dropEarly();
        earlyUrls = candidates().slice(0, budget());
        if (!earlyUrls.length) return;
        // the most likely page is drawn ahead when it is on the prerender list; the rest are fetched
        var drawable = (cfg.prerender || []).indexOf(earlyUrls[0].split('?')[0]) !== -1 && !/[?&]/.test(earlyUrls[0]);
        var rules = { prefetch: [{ urls: drawable ? earlyUrls.slice(1) : earlyUrls, eagerness: 'immediate' }] };
        if (drawable) rules.prerender = [{ urls: [earlyUrls[0]], eagerness: 'immediate' }];
        if (!rules.prefetch[0].urls.length) delete rules.prefetch;
        early = document.createElement('script');
        early.type = 'speculationrules';
        early.textContent = JSON.stringify(rules);
        document.head.appendChild(early);
    };
    var dropEarly = function () { if (early) { early.remove(); early = null; } earlyUrls = []; };
    ERP.earlyPages = function () { return earlyUrls.slice(); };

    var warmed = false;
    var warm = function () {
        if (warmed || budget() === 0) return;
        warmed = true;
        var have = {};
        $$('link[rel=stylesheet][href], script[src]').forEach(function (el) { have[el.href || el.src] = true; });
        (cfg.warm || []).forEach(function (url) {
            var abs = new URL(url, location.href).href;
            if (have[abs]) return;
            var l = document.createElement('link');
            l.rel = 'prefetch';
            l.href = abs;
            if (/\.woff2(\?|$)/.test(abs)) { l.as = 'font'; l.crossOrigin = 'anonymous'; }
            document.head.appendChild(l);
        });
    };

    // start only when this page is complete and the browser has nothing else to do
    var whenIdle = function (fn) {
        var go = function () { (window.requestIdleCallback || function (f) { return setTimeout(f, 1200); })(fn, { timeout: 4000 }); };
        if (document.readyState === 'complete') setTimeout(go, 600); else window.addEventListener('load', function () { setTimeout(go, 600); }, { once: true });
    };
    whenIdle(function () {
        if (document.visibilityState === 'visible') { fetchEarly(); warm(); }
        else document.addEventListener('visibilitychange', function once() {
            if (document.visibilityState !== 'visible') return;
            document.removeEventListener('visibilitychange', once);
            whenIdle(function () { fetchEarly(); warm(); });
        });
    });

    // a save on this page (form, fetch or the older pages' jQuery) drops the early pages; they are
    // fetched again a moment after the save has finished
    var saved = function () {
        store.set('sb.saved', Date.now());
        dropEarly();
        clearTimeout(saved.t);
        saved.t = setTimeout(fetchEarly, 2500);
    };
    var writes = /^(POST|PUT|PATCH|DELETE)$/i;
    document.addEventListener('submit', function (e) {
        var m = (e.target.getAttribute('method') || 'GET');
        var spoof = e.target.querySelector('input[name=_method]');
        if (writes.test(m) || (spoof && writes.test(spoof.value))) store.set('sb.saved', Date.now());
    }, true);
    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            var m = (init && init.method) || (input && input.method) || 'GET';
            var p = nativeFetch.apply(this, arguments);
            if (writes.test(m)) p.then(saved, saved);
            return p;
        };
    }
    if (window.XMLHttpRequest) {
        var nativeOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method) {
            if (writes.test(method || '')) this.addEventListener('loadend', saved);
            return nativeOpen.apply(this, arguments);
        };
    }
    // a save in another tab of the same Sarafi
    window.addEventListener('storage', function (e) { if (e.key === 'sb.saved') { dropEarly(); clearTimeout(saved.t); saved.t = setTimeout(fetchEarly, 2500); } });

    // ------------------------------------------- timing in the user's own browser
    // A few numbers per page (and per list change) go to /_timing, so speed is measured where the
    // users are, not only from the server: time to the first byte, to the first paint, to ready
    // and to done, how the page came (early or not) and the connection. No query string, no data.
    var sendTiming = function (v) {
        try {
            var c = navigator.connection || {};
            v.p = location.pathname; v.rtt = c.rtt || 0; v.net = c.effectiveType || '';
            var q = Object.keys(v).map(function (k) { return k + '=' + encodeURIComponent(v[k]); }).join('&');
            nativeFetchForTiming('/_timing?' + q, { credentials: 'omit', keepalive: true, cache: 'no-store' }).catch(function () {});
        } catch (e) {}
    };
    var nativeFetchForTiming = window.fetch ? window.fetch.bind(window) : function () { return Promise.reject(); };
    ERP.timing = function (kind, ms) { sendTiming({ k: kind, ttfb: 0, fcp: Math.round(ms), dcl: Math.round(ms), load: Math.round(ms) }); };
    shown(function () {
        var report = function () {
            var n = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
            if (!n) return;
            var start = n.activationStart || 0;
            var at = function (t) { return Math.max(0, Math.round(t - start)); };
            var paint = (performance.getEntriesByName('first-contentful-paint') || [])[0];
            sendTiming({
                k: start > 0 ? 'drawn' : (n.type === 'back_forward' ? 'back' : (n.type === 'reload' ? 'reload' : 'open')),
                d: n.deliveryType || (n.transferSize === 0 && n.decodedBodySize > 0 ? 'cache' : 'network'),
                ttfb: at(n.responseStart), fcp: paint ? at(paint.startTime) : 0,
                dcl: at(n.domContentLoadedEventEnd), load: at(n.loadEventEnd)
            });
        };
        if (document.readyState === 'complete') setTimeout(report, 0); else window.addEventListener('load', function () { setTimeout(report, 0); }, { once: true });
    });

    // Back and Forward show the page as it was left (instant); if something was saved since, it is loaded fresh
    window.addEventListener('pageshow', function (e) {
        if (progress) progress.classList.remove('is-active');
        if (e.persisted && store.get('sb.saved', 0) > loadedAt) location.reload();
    });

    // the thin bar on top while a page that was not loaded early is on its way
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || !progress) return;
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.origin !== location.origin || a.target === '_blank' || a.hasAttribute('download') || (a.getAttribute('href') || '').charAt(0) === '#') return;
        if (a.pathname === location.pathname && a.search === location.search) return;
        progress.classList.add('is-active');
    });

    // ------------------------------------------------ select with search box
    // <select data-search> becomes a box you can type in. No library needed.
    ERP.searchSelect = function (select) {
        if (select._ss || select.disabled) return;
        select._ss = true;
        var wrap = document.createElement('div');
        wrap.className = 'ss' + (select.classList.contains('form-select-sm') ? ' ss-sm' : '');
        var box = document.createElement('input');
        box.type = 'text';
        box.className = 'form-control ' + (select.classList.contains('is-invalid') ? 'is-invalid' : '');
        box.autocomplete = 'off';
        box.placeholder = select.options.length && !select.options[0].value ? select.options[0].text : '';
        if (select.required) { box.required = true; select.required = false; }
        if (select.id) { box.id = select.id; select.removeAttribute('id'); }
        var list = document.createElement('div');
        list.className = 'ss-list';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(box);
        // a small cross empties the choice, when the list has an empty first line
        var clear = null;
        if (select.options.length && !select.options[0].value) {
            clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'ss-clear';
            clear.tabIndex = -1;
            clear.setAttribute('aria-label', T.cancel || 'Clear');
            clear.innerHTML = '&times;';
            wrap.appendChild(clear);
        }
        wrap.appendChild(list);
        wrap.appendChild(select);
        select.hidden = true;
        var focus = 0;
        var current = function () { var o = select.options[select.selectedIndex]; return o && o.value ? o.text : ''; };
        var close = function () { wrap.classList.remove('open', 'up'); };
        var mark = function () { wrap.classList.toggle('has-value', !!select.value); };
        var show = function (all) {
            var q = all ? '' : box.value.trim().toLowerCase();
            var html = '';
            var n = 0;
            for (var i = 0; i < select.options.length && n < 60; i++) {
                var o = select.options[i];
                if (!o.value || o.disabled || o.hidden || (q && o.text.toLowerCase().indexOf(q) < 0)) continue;
                html += '<div class="ss-item' + (n === 0 ? ' focus' : '') + (o.value === select.value ? ' selected' : '') + '" data-value="' + ERP.escape(o.value) + '">' + ERP.escape(o.text) + '</div>';
                n++;
            }
            list.innerHTML = html || '<div class="ss-empty">' + ERP.escape(T.noResults || 'No results') + '</div>';
            focus = 0;
            wrap.classList.add('open');
            // open upwards when there is no room under the box
            var rect = wrap.getBoundingClientRect();
            wrap.classList.toggle('up', window.innerHeight - rect.bottom < 280 && rect.top > 280);
            var chosen = $('.ss-item.selected', list);
            if (chosen && all) chosen.scrollIntoView({ block: 'nearest' });
        };
        var pick = function (value) {
            select.value = value;
            box.value = current();
            mark();
            close();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        };
        box.value = current();
        mark();
        if (clear) clear.addEventListener('mousedown', function (e) { e.preventDefault(); pick(''); box.focus(); });
        box.addEventListener('focus', function () { box.select(); show(true); });
        // clicking a box that already has the cursor opens the list again, ready to type over
        box.addEventListener('click', function () { if (!wrap.classList.contains('open')) { box.select(); show(true); } });
        box.addEventListener('input', function () { show(false); });
        box.addEventListener('keydown', function (e) {
            var items = $$('.ss-item', list);
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!items.length) return;
                focus = (focus + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items.forEach(function (it, n) { it.classList.toggle('focus', n === focus); });
                items[focus].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && wrap.classList.contains('open')) {
                e.preventDefault();
                if (items[focus]) pick(items[focus].getAttribute('data-value'));
            } else if (e.key === 'Escape') {
                close();
            }
        });
        // mousedown comes before blur, so the choice is not lost
        list.addEventListener('mousedown', function (e) {
            var item = e.target.closest('.ss-item');
            if (item) { e.preventDefault(); pick(item.getAttribute('data-value')); }
        });
        // the options or the value were changed from outside: show the chosen text again
        select._ssSync = function () { box.value = current(); mark(); };
        // a value set by another script (with a change event) shows in the box too
        select.addEventListener('change', function () { if (document.activeElement !== box) select._ssSync(); });
        box.addEventListener('blur', function () {
            if (box.value.trim() === '' && select.value !== '') pick('');
            else box.value = current();
            close();
        });
    };
    // Lists marked data-search, and every list long enough to need it (8 choices or more), become searchable.
    var modernPage = !window.jQuery;
    ERP.enhance = function (scope) {
        $$('select', scope || document).forEach(function (select) {
            if (select.classList.contains('select2') || select.multiple || select.hasAttribute('data-plain') || select.closest('.input-group')) return;
            if (select.hasAttribute('data-search') || (modernPage && select.options.length >= 9)) ERP.searchSelect(select);
        });
    };

    // ------------------------------------------- lists that change in place (docs/DESIGN.md)
    // <form data-live="list,summary"> filters a list; the parts are marked data-live-part="list"
    // (Blade @fragment('list') around them). A change asks only for those parts (X-Fragment) and
    // swaps them; the address follows, Back works, the next page is fetched on hover, and the
    // first list is kept so going back to it needs no request.
    ERP.live = function (form) {
        if (!form || form._live || !window.fetch || !window.DOMParser) return;
        form._live = true;
        var names = (form.getAttribute('data-live') || 'list').split(',').map(function (s) { return s.trim(); });
        var parts = function () { var o = {}; names.forEach(function (n) { o[n] = $('[data-live-part="' + n + '"]'); }); return o; };
        var main = function () { return parts()[names[0]]; };
        if (!main()) return;
        var cache = {}, current = null, typing = null;
        var savedAt = function () { return store.get('sb.saved', 0); };
        var clearBtn = $('[data-live-clear]', form);
        var urlOf = function () {
            var params = new URLSearchParams(new FormData(form));
            Array.from(params.keys()).forEach(function (k) { if (!params.get(k) || k === 'page') params.delete(k); });
            $$('[data-default]', form).forEach(function (el) { if (params.get(el.name) === el.getAttribute('data-default')) params.delete(el.name); });
            var q = params.toString();
            return location.pathname + (q ? '?' + q : '');
        };
        var get = function (url) {
            var hit = cache[url];
            if (hit && hit.at > savedAt() && Date.now() - hit.at < 60000) return hit.p;
            var p = fetch(url, { headers: { 'X-Fragment': names.join(','), 'Accept': 'text/html' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok || r.redirected) throw new Error(r.status); return r.text(); });
            cache[url] = { at: Date.now(), p: p };
            p.catch(function () { delete cache[url]; });
            return p;
        };
        var show = function (url, push) {
            var mine = current = url, started = performance.now(), box = main();
            box.classList.add('is-loading'); box.setAttribute('aria-busy', 'true');
            if (progress) progress.classList.add('is-active');
            get(url).then(function (html) {
                if (mine !== current) return;
                var doc = new DOMParser().parseFromString(html, 'text/html'), now = parts();
                names.forEach(function (n) {
                    var next = doc.querySelector('[data-live-part="' + n + '"]');
                    if (!next) throw new Error('part ' + n);
                    if (now[n]) now[n].replaceWith(next);
                });
                var filtered = (main().getAttribute('data-filtered') === '1');
                if (clearBtn) clearBtn.hidden = !filtered;
                if (push === 'push') history.pushState({ live: 1 }, '', url);
                else if (push === 'replace') history.replaceState({ live: 1 }, '', url);
                $$('table.table', main()).forEach(function (t) { ERP.cardify(t); });
                document.dispatchEvent(new CustomEvent('erp:live', { detail: { form: form } }));
                if (ERP.timing) ERP.timing('list', performance.now() - started);
            }).catch(function () {
                if (mine === current) location.href = url;
            }).finally(function () {
                if (mine !== current) return;
                var b = main(); if (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); }
                if (progress) progress.classList.remove('is-active');
            });
        };
        var sync = function (url) {
            var params = new URL(url, location.href).searchParams;
            Array.from(form.elements).forEach(function (el) {
                if (!el.name || el.type === 'submit' || el.type === 'hidden') return;
                if (el.type === 'checkbox' || el.type === 'radio') el.checked = params.getAll(el.name).indexOf(el.value) !== -1;
                else el.value = params.get(el.name) || el.getAttribute('data-default') || '';
            });
        };
        history.replaceState({ live: 1 }, '', location.href);
        var start = location.pathname + location.search, keep = '';
        names.forEach(function (n) { var p = parts()[n]; if (p) keep += p.outerHTML; });
        cache[start] = { at: Date.now(), p: Promise.resolve(keep) };

        form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(typing); show(urlOf(), 'push'); });
        form.addEventListener('change', function (e) {
            if (e.target.matches('select, input[type=checkbox], input[type=radio], input[type=date], input[type=month]')) show(urlOf(), 'push');
        });
        form.addEventListener('input', function (e) {
            if (!e.target.matches('input[type=search], input[type=text]')) return;
            clearTimeout(typing);
            typing = setTimeout(function () { show(urlOf(), 'replace'); }, 300);
        });
        if (clearBtn) clearBtn.addEventListener('click', function (e) {
            e.preventDefault(); sync(location.pathname); show(location.pathname, 'push');
        });
        var pageLink = function (e) {
            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || !a.closest('.pagination, [data-live-link]')) return null;
            var box = main(); return box && box.contains(a) && a.origin === location.origin ? a : null;
        };
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
            var a = pageLink(e); if (!a) return;
            e.preventDefault();
            show(a.pathname + a.search, 'push');
            var top = form.getBoundingClientRect().top;
            if (top < 0) form.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }, true);
        document.addEventListener('pointerover', function (e) { var a = pageLink(e); if (a) get(a.pathname + a.search).catch(function () {}); });
        window.addEventListener('popstate', function (e) {
            if (!e.state || !e.state.live) return;
            sync(location.href);
            show(location.pathname + location.search, null);
        });
    };

    // ------------------------------------------- one pop-up form for "Add" and "Edit"
    // A button with data-modal-form="#id" opens the form; with data-fill='{"name":"…"}',
    // data-action and data-method it opens it for changing that row. The form posts the
    // ordinary way; after an error the page comes back and the form opens again as it was.
    var fillForm = function (form, data) {
        Object.keys(data || {}).forEach(function (key) {
            var value = data[key];
            var fields = $$('[name="' + key + '"], [name="' + key + '[]"]', form);
            var hasBox = fields.some(function (el) { return el.type === 'checkbox'; });
            fields.forEach(function (el) {
                if (el.type === 'hidden' && hasBox) return; // the 0 sent for an unticked switch
                if (el.type === 'checkbox') el.checked = Array.isArray(value) ? value.map(String).indexOf(el.value) !== -1 : !!value && value !== '0';
                else if (el.type === 'radio') el.checked = String(value) === el.value;
                else if (el.multiple) Array.from(el.options).forEach(function (o) { o.selected = (value || []).map(String).indexOf(o.value) !== -1; });
                else el.value = value == null ? '' : value;
            });
        });
    };
    ERP.openForm = function (modal, opts) {
        if (typeof modal === 'string') modal = $(modal);
        if (!modal) return;
        var form = $('form', modal); if (!form) return;
        opts = opts || {};
        form.reset();
        $$('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
        $$('.invalid-feedback.d-block, [data-form-errors]', form).forEach(function (el) { el.remove(); });
        var editing = !!opts.fill;
        form.action = opts.action || form.getAttribute('data-store') || form.action;
        var method = $('input[name=_method]', form); if (method) method.value = opts.method || (editing ? 'PUT' : 'POST');
        var again = $('input[name=_action]', form); if (again) again.value = form.action;
        var title = $('[data-title-new]', modal);
        if (title) title.textContent = editing ? title.getAttribute('data-title-edit') : title.getAttribute('data-title-new');
        $$('[data-only-new]', form).forEach(function (el) { el.hidden = editing; $$('input,select,textarea', el).forEach(function (f) { f.disabled = editing; }); });
        $$('[data-only-edit]', form).forEach(function (el) { el.hidden = !editing; $$('input,select,textarea', el).forEach(function (f) { f.disabled = !editing; }); });
        if (opts.fill) fillForm(form, opts.fill);
        form.dispatchEvent(new CustomEvent('erp:form-open', { bubbles: true, detail: { editing: editing, data: opts.fill || null } }));
        ERP.showModal(modal);
    };
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-modal-form]');
        if (!b) return;
        e.preventDefault();
        var fill = null;
        try { fill = b.getAttribute('data-fill') ? JSON.parse(b.getAttribute('data-fill')) : null; } catch (err) {}
        ERP.openForm(b.getAttribute('data-modal-form'), { fill: fill, action: b.getAttribute('data-action'), method: b.getAttribute('data-method') });
    });
    document.addEventListener('shown.bs.modal', function (e) {
        var first = $('[autofocus], input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea', e.target);
        if (first && !first.closest('[hidden]')) try { first.focus(); } catch (err) {}
    });

    // -------------------------------------------------------------------- boot
    var onScroll = function () { body.classList.toggle('is-scrolled', window.scrollY > 28); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    ERP.rehydrate();
    $$('form[data-live]').forEach(ERP.live);
    ERP.enhance(document);
    document.addEventListener('erp:live', function () { ERP.enhance(document); });
    var reopen = $('.modal[data-reopen]'); if (reopen) ERP.showModal(reopen);
    // tables are prepared once the scripts of the page (DataTables and others) have run
    window.addEventListener('load', function () { fitAll(); cardifyAll(); });
    var active = $('#sidebarMenu .menu-sublink.active, #sidebarMenu .menu-item.active > .menu-link');
    var scroller = $('#sidebarScroll');
    if (active && scroller) scroller.scrollTop = Math.max(0, active.getBoundingClientRect().top - scroller.clientHeight / 2);
    body.classList.add('is-ready', 'app-ready');
})();
