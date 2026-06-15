// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

// Admin settings-page enhancer for local_fastpix.
//
// Replaces the inline <script> blocks that previously lived in settings.php
// (forbidden by the Moodle coding style). One init() entry point, driven by a
// config object assembled in PHP and passed through js_call_amd, runs three
// progressive enhancements — all no-ops when their target elements are absent
// or JS is disabled, so the page still renders and saves through Moodle's
// default widgets:
//   1. AJAX action buttons (Test connection / Send test event).
//   2. Copy-to-clipboard for the read-only webhook URL.
//   3. The "card" restyle: section cards, masked credential inputs, pill
//      toggles, custom selects, and the save-first info popup.

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

/**
 * Set the status chip's class and text, clearing any previous state.
 *
 * @param {HTMLElement} status The status element.
 * @param {String} cls One of fp-status-busy|fp-status-ok|fp-status-err, or ''.
 * @param {String} text The text to display.
 */
const setState = (status, cls, text) => {
    status.classList.remove('fp-status-busy', 'fp-status-ok', 'fp-status-err');
    if (cls) {
        status.classList.add(cls);
    }
    status.textContent = text;
};

/**
 * Wire an AJAX action button to its web service, rendering the outcome into a
 * status chip. Strings (which carry {$a}) are resolved in JS so they are never
 * hard-coded here.
 *
 * @param {Object} b Button config: buttonId, statusId, methodname, successField,
 *                   runningKey, successKey, failedKey.
 */
const bindButton = (b) => {
    const btn = document.getElementById(b.buttonId);
    const status = document.getElementById(b.statusId);
    if (!btn || !status || btn.dataset.fpBound === '1') {
        return;
    }
    btn.dataset.fpBound = '1';

    btn.addEventListener('click', () => {
        getStrings([
            {key: b.runningKey, component: 'local_fastpix'},
            {key: b.successKey, component: 'local_fastpix'},
            {key: b.failedKey, component: 'local_fastpix'},
        ]).then(([running, successTpl, failedTpl]) => {
            setState(status, 'fp-status-busy', running);
            btn.disabled = true;
            return Ajax.call([{methodname: b.methodname, args: {}}])[0]
                .then((data) => {
                    btn.disabled = false;
                    if (data?.success) {
                        setState(status, 'fp-status-ok', '✓ ' + successTpl.replace('{$a}', String(data[b.successField])));
                    } else {
                        const msg = data?.error || data?.errors?.join(', ') || data?.result || 'unknown';
                        setState(status, 'fp-status-err', '✕ ' + failedTpl.replace('{$a}', msg));
                    }
                    return data;
                })
                .catch((err) => {
                    btn.disabled = false;
                    setState(status, 'fp-status-err', '✕ ' + failedTpl.replace('{$a}', err?.message || 'unknown'));
                });
        }).catch(() => {
            btn.disabled = false;
        });
    });
};

/**
 * Copy text to the clipboard, with a textarea+execCommand fallback for older
 * browsers and non-secure contexts.
 *
 * @param {String} text The text to copy.
 * @param {Function} done Called once the copy succeeds.
 */
const copyText = (text, done) => {
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => {
            fallbackCopy(text, done);
        });
    } else {
        fallbackCopy(text, done);
    }
};

/**
 * Clipboard fallback using a transient textarea.
 *
 * @param {String} text The text to copy.
 * @param {Function} done Called once the copy completes.
 */
const fallbackCopy = (text, done) => {
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        done();
    } finally {
        ta.remove();
    }
};

/**
 * Wire the read-only webhook URL's Copy button, swapping its icon/label briefly
 * on success.
 *
 * @param {Object} cfg Copy config: urlId, btnId, iconCopy, iconCheck, labelCopy,
 *                     labelDone.
 */
