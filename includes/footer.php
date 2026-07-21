<footer class="footer">
  <div class="container">
    <div>
      <a href="<?php echo BASE_URL; ?>/index.php" class="logo"><i class="fa-solid fa-ticket"></i> EventPro</a>
      <p style="margin-top:14px; max-width:340px; font-size:0.9rem;">Discover and book the best events near you — concerts, conferences, meetups and more, all in one premium platform.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
      <a href="<?php echo BASE_URL; ?>/events.php">All Events</a>
      <a href="<?php echo BASE_URL; ?>/register.php">Sign Up</a>
    </div>
    <div>
      <h4>Company</h4>
      <a href="#">About Us</a>
      <a href="#">Contact</a>
      <a href="#">Careers</a>
    </div>
    <div>
      <h4>Newsletter</h4>
      <p style="font-size:0.9rem; margin-bottom:14px;">Get updates on new and trending events.</p>
      <form onsubmit="event.preventDefault(); Swal.fire({icon:'success', title:'Subscribed!', text:'You will now receive event updates.'});">
        <div style="display:flex; gap:8px;">
          <input type="email" required placeholder="Your email" class="form-control" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.15); color:#fff;">
          <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
      </form>
    </div>
  </div>
  <div class="bottom">&copy; <?php echo date('Y'); ?> EventPro. All rights reserved.</div>
</footer>

<button id="backToTop"><i class="fa-solid fa-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
