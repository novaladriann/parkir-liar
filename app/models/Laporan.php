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
}