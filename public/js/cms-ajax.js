(function (window, document) {
    'use strict';

    var activeRequest = null;
    var isNavigating = false;
    var baselineDocumentHandlers = [];

    if (window.jQuery && window.jQuery._data) {
        var initialEvents = window.jQuery._data(document, 'events') || {};
        Object.keys(initialEvents).forEach(function (eventType) {
            initialEvents[eventType].forEach(function (handler) {
                baselineDocumentHandlers.push(handler);
            });
        });
    }

    function sameOrigin(url) {
        return url.origin === window.location.origin;
    }

    function beginLoading() {
        document.body.classList.remove('cms-ajax-complete');
        document.body.classList.add('cms-ajax-busy');
    }

    function finishLoading() {
        document.body.classList.remove('cms-ajax-busy');
        document.body.classList.add('cms-ajax-complete');
        window.setTimeout(function () {
            document.body.classList.remove('cms-ajax-complete');
        }, 260);
        if (window.swal && typeof window.swal.close === 'function') {
            try {
                window.swal.close();
            } catch (error) {
                // No-op: some sweetalert versions throw when closing while no alert is open.
            }
        }
    }

    function between(start, end) {
        var nodes = [];
        var current = start ? start.nextSibling : null;
        while (current && current !== end) {
            nodes.push(current);
            current = current.nextSibling;
        }
        return nodes;
    }

    function replacePageStyles(nextDocument) {
        var currentStart = document.getElementById('cms-page-styles-start');
        var currentEnd = document.getElementById('cms-page-styles-end');
        var nextStart = nextDocument.getElementById('cms-page-styles-start');
        var nextEnd = nextDocument.getElementById('cms-page-styles-end');
        if (!currentStart || !currentEnd || !nextStart || !nextEnd) return;

        between(currentStart, currentEnd).forEach(function (node) { node.remove(); });
        between(nextStart, nextEnd).forEach(function (node) {
            currentEnd.parentNode.insertBefore(document.importNode(node, true), currentEnd);
        });
    }

    function cleanupPageDocumentHandlers() {
        if (!window.jQuery || !window.jQuery._data) return;
        var events = window.jQuery._data(document, 'events') || {};
        Object.keys(events).forEach(function (eventType) {
            events[eventType].slice().forEach(function (handler) {
                if (baselineDocumentHandlers.indexOf(handler) !== -1) return;
                var eventName = eventType + (handler.namespace ? '.' + handler.namespace : '');
                window.jQuery(document).off(eventName, handler.selector, handler.handler);
            });
        });
    }

    function withDomReadyShim(fn) {
        var originalAddEventListener = document.addEventListener;
        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded') {
                // The real DOMContentLoaded event already fired once for this document.
                // Page scripts replayed after an in-app AJAX navigation register a fresh
                // listener expecting init-on-ready semantics, so invoke it directly instead
                // of binding a listener that would never fire again.
                window.setTimeout(listener, 0);
                return;
            }
            return originalAddEventListener.call(document, type, listener, options);
        };

        try {
            fn();
        } finally {
            document.addEventListener = originalAddEventListener;
        }
    }

    function executeScript(sourceScript, beforeNode) {
        return new Promise(function (resolve) {
            var type = sourceScript.getAttribute('type');
            if (type && type !== 'text/javascript' && type !== 'application/javascript') {
                resolve();
                return;
            }

            if (sourceScript.src) {
                var external = document.createElement('script');
                Array.prototype.forEach.call(sourceScript.attributes, function (attribute) {
                    external.setAttribute(attribute.name, attribute.value);
                });
                external.onload = resolve;
                external.onerror = resolve;
                beforeNode.parentNode.insertBefore(external, beforeNode);
                return;
            }

            try {
                // Function scope prevents repeated page visits from redeclaring top-level let/const.
                withDomReadyShim(function () {
                    (new Function(sourceScript.textContent))();
                });
            } catch (error) {
                window.console.error('CMS page script failed:', error);
            }
            resolve();
        });
    }

    function executeInlineGroup(scripts) {
        if (!scripts.length) return;

        var code = 'var table, columns, getUrl, showUrl, editUrl, destroyUrl, orders, fixedColumns, scrollX, searching, ajax;\n';
        code += scripts.map(function (script) {
            return script.textContent;
        }).join('\n;\n');
        var functionNames = [];
        var functionPattern = /(?:^|[;\n\r}])\s*function\s+([A-Za-z_$][\w$]*)\s*\(/g;
        var match;

        while ((match = functionPattern.exec(code)) !== null) {
            if (functionNames.indexOf(match[1]) === -1) functionNames.push(match[1]);
        }

        code += '\n' + functionNames.map(function (name) {
            return "if (typeof " + name + " === 'function') window['" + name + "'] = " + name + ";";
        }).join('\n');

        try {
            // One function gives all inline script tags on a page the same scope.
            withDomReadyShim(function () {
                (new Function(code))();
            });
        } catch (error) {
            window.console.error('CMS page scripts failed:', error);
        }
    }

    async function replacePageScripts(nextDocument) {
        var currentStart = document.getElementById('cms-page-scripts-start');
        var currentEnd = document.getElementById('cms-page-scripts-end');
        var nextStart = nextDocument.getElementById('cms-page-scripts-start');
        var nextEnd = nextDocument.getElementById('cms-page-scripts-end');
        if (!currentStart || !currentEnd || !nextStart || !nextEnd) return;

        between(currentStart, currentEnd).forEach(function (node) { node.remove(); });
        var scripts = between(nextStart, nextEnd).filter(function (node) {
            return node.nodeType === 1 && node.tagName === 'SCRIPT';
        });
        var inlineGroup = [];

        for (var index = 0; index < scripts.length; index += 1) {
            if (scripts[index].src) {
                executeInlineGroup(inlineGroup);
                inlineGroup = [];
                await executeScript(scripts[index], currentEnd);
            } else {
                inlineGroup.push(scripts[index]);
            }
        }
        executeInlineGroup(inlineGroup);
    }

    async function activateEmbeddedScripts(container) {
        var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        for (var index = 0; index < scripts.length; index += 1) {
            await executeScript(scripts[index], scripts[index]);
            scripts[index].remove();
        }
    }

    function updateSidebar(nextDocument) {
        var nextSidebar = nextDocument.querySelector('.app-sidebar');
        var currentSidebar = document.querySelector('.app-sidebar');
        if (!nextSidebar || !currentSidebar) return;

        var state = {};
        Array.prototype.forEach.call(nextSidebar.querySelectorAll('a[href]'), function (link) {
            state[link.getAttribute('href')] = {
                link: link.className,
                item: link.closest('li') ? link.closest('li').className : ''
            };
        });
        Array.prototype.forEach.call(currentSidebar.querySelectorAll('a[href]'), function (link) {
            var nextState = state[link.getAttribute('href')];
            if (!nextState) return;
            link.className = nextState.link;
            var item = link.closest('li');
            if (item) item.className = nextState.item;
        });
    }

    function sidebarStructureChanged(nextDocument) {
        var nextSidebar = nextDocument.querySelector('.app-sidebar');
        var currentSidebar = document.querySelector('.app-sidebar');
        if (!nextSidebar && !currentSidebar) return false;
        // Entering or leaving a sidebar-less "focus mode" page (e.g. theme editor) also
        // needs a full reload, since only the sidebar's own links are otherwise re-synced.
        if (!nextSidebar || !currentSidebar) return true;

        function signature(sidebar) {
            return Array.prototype.map.call(sidebar.querySelectorAll('a[href]'), function (link) {
                return link.getAttribute('href');
            }).join('|');
        }

        return signature(currentSidebar) !== signature(nextSidebar);
    }

    function showFlash(nextDocument) {
        var flash = nextDocument.getElementById('cms-ajax-flash');
        if (!flash || !window.toastr) return;
        if (flash.dataset.success) window.toastr.success(flash.dataset.success, 'Success');
        if (flash.dataset.error) window.toastr.error(flash.dataset.error, 'Error');
        if (flash.dataset.warning) window.toastr.warning(flash.dataset.warning, 'Warning');
    }

    function destroyCurrentDataTables() {
        if (!window.jQuery || !window.jQuery.fn.dataTable) return;
        window.jQuery('.data-table').each(function () {
            if (!window.jQuery.fn.dataTable.isDataTable(this)) return;
            var instance = window.jQuery(this).DataTable();
            var settings = instance.settings()[0];
            if (settings && settings.jqXHR && typeof settings.jqXHR.abort === 'function') {
                settings.jqXHR.abort();
            }
            instance.destroy();
        });
    }

    async function renderHtml(html, finalUrl, pushState) {
        var nextDocument = new DOMParser().parseFromString(html, 'text/html');
        var nextMain = nextDocument.querySelector('.app-main__outer');
        var currentMain = document.querySelector('.app-main__outer');

        if (!nextMain || !currentMain || nextDocument.querySelector('.auth-shell')) {
            window.location.assign(finalUrl);
            return;
        }

        // Portfolio manager dan CMS hotel memiliki struktur navigasi berbeda.
        // Reload penuh diperlukan agar sidebar, header, dan konfigurasi hotel
        // semuanya mengikuti context yang baru dipilih.
        if (sidebarStructureChanged(nextDocument)) {
            window.location.assign(finalUrl);
            return;
        }

        document.dispatchEvent(new CustomEvent('cms:before-page-change'));
        destroyCurrentDataTables();
        cleanupPageDocumentHandlers();
        replacePageStyles(nextDocument);
        currentMain.innerHTML = nextMain.innerHTML;
        updateSidebar(nextDocument);
        showFlash(nextDocument);
        document.title = nextDocument.title || document.title;

        var nextToken = nextDocument.querySelector('meta[name="csrf-token"]');
        var currentToken = document.querySelector('meta[name="csrf-token"]');
        if (nextToken && currentToken) currentToken.content = nextToken.content;

        await activateEmbeddedScripts(currentMain);
        await replacePageScripts(nextDocument);

        if (pushState) window.history.pushState({ cmsAjax: true }, '', finalUrl);
        window.scrollTo({ top: 0, behavior: 'auto' });
        document.dispatchEvent(new CustomEvent('cms:page-loaded', { detail: { url: finalUrl } }));
    }

    async function navigate(url, options) {
        options = options || {};
        if (isNavigating && activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        isNavigating = true;
        beginLoading();

        try {
            var response = await fetch(url, {
                method: options.method || 'GET',
                body: options.body || null,
                headers: Object.assign({
                    'Accept': 'text/html,application/xhtml+xml',
                    'X-CMS-Navigation': 'true'
                }, options.headers || {}),
                credentials: 'same-origin',
                signal: activeRequest.signal,
                redirect: 'follow'
            });

            if (response.status === 419) {
                window.location.reload();
                return;
            }

            var contentType = response.headers.get('content-type') || '';
            if (contentType.indexOf('application/json') !== -1) {
                var payload = await response.json();
                if (payload.redirect) {
                    await navigate(payload.redirect, { pushState: true });
                } else {
                    if (window.toastr && payload.message) window.toastr.success(payload.message);
                    await navigate(window.location.href, { pushState: false });
                }
                return;
            }

            if (contentType.indexOf('text/html') === -1 && contentType.indexOf('application/xhtml+xml') === -1) {
                window.location.assign(response.url || url);
                return;
            }

            await renderHtml(await response.text(), response.url || url, options.pushState !== false);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.console.error('CMS AJAX navigation failed, falling back to full page load:', error);
                window.location.assign(url);
                return;
            }
        } finally {
            isNavigating = false;
            activeRequest = null;
            finishLoading();
        }
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var link = event.target.closest('a[href]');
        if (!link || link.dataset.noAjax !== undefined || link.target || link.hasAttribute('download')) return;
        if (link.hasAttribute('data-toggle') || link.getAttribute('role') === 'tab') return;

        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        var url = new URL(link.href, window.location.href);
        if (!sameOrigin(url) || url.pathname.indexOf('/logout') !== -1) return;
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return;

        event.preventDefault();
        navigate(url.href, { pushState: true });
    });

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('.app-main__outer')) return;
        if (form.dataset.noAjax !== undefined || form.id === 'uploadForm' || form.target) return;

        event.preventDefault();
        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        var url = new URL(form.action || window.location.href, window.location.href);
        var formData = new FormData(form);
        if (event.submitter && event.submitter.name) {
            formData.set(event.submitter.name, event.submitter.value);
        }

        if (method === 'GET') {
            url.search = new URLSearchParams(formData).toString();
            navigate(url.href, { pushState: true });
            return;
        }

        navigate(url.href, { method: method, body: formData, pushState: true });
    });

    window.addEventListener('popstate', function () {
        navigate(window.location.href, { pushState: false });
    });

    window.CmsAjax = { navigate: navigate };
})(window, document);