const bindCopy = (cfg) => {
    const btn = document.getElementById(cfg.btnId);
    const urlEl = document.getElementById(cfg.urlId);
    if (!btn || !urlEl || btn.dataset.fpBound === '1') {
        return;
    }
    btn.dataset.fpBound = '1';
    btn.addEventListener('click', () => {
        const text = (urlEl.value === undefined ? urlEl.textContent : urlEl.value) || '';
        copyText(text, () => {
            btn.innerHTML = cfg.iconCheck + '<span>' + cfg.labelDone + '</span>';
            setTimeout(() => {
                btn.innerHTML = cfg.iconCopy + '<span>' + cfg.labelCopy + '</span>';
            }, 1500);
        });
    });
};

/**
 * The "card" progressive enhancement: groups each section heading and its
 * settings into a bordered card, decorates credential rows with mask/reveal/copy,
 * renders checkboxes as pill toggles and selects as custom dropdowns, and adds
 * the save-first info popup. Wrapped in try/catch so a DOM surprise degrades to
 * the plain page rather than a broken one.
 *
 * @param {Object} CFG The card config assembled in settings.php.
 */
const enhanceCards = (CFG) => {
    try {
        const page = document.getElementById('page-admin-setting-local_fastpix') || document.body;
        const form = document.getElementById('adminsettings') || page;
        const heads = Array.prototype.slice.call(form.querySelectorAll('h3.main'));
        if (!heads.length) {
            return;
        }
        const container = heads[0].parentNode;
        page.classList.add('fp-enhanced');

        // Drop the FastPix logo beside the core "FastPix" page-title heading.
        if (CFG.brandLogo && CFG.brandName) {
            const titleHeads = page.querySelectorAll('h1, h2');
            for (const th of titleHeads) {
                if (th.dataset.fpLogo === '1' || th.querySelector('svg')) {
                    continue;
                }
                if ((th.textContent || '').trim().startsWith(CFG.brandName)) {
                    th.dataset.fpLogo = '1';
                    th.classList.add('fp-titled');
                    const logo = document.createElement('span');
                    logo.className = 'fp-title-logo';
                    logo.innerHTML = CFG.brandLogo;
                    th.insertBefore(logo, th.firstChild);
                    break;
                }
            }
        }

        // Clickable "i" beside a label -> popup. Used on the Test connection
        // and Send test event rows to warn that those buttons act on SAVED
        // settings, not unsaved field values.
        const fpAttachInfo = (host) => {
            if (!host || host.querySelector('.fp-info-wrap')) {
                return;
            }
            const info = document.createElement('span');
            info.className = 'fp-info-wrap';
            const ibtn = document.createElement('button');
            ibtn.type = 'button';
            ibtn.className = 'fp-info-btn';
            ibtn.setAttribute('aria-label', CFG.infoLabel || 'More information');
            ibtn.setAttribute('aria-expanded', 'false');
            ibtn.innerHTML = CFG.infoIcon;
            const pop = document.createElement('span');
            pop.className = 'fp-info-pop';
            pop.setAttribute('role', 'tooltip');
            pop.hidden = true;
            pop.innerHTML = CFG.saveNotice;
            info.appendChild(ibtn);
            info.appendChild(pop);
            host.appendChild(info);

            const hide = () => {
                pop.hidden = true;
                ibtn.setAttribute('aria-expanded', 'false');
                document.removeEventListener('mousedown', outside, true);
            };
            const outside = (e) => {
                if (!info.contains(e.target)) {
                    hide();
                }
            };
            ibtn.addEventListener('click', () => {
                if (pop.hidden) {
                    pop.hidden = false;
                    ibtn.setAttribute('aria-expanded', 'true');
                    document.addEventListener('mousedown', outside, true);
                } else {
                    hide();
                }
            });
            ibtn.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !pop.hidden) {
                    hide();
                    ibtn.focus();
                }
            });
        };
        if (CFG.saveNotice && CFG.infoIcon) {
            (CFG.infoRows || []).forEach((id) => {
                const btn = document.getElementById(id);
                const item = btn?.closest('.form-item');
                if (!item) {
                    return;
                }
                const lbl = item.querySelector('.form-label label') || item.querySelector('.form-label');
                if (lbl) {
                    fpAttachInfo(lbl);
                }
            });
        }

        const iconByTitle = {};
        CFG.sections.forEach((s) => {
            iconByTitle[(s.title || '').trim()] = s.icon;
        });
        const skip = {};
        (CFG.skipIds || []).forEach((id) => {
            skip[id] = 1;
        });

        // Snapshot the ordered children before we start moving nodes.
        const nodes = Array.prototype.slice.call(container.children);
        let body = null;
        nodes.forEach((node) => {
            if (node.matches?.('h3.main')) {
                const card = document.createElement('section');
                card.className = 'fp-card';
                const header = document.createElement('div');
                header.className = 'fp-card-h';
                const icon = document.createElement('span');
                icon.className = 'fp-card-icon';
                icon.innerHTML = iconByTitle[(node.textContent || '').trim()] || '';
                const htext = document.createElement('div');
                htext.className = 'fp-card-htext';
                header.appendChild(icon);
                header.appendChild(htext);
                body = document.createElement('div');
                body.className = 'fp-card-body';
                card.appendChild(header);
                card.appendChild(body);
                node.before(card);
                htext.appendChild(node);
                return;
            }
            if (node.classList?.contains('formsettingheading')) {
                // Description belongs in the header of the card just built.
                const card2 = container.querySelector('.fp-card:last-of-type .fp-card-htext');
                if (card2) {
                    card2.appendChild(node);
                }
                return;
            }
            if (body && node.classList?.contains('form-item')) {
                if (skip[node.id]) {
                    node.style.display = 'none';
                    return;
                }
                body.appendChild(node);
            }
        });

        // Masked credential inputs with reveal (eye) + copy.
        (CFG.secrets || []).forEach((id) => {
            const inp = document.getElementById(id);
            if (!inp || inp.dataset.fpDecorated === '1') {
                return;
            }
            inp.dataset.fpDecorated = '1';
            inp.classList.add('fp-masked');
            const wrap = document.createElement('div');
            wrap.className = 'fp-input-wrap';
            inp.parentNode.insertBefore(wrap, inp);
            wrap.appendChild(inp);

            const reveal = document.createElement('button');
            reveal.type = 'button';
            reveal.className = 'fp-ibtn';
            reveal.innerHTML = CFG.icons.eye;
            reveal.addEventListener('click', () => {
                const masked = inp.classList.toggle('fp-masked');
                reveal.innerHTML = masked ? CFG.icons.eye : CFG.icons.eyeoff;
            });

            const copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'fp-ibtn';
            copy.innerHTML = CFG.icons.copy + '<span>' + CFG.labels.copy + '</span>';
            copy.addEventListener('click', () => {
                copyText(inp.value || '', () => {
                    copy.innerHTML = CFG.icons.check + '<span>' + CFG.labels.copied + '</span>';
                    setTimeout(() => {
                        copy.innerHTML = CFG.icons.copy + '<span>' + CFG.labels.copy + '</span>';
                    }, 1400);
                });
            });

            wrap.appendChild(reveal);
            wrap.appendChild(copy);
        });

        // Pill toggles replacing each native checkbox. The native input stays
        // in the DOM (visually hidden) as the form's source of truth, so
        // Moodle's save path is unchanged.
        (CFG.toggles || []).forEach((id) => {
            const cb = document.getElementById(id);
            if (!cb || cb.dataset.fpToggle === '1') {
                return;
            }
            cb.dataset.fpToggle = '1';
            cb.classList.add('fp-native-hidden');
            cb.setAttribute('tabindex', '-1');
            cb.setAttribute('aria-hidden', 'true');

            const wrap = document.createElement('span');
            wrap.className = 'fp-toggle-wrap';
            cb.parentNode.insertBefore(wrap, cb);
            wrap.appendChild(cb);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fp-toggle';
            btn.setAttribute('role', 'switch');
            const lbl = document.createElement('span');
            lbl.className = 'fp-toggle-label';

            // Dependent field to reveal only while the toggle is on.
            const revealId = CFG.reveals?.[id];
            const revealItem = revealId ? document.getElementById(revealId) : null;

            const sync = () => {
                const on = cb.checked;
                btn.classList.toggle('is-on', on);
                btn.setAttribute('aria-checked', on ? 'true' : 'false');
                lbl.textContent = on ? CFG.labels.toggleOn : CFG.labels.toggleOff;
                if (revealItem) {
                    revealItem.style.display = on ? '' : 'none';
                }
            };
            // Drive the native checkbox with a real click so Moodle's hide_if
            // dependency manager fires and shows/hides the dependent DRM
            // Configuration ID field. (Setting .checked + a synthetic 'change'
            // does NOT trigger that logic.)
            btn.addEventListener('click', () => {
                cb.click();
                sync();
            });
            // Keep the toggle in sync if anything else flips the checkbox.
            cb.addEventListener('change', sync);

            wrap.appendChild(btn);
            wrap.appendChild(lbl);
            sync();

            // Rewrite the native "Default: No/Yes" hint to the Enabled/Disabled
            // wording shown beside the toggle.
            const item = btn.closest?.('.form-item');
            if (item) {
                const di = item.querySelector('.form-defaultinfo');
                if (di) {
                    di.textContent = di.textContent
                        .replace(new RegExp(String.raw`\b` + CFG.labels.defaultNo + String.raw`\b`), CFG.labels.toggleOff)
                        .replace(new RegExp(String.raw`\b` + CFG.labels.defaultYes + String.raw`\b`), CFG.labels.toggleOn);
                    // Drop it onto its own line below the toggle (Moodle's
                    // defaultsnext layout renders it inline next to the input).
                    di.style.display = 'block';
                }
            }
        });

        // Custom accessible dropdown replacing each native <select>. The native
        // select stays in the DOM (visually hidden) and remains the source of
        // truth, so Moodle's form save is unchanged.
        const enhanceSelect = (sel) => {
            if (sel.dataset.fpSelect === '1') {
                return;
            }
            sel.dataset.fpSelect = '1';
            const wrap = document.createElement('div');
            wrap.className = 'fp-select-wrap';
            sel.parentNode.insertBefore(wrap, sel);
            wrap.appendChild(sel);
            sel.classList.add('fp-native-hidden');
            sel.setAttribute('tabindex', '-1');
            sel.setAttribute('aria-hidden', 'true');

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fp-select';
            btn.setAttribute('aria-haspopup', 'listbox');
            btn.setAttribute('aria-expanded', 'false');
            const label = document.createElement('span');
            label.className = 'fp-select-label';
            const chev = document.createElement('span');
            chev.className = 'fp-select-chev';
            chev.innerHTML = CFG.icons.chevron;
            btn.appendChild(label);
            btn.appendChild(chev);

            const menu = document.createElement('ul');
            menu.className = 'fp-select-menu';
            menu.setAttribute('role', 'listbox');
            menu.hidden = true;

            const items = [];
            let active = -1;
            Array.prototype.forEach.call(sel.options, (opt, i) => {
                const li = document.createElement('li');
                li.className = 'fp-select-opt';
                li.setAttribute('role', 'option');
                const check = document.createElement('span');
                check.className = 'fp-select-check';
                check.innerHTML = CFG.icons.check;
                const txt = document.createElement('span');
                txt.textContent = opt.text;
                li.appendChild(check);
                li.appendChild(txt);
                li.addEventListener('click', () => {
                    choose(i);
                });
                li.addEventListener('mousemove', () => {
                    setActive(i);
                });
                menu.appendChild(li);
                items.push(li);
            });

            wrap.appendChild(btn);
            wrap.appendChild(menu);

            const sync = () => {
                const idx = sel.selectedIndex;
                label.textContent = idx >= 0 ? sel.options[idx].text : '';
                items.forEach((li, i) => {
                    const on = (i === idx);
                    li.classList.toggle('is-selected', on);
                    li.setAttribute('aria-selected', on ? 'true' : 'false');
                });
            };
            const setActive = (i) => {
                active = i;
                items.forEach((li, j) => {
                    li.classList.toggle('is-active', j === i);
                });
                if (items[i]) {
                    items[i].scrollIntoView({block: 'nearest'});
                }
            };
            // Open downward by default; flip above the button when there isn't
            // room below. Either way clamp max-height to the space actually
            // available (min 160px) so the menu's own scrollbar engages instead
            // of the list spilling off-screen.
            const position = () => {
                const GAP = 6, MARGIN = 12, MINH = 160, CAP = 320;
                const r = btn.getBoundingClientRect();
                const below = window.innerHeight - r.bottom - GAP - MARGIN;
                const above = r.top - GAP - MARGIN;
                const up = below < MINH && above > below;
                const space = Math.max(MINH, Math.min(CAP, up ? above : below));
                menu.style.maxHeight = space + 'px';
                if (up) {
                    menu.style.top = 'auto';
                    menu.style.bottom = 'calc(100% + ' + GAP + 'px)';
                } else {
                    menu.style.bottom = 'auto';
                    menu.style.top = 'calc(100% + ' + GAP + 'px)';
                }
            };
            const outside = (e) => {
                if (!wrap.contains(e.target)) {
                    close();
                }
            };
            const open = () => {
                menu.hidden = false;
                wrap.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                position();
                setActive(Math.max(0, sel.selectedIndex));
                document.addEventListener('mousedown', outside, true);
                window.addEventListener('resize', position);
                window.addEventListener('scroll', position, true);
            };
            const close = () => {
                menu.hidden = true;
                wrap.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
                document.removeEventListener('mousedown', outside, true);
                window.removeEventListener('resize', position);
                window.removeEventListener('scroll', position, true);
            };
            const choose = (i) => {
                if (i < 0 || i >= sel.options.length) {
                    return;
                }
                sel.selectedIndex = i;
                sel.dispatchEvent(new Event('change', {bubbles: true}));
                sync();
                close();
                btn.focus();
            };

            btn.addEventListener('click', () => {
                if (menu.hidden) {
                    open();
                } else {
                    close();
                }
            });
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (menu.hidden) {
                        open();
                        return;
                    }
                    if (e.key === 'ArrowDown') {
                        setActive(Math.min(items.length - 1, active + 1));
                    } else if (e.key === 'ArrowUp') {
                        setActive(Math.max(0, active - 1));
                    } else {
                        choose(active);
                    }
                } else if (e.key === 'Escape') {
                    if (!menu.hidden) {
                        e.preventDefault();
                        close();
                        btn.focus();
                    }
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    if (!menu.hidden) {
                        setActive(0);
                    }
                } else if (e.key === 'End') {
                    e.preventDefault();
                    if (!menu.hidden) {
                        setActive(items.length - 1);
                    }
                }
            });

            sync();
        };
        const selform = document.getElementById('adminsettings') || page;
        Array.prototype.forEach.call(selform.querySelectorAll('.fp-card-body select'), enhanceSelect);
    } catch (e) {
        if (window.console?.warn) {
            window.console.warn('local_fastpix cards:', e);
        }
    }
};

/**
 * Entry point. Runs each enhancement that its config block requests, after the
 * DOM is ready.
 *
 * @param {Object} config buttons[], copy{}, cards{} — any may be omitted.
 */
export const init = (config) => {
    const run = () => {
        (config.buttons || []).forEach(bindButton);
        if (config.copy) {
            bindCopy(config.copy);
        }
        if (config.cards) {
            enhanceCards(config.cards);
        }
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
};
