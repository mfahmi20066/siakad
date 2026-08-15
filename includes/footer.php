<!-- jQuery (harus LOAD PERTAMA agar DataTables & Bootstrap bisa kerja) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap JS Bundle (includes Popper) - setelah jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables Buttons (Export Excel) -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <!-- Alert System (modern modal + toast) -->
    <script src="/siakad/assets/js/alert.js?v=1.0"></script>

    <!-- Custom JS -->
    <script src="/siakad/assets/js/main.js?v=8"></script>

    <!-- ===== Chatbot AI (SiA Bot) ===== -->
    <?php if (!empty($show_chatbot)): ?>
    <link rel="stylesheet" href="/siakad/assets/css/chatbot.css?v=4">
    <button type="button" class="chatbot-fab" id="chatbotToggle" aria-label="Buka chatbot">
        <i class="bi bi-chat-dots-fill fab-open"></i>
        <i class="bi bi-x-lg fab-close"></i>
    </button>

    <div class="chatbot-panel" id="chatbotPanel" role="dialog" aria-label="Chatbot SiA Bot">
        <div class="chatbot-header">
            <div class="bot-avatar"><i class="bi bi-robot"></i></div>
            <div class="bot-info">
                <h6>SiA Bot</h6>
                <small>Asisten Virtual SMA Negeri 4 Palopo</small>
            </div>
        </div>

        <div class="chat-quick-chips" id="chatbotQuickChips">
            <span class="chat-chip" data-q="Bagaimana cara daftar SPMB?">Daftar SPMB</span>
            <span class="chat-chip" data-q="Bagaimana cara cek status SPMB?">Cek status SPMB</span>
            <span class="chat-chip" data-q="Berapa jam belajar dimulai?">Jam belajar</span>
            <span class="chat-chip" data-q="Bagaimana cara melihat rapor?">Lihat rapor</span>
            <span class="chat-chip" data-q="Siapa kepala sekolah sekarang?">Kepala sekolah</span>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div id="chatbotAnchor"></div>
        </div>

        <div class="chat-typing" id="chatbotTyping">
            <div class="chat-bubble">
                <div class="chat-avatar"><i class="bi bi-robot"></i></div>
                <div class="chat-text">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>

        <form class="chatbot-form" id="chatbotForm" autocomplete="off">
            <input type="text" class="chatbot-input" id="chatbotInput"
                   placeholder="Ketik pertanyaanmu..." maxlength="2000">
            <button type="submit" class="chat-send-btn" aria-label="Kirim">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>

    <script>window.SIA_CHAT_USER = <?= json_encode($_SESSION['username'] ?? 'guest') ?>;</script>
    <script src="/siakad/assets/js/chatbot.js"></script>
    <?php endif; ?>

    <script>
        // badge pesan belum dibaca, polling ringan di semua halaman
        (function () {
            var URL = '/siakad/chat/api.php?action=unread';
            function updateBadge(n) {
                var b = document.getElementById('chatTopBadge');
                if (!b) return;
                if (n > 0) {
                    b.style.display = 'inline-flex';
                    b.textContent = n > 99 ? '99+' : String(n);
                } else {
                    b.style.display = 'none';
                }
            }
            function poll() {
                fetch(URL).then(function (r) { return r.json(); }).then(function (j) {
                    if (j && j.ok) updateBadge(j.data.total);
                }).catch(function () {});
            }
            setTimeout(poll, 2000);
            setInterval(poll, 30000);
        })();
    </script>

</body>
</html>