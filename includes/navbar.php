<header>
    <nav>
        <!-- KIRI: logo + menu -->
        <div class="nav-left">
            <div class="logo">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="Logo-Hotel">
                </a>
            </div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="hotels.php">Hotel</a></li>
                <li><a href="hotels.php">Rooms</a></li>
                <?php if (isset($_SESSION['id_user'])): ?>
                    <li><a href="my_bookings.php">History</a></li>
                <?php else: ?>
                    <li><a href="login.php">History</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- KANAN: login / register -->
        <div class="nav-auth">
            <?php if (isset($_SESSION['id_user'])): ?>
                <a href="profile.php" class="nav-user">
                    <?php echo htmlspecialchars($_SESSION['nama']); ?>
                </a>
                <a href="logout.php" class="Masuk">Logout</a>
            <?php else: ?>
                <a href="login.php" class="Masuk">Login</a>
                <a href="register.php" class="Daftar">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>