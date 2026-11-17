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
    (function learn() {
        if (isNever(location.pathname)) return;
        var seen = store.get('sb.nav', {});
        Object.keys(seen).forEach(function (k) { seen[k] *= 0.97; if (seen[k] < 0.3) delete seen[k]; });
        seen[here] = (seen[here] || 0) + 1;
        var top = Object.keys(seen).sort(function (x, y) { return seen[y] - seen[x]; }).slice(0, 40);
        var keep = {}; top.forEach(function (k) { keep[k] = Math.round(seen[k] * 100) / 100; });
        store.set('sb.nav', keep);
    })();

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
        early = document.createElement('script');
        early.type = 'speculationrules';
        early.textContent = JSON.stringify({ prefetch: [{ urls: earlyUrls, eagerness: 'immediate' }] });
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

    // -------------------------------------------------------------------- boot
    var onScroll = function () { body.classList.toggle('is-scrolled', window.scrollY > 28); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    ERP.rehydrate();
    // tables are prepared once the scripts of the page (DataTables and others) have run
    window.addEventListener('load', function () { fitAll(); cardifyAll(); });
    var active = $('#sidebarMenu .menu-sublink.active, #sidebarMenu .menu-item.active > .menu-link');
    var scroller = $('#sidebarScroll');
    if (active && scroller) scroller.scrollTop = Math.max(0, active.getBoundingClientRect().top - scroller.clientHeight / 2);
    body.classList.add('is-ready', 'app-ready');
})();
