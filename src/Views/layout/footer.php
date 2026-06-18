        </div><!-- /page-body -->
    </main><!-- /main -->
</div><!-- /app-shell -->

<script>
// Initialize Lucide Icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Clock
function tickClock() {
    const el = document.getElementById('topbar-clock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
}
tickClock();
setInterval(tickClock, 1000);
</script>
</body>
</html>
