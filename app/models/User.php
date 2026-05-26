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
    public function findByEmailExceptId($email, $idUser)
    {
        $query = "SELECT * FROM users 
              WHERE email = :email 
              AND id_user != :id_user 
              LIMIT 1";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':email' => $email,
            ':id_user' => $idUser
        ]);

        return $stmt->fetch();
    }

    public function updateProfile($idUser, $nama, $email)
    {
        $query = "UPDATE users 
              SET nama = :nama,
                  email = :email
              WHERE id_user = :id_user";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':id_user' => $idUser
        ]);
    }

    public function updatePassword($idUser, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $query = "UPDATE users 
              SET password = :password
              WHERE id_user = :id_user";

        $stmt = $this->pdo->prepare($query);

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id_user' => $idUser
        ]);
    }
}