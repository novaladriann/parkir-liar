<?php

class Laporan
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data)
    {
        $query = "INSERT INTO laporan 
                    (id_user, judul, deskripsi, foto, latitude, longitude, alamat, status)
                  VALUES 
                    (:id_user, :judul, :deskripsi, :foto, :latitude, :longitude, :alamat, :status)";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_user' => $data['id_user'],
            ':judul' => $data['judul'],
            ':deskripsi' => $data['deskripsi'],
            ':foto' => $data['foto'],
            ':latitude' => $data['latitude'],
            ':longitude' => $data['longitude'],
            ':alamat' => $data['alamat'],
            ':status' => $data['status'] ?? 'menunggu'
        ]);

        return $this->pdo->lastInsertId();
    }

    public function findByUser($idUser)
    {
        $query = "SELECT * FROM laporan 
                  WHERE id_user = :id_user 
                  ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_user' => $idUser
        ]);

        return $stmt->fetchAll();
    }

    public function findByIdAndUser($idLaporan, $idUser)
    {
        $query = "SELECT * FROM laporan 
                  WHERE id_laporan = :id_laporan 
                  AND id_user = :id_user 
                  LIMIT 1";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_laporan' => $idLaporan,
            ':id_user' => $idUser
        ]);

        return $stmt->fetch();
    }

    public function findAllWithUser($status = null)
    {
        if ($status) {
            $query = "SELECT laporan.*, users.nama, users.email
                  FROM laporan
                  JOIN users ON users.id_user = laporan.id_user
                  WHERE laporan.status = :status
                  ORDER BY laporan.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':status' => $status
            ]);

            return $stmt->fetchAll();
        }

        $query = "SELECT laporan.*, users.nama, users.email
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              ORDER BY laporan.created_at DESC";

        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    public function findByIdWithUser($idLaporan)
    {
        $query = "SELECT laporan.*, users.nama, users.email
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              WHERE laporan.id_laporan = :id_laporan
              LIMIT 1";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_laporan' => $idLaporan
        ]);

        return $stmt->fetch();
    }

    public function updateStatus($idLaporan, $status, $catatanAdmin = null)
    {
        $query = "UPDATE laporan
              SET status = :status,
                  catatan_admin = :catatan_admin
              WHERE id_laporan = :id_laporan";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':status' => $status,
            ':catatan_admin' => $catatanAdmin,
            ':id_laporan' => $idLaporan
        ]);
    }

    public function findForMap($status = null)
    {
        if ($status) {
            $query = "SELECT laporan.id_laporan, laporan.judul, laporan.deskripsi, laporan.foto,
                         laporan.latitude, laporan.longitude, laporan.alamat, laporan.status,
                         laporan.created_at, users.nama, users.email
                  FROM laporan
                  JOIN users ON users.id_user = laporan.id_user
                  WHERE laporan.status = :status
                  ORDER BY laporan.created_at DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':status' => $status
            ]);

            return $stmt->fetchAll();
        }

        $query = "SELECT laporan.id_laporan, laporan.judul, laporan.deskripsi, laporan.foto,
                     laporan.latitude, laporan.longitude, laporan.alamat, laporan.status,
                     laporan.created_at, users.nama, users.email
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              ORDER BY laporan.created_at DESC";

        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    public function countByStatus()
    {
        $query = "SELECT status, COUNT(*) AS total
              FROM laporan
              GROUP BY status";

        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    public function countByMonth()
    {
        $query = "SELECT 
                  DATE_FORMAT(created_at, '%Y-%m') AS bulan,
                  COUNT(*) AS total
              FROM laporan
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY bulan ASC";

        $stmt = $this->pdo->query($query);

        return $stmt->fetchAll();
    }

    public function latestReports($limit = 5)
    {
        $query = "SELECT laporan.*, users.nama, users.email
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              ORDER BY laporan.created_at DESC
              LIMIT :limit";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function buildAdminReportWhere(array $filters, array &$params)
    {
        $conditions = [];

        if (!empty($filters['status'])) {
            $conditions[] = "laporan.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';

            $conditions[] = "(
            laporan.judul LIKE :keyword_judul
            OR laporan.deskripsi LIKE :keyword_deskripsi
            OR laporan.alamat LIKE :keyword_alamat
            OR users.nama LIKE :keyword_nama
            OR users.email LIKE :keyword_email
        )";

            $params[':keyword_judul'] = $keyword;
            $params[':keyword_deskripsi'] = $keyword;
            $params[':keyword_alamat'] = $keyword;
            $params[':keyword_nama'] = $keyword;
            $params[':keyword_email'] = $keyword;
        }

        if (!empty($filters['tanggal_mulai'])) {
            $conditions[] = "laporan.created_at >= :tanggal_mulai";
            $params[':tanggal_mulai'] = $filters['tanggal_mulai'] . ' 00:00:00';
        }

        if (!empty($filters['tanggal_selesai'])) {
            $conditions[] = "laporan.created_at <= :tanggal_selesai";
            $params[':tanggal_selesai'] = $filters['tanggal_selesai'] . ' 23:59:59';
        }

        if (count($conditions) === 0) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    public function searchAdminReports(array $filters = [], $limit = 10, $offset = 0)
    {
        $params = [];
        $where = $this->buildAdminReportWhere($filters, $params);

        $query = "SELECT laporan.*, users.nama, users.email
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              $where
              ORDER BY laporan.created_at DESC
              LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAdminReports(array $filters = [])
    {
        $params = [];
        $where = $this->buildAdminReportWhere($filters, $params);

        $query = "SELECT COUNT(*) AS total
              FROM laporan
              JOIN users ON users.id_user = laporan.id_user
              $where";

        $stmt = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return (int) $stmt->fetch()['total'];
    }

    public function addStatusLog($idLaporan, $statusSebelum, $statusSesudah, $catatan = null, $changedBy = null)
    {
        $query = "INSERT INTO laporan_status_logs 
                (id_laporan, status_sebelum, status_sesudah, catatan, changed_by)
              VALUES 
                (:id_laporan, :status_sebelum, :status_sesudah, :catatan, :changed_by)";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':id_laporan' => $idLaporan,
            ':status_sebelum' => $statusSebelum,
            ':status_sesudah' => $statusSesudah,
            ':catatan' => $catatan,
            ':changed_by' => $changedBy
        ]);
    }

    public function getStatusLogs($idLaporan)
    {
        $query = "SELECT laporan_status_logs.*, users.nama, users.role
              FROM laporan_status_logs
              LEFT JOIN users ON users.id_user = laporan_status_logs.changed_by
              WHERE laporan_status_logs.id_laporan = :id_laporan
              ORDER BY laporan_status_logs.created_at ASC";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_laporan' => $idLaporan
        ]);

        return $stmt->fetchAll();
    }
}