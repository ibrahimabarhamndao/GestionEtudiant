<?php
require_once '../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Optionnel : si vous voulez une vraie restauration, vous pouvez 
        // marquer un champ is_deleted = 1 au lieu d'un DELETE direct.
        // Pour l'exemple avec DELETE, on redirige avec l'ID pour le restaurer si cliqué :
        $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
        $stmt->execute([$id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant) {
            // Suppression effective
            $del = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
            $del->execute([$id]);

            // Redirection avec un paramètre spécial contenant l'ID au cas où l'utilisateur annule
            header("Location: etudiants.php?tab=etudiants&success=delete&restorable_id=" . $id);
            exit();
        }
    } catch (PDOException $e) {
        header("Location: etudiants.php?tab=etudiants&error=delete");
        exit();
    }
}
header("Location: etudiants.php?tab=etudiants");
exit();