<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perpustakaan Kolika</title>
  <link rel="icon" href="asset/logo.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <nav id="sidebar">
    <ul>
      <li>
        <span class="logo">Kolika</span>
        <button onclick="toggleSidebar()" id="toggle-btn">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z"/></svg>
        </button>
      </li>
      <li class="active">
        <a href="index.php">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg>
          <span>Home</span>
        </a>
      </li>
      <li>
        <a href="dashboard.php">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg>
          <span>dashboard</span>
        </a>
      </li>
      </li>
      <li>
        <a href="tambah.php">
          <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Z"/></svg>
          <span>Tambah data</span>
        </a>
      </li>
    </ul>
  </nav>
  <main>
    <div>
      <h1>Halo selamat datang di panel admin perpustakaan SMKN 1 Katapang</h1>
      <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Suscipit ratione id illo, at, pariatur vitae nesciunt, cum eos temporibus minima cumque ab asperiores. Porro officiis fugiat tempora dignissimos. Officia, ut.</p>
      <div class="container">
      <div class="page-header">
        <h1>Daftar Buku</h1>
      </div>
  </main>
  <script src="main.js">
  </script>
</body>
</html>