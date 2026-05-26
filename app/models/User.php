<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($nama, $email, $password, $role = 'masyarakat')
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (nama, email, password, role)
                  VALUES (:nama, :email, :password, :role)";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => $role
        ]);

        return $this->pdo->lastInsertId();
    }

    public function findByEmail($email)
    {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch();
    }

    public function findById($idUser)
    {
        $query = "SELECT * FROM users WHERE id_user = :id_user LIMIT 1";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':id_user' => $idUser
        ]);

        return $stmt->fetch();
    }
}