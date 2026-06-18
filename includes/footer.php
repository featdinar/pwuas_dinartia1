    </main>
  </div> <!-- Close app-container -->

  <!-- Footer -->
  <footer class="footer-container">
    <div class="footer-content">
      <div class="footer-brand">
        <h4>
          <svg style="width: 18px; height: 18px; fill: var(--color-primary);" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2v20M2 12h20M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          </svg>
          Unsent
        </h4>
        <p style="max-width: 300px; margin-top: 10px; line-height: 1.6;">
          Menghubungkan kata-kata yang terlewatkan dengan lagu yang mewakili perasaan yang tak terucapkan.
        </p>
      </div>
      <div class="footer-links">
        <h5>Navigasi</h5>
        <ul>
          <li><a href="index.php">Beranda</a></li>
          <li><a href="search.php">Cari Penerima</a></li>
          <li><a href="premium.php">Layanan Premium</a></li>
        </ul>
      </div>
      <div class="footer-links">
        <h5>Akun</h5>
        <ul>
          <?php if (isLoggedIn()): ?>
            <li><a href="dashboard_user.php">Dashboard</a></li>
            <li><a href="profile.php">Pengaturan Profil</a></li>
            <li><a href="logout.php">Keluar</a></li>
          <?php else: ?>
            <li><a href="login.php">Masuk Akun</a></li>
            <li><a href="register.php">Daftar Baru</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Unsent. Hak Cipta Dilindungi.</p>
      <p style="font-size: 12px; color: var(--color-on-dark-soft);">
        Designed in compliance with the Claude warm-canvas aesthetic system.
      </p>
    </div>
  </footer>

  <!-- Simple client-side scripts for premium interactive widgets -->
  <script>
    // Music Player Simulation
    document.addEventListener("DOMContentLoaded", function() {
      const playBtn = document.getElementById("simulate-play-btn");
      const playerContainer = document.getElementById("music-player");
      const vinylDisc = document.getElementById("vinyl");
      const playStatusText = document.getElementById("play-status");
      
      let audio = null;
      let isPlaying = false;
      
      if (playBtn) {
        // Create a basic web audio API synth or dummy oscillator for audio feedback!
        let audioCtx = null;
        let oscillator = null;
        let gainNode = null;
        
        playBtn.addEventListener("click", function() {
          if (!isPlaying) {
            // Start playing simulation
            isPlaying = true;
            playerContainer.classList.add("playing");
            playBtn.innerHTML = "⏸";
            playStatusText.textContent = "Memutar lagu...";
            
            // Web Audio Synth to play a peaceful ambient melody
            try {
              audioCtx = new (window.AudioContext || window.webkitAudioContext)();
              
              // We'll play a recurring soft chord/note
              function playNote(freq, start, duration) {
                if (!isPlaying) return;
                let osc = audioCtx.createOscillator();
                let gain = audioCtx.createGain();
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, start);
                
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.08, start + 0.1);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + duration - 0.1);
                
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                osc.start(start);
                osc.stop(start + duration);
              }
              
              let now = audioCtx.currentTime;
              // Play a soft arpeggio (C major 7th or F major)
              playNote(261.63, now, 2); // C4
              playNote(329.63, now + 0.5, 2); // E4
              playNote(392.00, now + 1.0, 2); // G4
              playNote(493.88, now + 1.5, 2); // B4
              
              // Set interval to repeat
              oscillator = setInterval(() => {
                if (!isPlaying || !audioCtx) return;
                let t = audioCtx.currentTime;
                playNote(261.63, t, 2);
                playNote(329.63, t + 0.5, 2);
                playNote(392.00, t + 1.0, 2);
                playNote(493.88, t + 1.5, 2);
              }, 3000);
              
            } catch (e) {
              console.log("Audio API not supported or user interaction required first.");
            }
            
          } else {
            // Stop playing simulation
            isPlaying = false;
            playerContainer.classList.remove("playing");
            playBtn.innerHTML = "▶";
            playStatusText.textContent = "Diputar melalui tautan";
            
            if (oscillator) {
              clearInterval(oscillator);
            }
            if (audioCtx) {
              audioCtx.close();
            }
          }
        });
      }
    });
  </script>
</body>
</html>
