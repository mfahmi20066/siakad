/* ================================================================
   CHAT INTERNAL (OPENCODE.md 21-42)
   Rendering pesan TANPA innerHTML (textContent) — aman XSS.
   ================================================================ */
(function () {
    'use strict';

    var API = '/siakad/chat/api.php';
    var MINE_UID = window.SIA_CHAT_UID || 0;
    var state = { conv: null, loading: false, sending: false };
    var pollListTimer = null;
    var pollMsgTimer = null;

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function qs(sel) { return document.querySelector(sel); }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function fetchJson(url, opts) {
        return fetch(url, opts).then(function (res) {
            return res.json().catch(function () { return { ok: false, error: 'Respon tidak valid' }; });
        });
    }

    function post(body) {
        var data = new URLSearchParams();
        data.append('csrf_token', csrfToken());
        Object.keys(body || {}).forEach(function (k) { data.append(k, body[k]); });
        return fetchJson(API, { method: 'POST', body: data });
    }

    /* ---------- format & avatar ---------- */

    function formatWaktu(isoDb) {
        if (!isoDb) return '';
        var d = new Date(isoDb.replace(' ', 'T'));
        if (isNaN(d.getTime())) return isoDb;
        var now = new Date();
        var hm = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        var sameDay = d.toDateString() === now.toDateString();
        if (sameDay) return hm;
        var yest = new Date(now); yest.setDate(now.getDate() - 1);
        if (d.toDateString() === yest.toDateString()) return 'Kemarin';
        return String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
    }

    function inisial(nama) {
        var parts = String(nama || '').split(' ').filter(Boolean);
        var a = parts[0] ? parts[0][0] : '?';
        var b = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (a + b).toUpperCase();
    }

    function avatar(nama, cls) {
        var box = el('div', 'chat-avatar ' + (cls || ''));
        box.textContent = inisial(nama);
        return box;
    }

    function roleLabel(role) {
        var map = { admin: 'Admin', guru: 'Guru', siswa: 'Siswa' };
        return map[role] || role;
    }

    /* ---------- badge unread global ---------- */

    function updateBadges(total) {
        ['chatTopBadge', 'chatFabBadge'].forEach(function (id) {
            var b = document.getElementById(id);
            if (!b) return;
            if (total > 0) {
                b.style.display = 'inline-flex';
                b.textContent = total > 99 ? '99+' : String(total);
            } else {
                b.style.display = 'none';
            }
        });
    }

    function pollUnread() {
        fetchJson(API + '?action=unread').then(function (res) {
            if (res.ok) updateBadges(res.data.total);
        }).catch(function () {});
    }

    /* ---------- daftar percakapan ---------- */

    function renderConvList(rows) {
        var list = document.getElementById('chatConvList');
        if (!list) return;
        list.textContent = '';

        if (!rows.length) {
            var e = el('div', 'chat-empty-conv');
            e.appendChild(el('i', 'fas fa-comments'));
            e.appendChild(el('span', null, 'Belum ada percakapan'));
            e.appendChild(el('small', null, 'Mulai percakapan dengan pengguna lain untuk berkomunikasi melalui SIAKAD.'));
            list.appendChild(e);
            return;
        }

        var filter = (qs('#chatConvSearch') || {}).value || '';
        var shown = 0;
        rows.forEach(function (r) {
            var hay = (r.other_nama + ' ' + r.other_username + ' ' + roleLabel(r.other_role)).toLowerCase();
            if (filter && hay.indexOf(filter.toLowerCase()) === -1) return;
            shown++;

            var item = el('div', 'conv-item' + (state.conv === r.conversation_id ? ' active' : ''));
            item.setAttribute('data-id', r.conversation_id);

            var av = avatar(r.other_nama);
            item.appendChild(av);

            var body = el('div', 'conv-body');
            var top = el('div', 'conv-top');
            top.appendChild(el('span', 'conv-name', r.other_nama));
            top.appendChild(el('span', 'conv-time', formatWaktu(r.last_time)));
            body.appendChild(top);

            var bottom = el('div', 'conv-bottom');
            var preview = el('span', 'conv-preview', (r.last_message || 'Belum ada pesan'));
            var right = el('span', 'conv-meta');
            right.appendChild(el('span', 'conv-role', roleLabel(r.other_role)));
            if (r.unread > 0) {
                right.appendChild(el('span', 'conv-unread', r.unread > 99 ? '99+' : String(r.unread)));
            }
            bottom.appendChild(preview);
            bottom.appendChild(right);
            body.appendChild(bottom);

            item.appendChild(body);
            item.addEventListener('click', function () { openConversation(r.conversation_id); });
            list.appendChild(item);
        });

        if (shown === 0) {
            var ne = el('div', 'chat-empty-conv');
            ne.appendChild(el('i', 'fas fa-search'));
            ne.appendChild(el('span', null, 'Tidak ada percakapan yang cocok'));
            list.appendChild(ne);
        }
    }

    function loadConvList() {
        fetchJson(API + '?action=conv_list').then(function (res) {
            if (res.ok) {
                renderConvList(res.data);
                var total = res.data.reduce(function (s, r) { return s + r.unread; }, 0);
                updateBadges(total);
            }
        }).catch(function () {});
    }

    /* ---------- format & avatar ---------- */

    function formatTanggalPisah(isoDb) {
        if (!isoDb) return '';
        var d = new Date(isoDb.replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        var now = new Date();
        var yest = new Date(now); yest.setDate(now.getDate() - 1);
        if (d.toDateString() === now.toDateString()) return 'Hari ini';
        if (d.toDateString() === yest.toDateString()) return 'Kemarin';
        return String(d.getDate()).padStart(2, '0') + ' ' +
               ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][d.getMonth()] +
               ' ' + d.getFullYear();
    }

    function tanggalKey(isoDb) {
        var d = new Date(isoDb.replace(' ', 'T'));
        return isNaN(d.getTime()) ? '' : d.toDateString();
    }

    function renderDateSep(label) {
        var sep = el('div', 'chat-date-sep');
        var line = el('span', 'chat-date-sep-text', label);
        sep.appendChild(line);
        return sep;
    }

    function renderOneMessage(m) {
        var mine = (MINE_UID && Number(m.sender_id) === MINE_UID);
        var deleted = m.deleted_at && m.deleted_at !== null && m.deleted_at !== '0000-00-00 00:00:00';

        var row = el('div', 'msg-row ' + (mine ? 'mine' : 'theirs'));
        row.setAttribute('data-id', m.id);

        if (deleted) {
            row.classList.add('msg-deleted-row');
            var del = el('div', 'msg-deleted');
            del.textContent = mine ? 'Pesan ini dihapus' : 'Pesan dihapus';
            row.appendChild(del);
            return row;
        }

        var bubble = el('div', 'msg-bubble');
        bubble.textContent = m.message;

        var meta = el('div', 'msg-meta');
        meta.appendChild(el('span', 'msg-time', formatWaktu(m.created_at)));
        if (mine && m.read_at) {
            meta.appendChild(el('span', 'msg-status', 'Dibaca'));
        }
        bubble.appendChild(meta);

        // Tombol hapus untuk pesan milik sendiri (belum dihapus).
        // Diletakkan sebagai sibling bubble (di gutter kiri bubble milik sendiri)
        // agar tidak menimpa teks pesan.
        if (mine) {
            var delBtn = el('button', 'msg-del-btn');
            delBtn.type = 'button';
            delBtn.setAttribute('title', 'Hapus pesan');
            delBtn.setAttribute('aria-label', 'Hapus pesan');
            delBtn.setAttribute('data-id', m.id);
            delBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
            delBtn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                hapusPesan(m.id, row);
            });
            row.appendChild(delBtn);
        }

        row.appendChild(bubble);
        return row;
    }

    function renderMessages(rows, opts) {
        opts = opts || {};
        var box = document.getElementById('chatMessages');
        if (!box) return;

        if (!rows.length) {
            if (!opts.prepend) {
                var e = el('div', 'chat-empty');
                e.appendChild(el('i', 'fas fa-comment-dots'));
                e.appendChild(el('h6', null, 'Belum ada pesan'));
                e.appendChild(el('p', null, 'Kirim pesan pertama untuk memulai percakapan.'));
                box.textContent = '';
                box.appendChild(e);
            }
            return;
        }

        if (opts.prepend) {
            // Muat pesan lebih lama: sisip di atas, pertahankan separator & scroll
            var oldScroll = box.scrollHeight - box.scrollTop;
            var first = true;
            var lastKey = null;
            rows.forEach(function (m) {
                var key = tanggalKey(m.created_at);
                if (key !== lastKey) {
                    box.insertBefore(renderDateSep(formatTanggalPisah(m.created_at)), box.firstChild);
                    lastKey = key;
                }
                box.insertBefore(renderOneMessage(m), box.firstChild);
            });
            box.scrollTop = box.scrollHeight - oldScroll;
            return;
        }

        // Render penuh
        box.textContent = '';
        var lastKey = null;
        rows.forEach(function (m) {
            var key = tanggalKey(m.created_at);
            if (key !== lastKey) {
                box.appendChild(renderDateSep(formatTanggalPisah(m.created_at)));
                lastKey = key;
            }
            box.appendChild(renderOneMessage(m));
        });

        // Tombol "Muat pesan lebih lama"
        if (opts.has_more && opts.oldest_id) {
            var btn = el('button', 'chat-load-older', 'Muat pesan lebih lama…');
            btn.type = 'button';
            btn.setAttribute('data-before', opts.oldest_id);
            btn.addEventListener('click', loadOlder);
            box.appendChild(btn);
        }

        box.scrollTop = box.scrollHeight;
    }

    function renderMitra(mitra, loading) {
        var head = document.getElementById('chatMitra');
        if (!head) return;
        head.textContent = '';
        if (!mitra) {
            head.appendChild(el('span', 'chat-mitra-placeholder', loading ? 'Memuat...' : '—'));
            return;
        }
        head.appendChild(el('span', 'chat-mitra-name', mitra.nama));
        head.appendChild(el('span', 'chat-mitra-role', roleLabel(mitra.role)));
    }

    function openConversation(cid, noPollReset) {
        cid = parseInt(cid, 10);
        if (!cid) return;
        state.conv = cid;

        var app = document.getElementById('chatApp');
        if (app) app.classList.add('chat-view-detail');

        var input = document.getElementById('chatInput');
        var send = document.getElementById('chatSendBtn');
        if (input) input.disabled = false;
        if (send) send.disabled = true;

        renderMitra(null, true);

        fetchJson(API + '?action=messages&id=' + cid).then(function (res) {
            if (!res.ok) {
                renderMitra(null, false);
                if (input) input.disabled = true;
                if (send) send.disabled = true;
                return;
            }
            renderMessages(res.data.messages, {
                has_more: !!res.data.has_more,
                oldest_id: res.data.oldest_id
            });
            renderMitra(res.data.mitra, false);
            if (send) send.disabled = false;
            if (input) input.focus();

            post({ action: 'mark_read', conversation_id: cid }).then(function (rr) {
                if (rr.ok) updateBadges(rr.data.unread_total);
            });
            loadConvList();
        });

        if (!noPollReset) resetMsgPoll();
    }

    function appendSent(msg) {
        var box = document.getElementById('chatMessages');
        if (!box) return;
        var empty = box.querySelector('.chat-empty');
        if (empty) empty.remove();
        // Hapus tombol "Muat pesan lebih lama" bila ada (pesan baru di bawah)
        var loadBtn = box.querySelector('.chat-load-older');
        if (loadBtn) loadBtn.remove();

        box.appendChild(renderOneMessage(msg));
        box.scrollTop = box.scrollHeight;
    }

    function hapusPesan(mid, rowNode) {
        if (typeof window.siConfirm !== 'function') {
            // Fallback minimal bila SIAAlert belum tersedia
            if (!window.confirm('Hapus pesan ini? Pesan akan dihapus untuk kedua belah pihak.')) return;
            doHapusPesan(mid, rowNode);
            return;
        }
        window.siConfirm({
            icon: 'delete',
            title: 'Hapus pesan?',
            text: 'Pesan ini akan dihapus dan tidak dapat dikembalikan. Pesan akan hilang untuk Anda dan lawan bicara.',
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            danger: true
        }).then(function (ok) {
            if (ok) doHapusPesan(mid, rowNode);
        });
    }

    function doHapusPesan(mid, rowNode) {
        post({ action: 'delete', message_id: mid }).then(function (res) {
            if (!res.ok) {
                if (window.siError) siError(res.error || 'Gagal menghapus pesan');
                return;
            }
            // Pesan terhapus lenyap dari thread (soft-delete untuk kedua belah pihak)
            if (rowNode && rowNode.parentNode) {
                rowNode.parentNode.removeChild(rowNode);
            }
            if (window.siSuccess) siSuccess('Pesan telah dihapus');
            if (typeof loadConvList === 'function') loadConvList();
        }).catch(function () {
            if (window.siError) siError('Gagal menghapus pesan');
        });
    }

    function loadOlder() {
        if (!state.conv) return;
        var btn = document.querySelector('.chat-load-older');
        var before = btn ? parseInt(btn.getAttribute('data-before'), 10) : 0;
        if (!before) return;
        if (btn) { btn.disabled = true; btn.textContent = 'Memuat…'; }
        fetchJson(API + '?action=messages&id=' + state.conv + '&before_id=' + before).then(function (res) {
            if (!res.ok || !res.data.messages.length) {
                if (btn) btn.remove();
                return;
            }
            renderMessages(res.data.messages, { prepend: true });
            // Geser tombol ke posisi baru (terakhir di render prepend = paling bawah list lama)
            if (res.data.has_more) {
                var newBtn = el('button', 'chat-load-older', 'Muat pesan lebih lama…');
                newBtn.type = 'button';
                newBtn.setAttribute('data-before', res.data.oldest_id);
                newBtn.addEventListener('click', loadOlder);
                var box = document.getElementById('chatMessages');
                if (box) box.appendChild(newBtn);
            } else if (btn) {
                btn.remove();
            }
        }).catch(function () { if (btn) { btn.disabled = false; btn.textContent = 'Muat pesan lebih lama…'; } });
    }

    function sendMessage(e) {
        if (e) e.preventDefault();
        if (state.sending) return;

        var input = document.getElementById('chatInput');
        var msg = (input.value || '').trim();
        if (!msg) return;

        state.sending = true;
        var sendBtn = document.getElementById('chatSendBtn');
        if (sendBtn) sendBtn.disabled = true;

        var payload = { action: 'send', message: msg };
        if (state.conv) payload.conversation_id = state.conv;

        post(payload).then(function (res) {
            state.sending = false;
            if (!res.ok) {
                if (sendBtn) sendBtn.disabled = false;
                if (window.siError) siError(res.error || 'Gagal mengirim pesan');
                return;
            }
            if (input) input.value = '';
            if (sendBtn) sendBtn.disabled = false;
            appendSent(res.data.message);
            if (res.data.conversation_id && res.data.conversation_id !== state.conv) {
                state.conv = res.data.conversation_id;
            }
            if (res.data.unread_total !== undefined) updateBadges(res.data.unread_total);
            loadConvList();
        }).catch(function () {
            state.sending = false;
            if (sendBtn) sendBtn.disabled = false;
        });
    }

    /* ---------- poll ---------- */

    function resetMsgPoll() {
        if (pollMsgTimer) clearInterval(pollMsgTimer);
        pollMsgTimer = setInterval(function () {
            if (!state.conv) return;
            var visible = document.visibilityState === 'visible';
            if (!visible) return;
            fetchJson(API + '?action=messages&id=' + state.conv).then(function (res) {
                if (!res.ok || !state.conv) return;
                renderMitra(res.data.mitra, false);
                // Hanya tampilkan pesan yang ID-nya lebih besar dari yang sudah
                // dirender (jangan re-render penuh agar paginasi & scroll aman).
                var box = document.getElementById('chatMessages');
                if (!box) return;
                var existing = box.querySelectorAll('.msg-row[data-id]');
                var maxId = 0;
                existing.forEach(function (n) {
                    var id = parseInt(n.getAttribute('data-id'), 10);
                    if (id > maxId) maxId = id;
                });
                var baru = res.data.messages.filter(function (m) {
                    return parseInt(m.id, 10) > maxId;
                });
                if (baru.length) {
                    var atBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 60;
                    var lastKey = null;
                    baru.forEach(function (m) {
                        var key = tanggalKey(m.created_at);
                        if (key !== lastKey) {
                            var sep = renderDateSep(formatTanggalPisah(m.created_at));
                            box.insertBefore(sep, box.querySelector('.chat-load-older'));
                            lastKey = key;
                        }
                        box.insertBefore(renderOneMessage(m), box.querySelector('.chat-load-older'));
                    });
                    if (atBottom) box.scrollTop = box.scrollHeight;
                }
            }).catch(function () {});
        }, 5000);
    }

    function resetListPoll() {
        if (pollListTimer) clearInterval(pollListTimer);
        pollListTimer = setInterval(function () {
            if (document.visibilityState !== 'visible') return;
            loadConvList();
            pollUnread();
        }, 15000);
    }

    /* ---------- Pesan Baru (modal) ---------- */

    function renderUserList(rows) {
        var list = document.getElementById('chatUserList');
        if (!list) return;
        list.textContent = '';

        if (!rows.length) {
            var e = el('div', 'chat-empty-conv');
            e.appendChild(el('i', 'fas fa-search'));
            e.appendChild(el('span', null, 'Tidak ada pengguna yang cocok'));
            list.appendChild(e);
            return;
        }

        rows.forEach(function (u) {
            var row = el('div', 'chat-user-row');
            row.setAttribute('data-id', u.id);
            row.appendChild(avatar(u.nama));
            var body = el('div', 'conv-body');
            body.appendChild(el('span', 'conv-name', u.nama));
            body.appendChild(el('span', 'conv-role', '@' + u.username + ' · ' + roleLabel(u.role)));
            row.appendChild(body);
            row.addEventListener('click', function () {
                post({ action: 'open', target_id: u.id }).then(function (res) {
                    if (!res.ok) {
                        if (window.siError) siError(res.error || 'Tidak dapat memulai percakapan');
                        return;
                    }
                    var modal = document.getElementById('chatNewModal');
                    if (modal && window.bootstrap && bootstrap.Modal) {
                        bootstrap.Modal.getInstance(modal) ? bootstrap.Modal.getInstance(modal).hide() : new bootstrap.Modal(modal).hide();
                    } else if (modal) {
                        modal.classList.remove('show');
                        if (modal.parentNode) modal.parentNode.removeChild(modal);
                    }
                    if (res.data.unread_total !== undefined) updateBadges(res.data.unread_total);
                    openConversation(res.data.conversation_id);
                    loadConvList();
                });
            });
            list.appendChild(row);
        });
    }

    function searchUser(q) {
        var list = document.getElementById('chatUserList');
        if (!list) return;
        var qsWords = (q || '').trim();
        if (!qsWords) {
            list.textContent = '';
            var hint = el('div', 'chat-empty-conv');
            hint.appendChild(el('i', 'fas fa-search'));
            hint.appendChild(el('span', null, 'Ketik nama untuk mencari pengguna.'));
            list.appendChild(hint);
            return;
        }
        fetchJson(API + '?action=search&q=' + encodeURIComponent(qsWords)).then(function (res) {
            if (res.ok) renderUserList(res.data);
        }).catch(function () {});
    }

    /* ---------- init ---------- */

    ready(function () {
        var app = document.getElementById('chatApp');
        if (!app) return;

        // UID saya (disuntik dari PHP sebelum chat.js)
        if (!MINE_UID) {
            var script = document.getElementById('siaChatUid');
            if (script && script.getAttribute('data-uid')) MINE_UID = parseInt(script.getAttribute('data-uid'), 10);
        }

        var sendBtn = document.getElementById('chatSendBtn');
        sendBtn.addEventListener('click', function (ev) { ev.preventDefault(); sendMessage(ev); });

        var form = document.getElementById('chatForm');
        form.addEventListener('submit', sendMessage);

        var backHomeBtn = document.getElementById('chatBackHomeBtn');
        if (backHomeBtn) {
            backHomeBtn.addEventListener('click', function () {
                var role = window.SIA_CHAT_ROLE || 'admin';
                var dash = { admin: '/siakad/admin/dashboard.php', guru: '/siakad/guru/dashboard.php', siswa: '/siakad/siswa/dashboard.php' };
                window.location.href = dash[role] || '/siakad/';
            });
        }

        var backBtn = document.getElementById('chatBackBtn');
        backBtn.addEventListener('click', function () {
            state.conv = null;
            app.classList.remove('chat-view-detail');
            loadConvList();
        });

        var searchInput = document.getElementById('chatConvSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                loadConvList();
            });
        }

        var newBtn = document.getElementById('chatNewBtn');
        if (newBtn && window.bootstrap && bootstrap.Modal) {
            newBtn.addEventListener('click', function () {
                var modal = new bootstrap.Modal(document.getElementById('chatNewModal'));
                modal.show();
                searchUser('');
                var us = document.getElementById('chatUserSearch');
                if (us) {
                    setTimeout(function () { us.focus(); }, 300);
                }
            });
        }

        var userSearch = document.getElementById('chatUserSearch');
        if (userSearch) {
            var debounce = null;
            userSearch.addEventListener('input', function () {
                clearTimeout(debounce);
                var v = this.value;
                debounce = setTimeout(function () { searchUser(v); }, 250);
            });
        }

        // Deep link dari notifikasi (?conv=ID)
        var initial = parseInt(app.getAttribute('data-initial-conv') || '0', 10);

        loadConvList();
        pollUnread();
        resetListPoll();

        if (initial > 0) {
            openConversation(initial);
        }
    });
})();