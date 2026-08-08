/* ================================================================
   Chatbot AI — SiA Bot (SMA Negeri 4 Palopo)
   Riwayat chat disimpan di localStorage, bertahan saat pindah halaman,
   dan hanya dihapus ketika logout.
   ================================================================ */
(function () {
    'use strict';

    var API_URL = '/siakad/auth/chatbot-api.php';
    var STORAGE_PREFIX = 'siaChatbotHistory';

    // Riwayat percakapan (format OpenAI/Grok: role user/assistant)
    var history = [];
    var isBusy  = false;

    // Elemen DOM
    var body, toggle, panel, messages, input, form, typing, scrollAnchor, chips;

    /* ── Penyimpanan riwayat per user ── */
    function storageKey() {
        var user = (typeof window.SIA_CHAT_USER !== 'undefined' && window.SIA_CHAT_USER)
            ? window.SIA_CHAT_USER : 'guest';
        return STORAGE_PREFIX + '_' + user;
    }

    function saveHistory() {
        try {
            localStorage.setItem(storageKey(), JSON.stringify(history));
        } catch (e) { /* storage penuh/tidak tersedia */ }
    }

    function loadHistory() {
        try {
            var raw = localStorage.getItem(storageKey());
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function clearHistory() {
        try {
            localStorage.removeItem(storageKey());
        } catch (e) { /* noop */ }
    }

    /* ── Rendering ── */
    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /* Render teks: ubah **teks** -> <strong>, dan baris baru -> <br> */
    function render(text) {
        return esc(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function addBubble(from, text, animate) {
        var row = document.createElement('div');
        row.className = 'chat-row ' + (from === 'bot' ? 'bot' : 'user');

        var bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        if (from === 'bot') {
            bubble.innerHTML = '<div class="chat-avatar"><i class="bi bi-robot"></i></div>'
                + '<div class="chat-text">' + (animate ? '' : render(text)) + '</div>';
        } else {
            bubble.innerHTML = '<div class="chat-text">' + render(text) + '</div>';
        }
        row.appendChild(bubble);
        messages.appendChild(row);

        if (animate && from === 'bot') {
            typeText(bubble.querySelector('.chat-text'), text);
        }
        scrollDown();
    }

    /* Efek mengetik huruf demi huruf */
    function typeText(el, text) {
        var i = 0;
        el.textContent = '';
        var fast = text.length > 300;
        var step = fast ? 8 : 2;
        (function loop() {
            if (i < text.length) {
                el.textContent = text.slice(0, i += step);
                scrollDown();
                setTimeout(loop, 6);
            } else {
                el.innerHTML = render(text);
                scrollDown();
            }
        })();
    }

    /* Muat ulang seluruh pesan dari riwayat tersimpan */
    function renderHistory() {
        messages.innerHTML = '';
        if (history.length === 0) {
            addBubble('bot',
                'Halo! Saya SiA Bot, asisten virtual SIA SMA Negeri 4 Palopo. '
                + 'Ada yang bisa saya bantu?',
                false);
            showChips(true);
            return;
        }
        showChips(false);
        history.forEach(function (item) {
            var t = (item.content || '').toString();
            if (t) addBubble(item.role === 'user' ? 'user' : 'bot', t, false);
        });
        scrollDown();
    }

    /* Tampilkan / sembunyikan saran pertanyaan cepat */
    function showChips(show) {
        if (!chips) return;
        chips.style.display = show ? '' : 'none';
    }

    function showTyping() {
        typing.style.display = 'block';
        scrollDown();
    }

    function hideTyping() {
        typing.style.display = 'none';
    }

    function scrollDown() {
        if (messages && messages.scrollHeight > messages.clientHeight) {
            messages.scrollTop = messages.scrollHeight;
        }
        if (scrollAnchor) {
            scrollAnchor.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function setBusy(b) {
        isBusy = b;
        input.disabled = b;
        var btn = form.querySelector('.chat-send-btn');
        if (btn) btn.disabled = b;
    }

    function sendMessage(text) {
        if (isBusy) return;
        text = (text || '').trim();
        if (!text) return;

        addBubble('user', text);
        history.push({ role: 'user', content: text });
        saveHistory();

        setBusy(true);
        showTyping();

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ messages: history })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                hideTyping();
                var reply = data.reply || 'Maaf, saya tidak dapat menjawab saat ini.';
                history.push({ role: 'assistant', content: reply });
                addBubble('bot', reply, true);
                saveHistory();
            })
            .catch(function () {
                hideTyping();
                var reply = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
                history.push({ role: 'assistant', content: reply });
                addBubble('bot', reply, true);
                saveHistory();
            })
            .then(function () {
                setBusy(false);
            });
    }

    function togglePanel() {
        var open = panel.classList.toggle('open');
        toggle.classList.toggle('open');
        if (open) input.focus();
    }

    function init() {
        body         = document.body;
        toggle       = document.getElementById('chatbotToggle');
        panel        = document.getElementById('chatbotPanel');
        messages     = document.getElementById('chatbotMessages');
        form         = document.getElementById('chatbotForm');
        input        = document.getElementById('chatbotInput');
        typing       = document.getElementById('chatbotTyping');
        scrollAnchor = document.getElementById('chatbotAnchor');
        chips        = document.getElementById('chatbotQuickChips');

        if (!toggle || !panel || !messages || !form || !input) return;

        // Pulihkan riwayat chat dari localStorage
        history = loadHistory();
        renderHistory();

        toggle.addEventListener('click', togglePanel);

        // Klik chip saran → langsung kirim pertanyaan
        if (chips) {
            Array.prototype.forEach.call(chips.querySelectorAll('.chat-chip'), function (chip) {
                chip.addEventListener('click', function () {
                    var q = chip.getAttribute('data-q');
                    if (q && !isBusy) {
                        showChips(false);
                        sendMessage(q);
                        input.value = '';
                    }
                });
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!isBusy) {
                sendMessage(input.value);
                input.value = '';
            }
        });
    }

    // Ekspor fungsi untuk dipakai logout.php (membersihkan riwayat)
    window.clearChatbotHistory = clearHistory;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();