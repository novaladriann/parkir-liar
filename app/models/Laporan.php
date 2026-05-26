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
}