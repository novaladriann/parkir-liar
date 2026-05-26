<?php

class Notification
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($idUser, $idLaporan, $judul, $pesan)
    {
        $query = "INSERT INTO notifikasi 
                    (id_user, id_laporan, judul, pesan)
                  VALUES 
                    (:id_user, :id_laporan, :judul, :pesan)";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':id_user' => $idUser,
            ':id_laporan' => $idLaporan,
            ':judul' => $judul,
            ':pesan' => $pesan
        ]);
    }

    public function findByUser($idUser)
    {
        $query = "SELECT notifikasi.*, laporan.judul AS judul_laporan
                  FROM notifikasi
                  LEFT JOIN laporan ON laporan.id_laporan = notifikasi.id_laporan
                  WHERE notifikasi.id_user = :id_user
                  ORDER BY notifikasi.created_at DESC";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_user' => $idUser
        ]);

        return $stmt->fetchAll();
    }

    public function countUnreadByUser($idUser)
    {
        $query = "SELECT COUNT(*) AS total
                  FROM notifikasi
                  WHERE id_user = :id_user
                  AND is_read = 0";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_user' => $idUser
        ]);

        return (int) $stmt->fetch()['total'];
    }

    public function markAllAsRead($idUser)
    {
        $query = "UPDATE notifikasi
                  SET is_read = 1
                  WHERE id_user = :id_user";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':id_user' => $idUser
        ]);
    }

    public function markAsRead($idNotifikasi, $idUser)
    {
        $query = "UPDATE notifikasi
                  SET is_read = 1
                  WHERE id_notifikasi = :id_notifikasi
                  AND id_user = :id_user";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':id_notifikasi' => $idNotifikasi,
            ':id_user' => $idUser
        ]);
    }
}