<?php require_once __DIR__ . '/layouts/header.php'; ?>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-6">
                <h1 class="fw-bold mb-3">
                    Sistem Pelaporan Parkir Liar
                </h1>

                <p class="text-muted mb-4">
                    Laporkan area parkir ilegal secara cepat dengan bukti foto,
                    lokasi GPS otomatis, serta pantau perkembangan laporan secara langsung.
                </p>

                <a href="<?= url('register.php'); ?>" class="btn btn-primary btn-lg me-2">
                    Buat Laporan
                </a>

                <a href="<?= url('login.php'); ?>" class="btn btn-outline-primary btn-lg">
                    Login
                </a>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Fitur Utama</h5>

                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Upload foto kendaraan atau lokasi</li>
                            <li class="list-group-item">GPS otomatis untuk titik pelanggaran</li>
                            <li class="list-group-item">Monitoring progress laporan</li>
                            <li class="list-group-item">Peta pelanggaran parkir liar</li>
                            <li class="list-group-item">Grafik statistik pelaporan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>