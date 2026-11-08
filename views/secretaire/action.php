<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
    exit;
}

$action = $data['action'] ?? '';
$id = isset($data['id']) ? (int)$data['id'] : 0;

// ==========================================
// MODULE ÉTUDIANTS
// ==========================================
if ($action === 'supprimer_etudiant' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
        $stmt->execute([$id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($etudiant) {
            $del = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Étudiant supprimé avec succès.', 'data' => $etudiant]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Étudiant introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_etudiant') {
    $e = $data['data'] ?? null;
    if ($e) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO etudiants (id, matricule, nom, prenom, date_naissance, sexe, telephone, adresse, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $e['id'], $e['matricule'], $e['nom'], $e['prenom'], 
                $e['date_naissance'], $e['sexe'], $e['telephone'], $e['adresse'], $e['classe_id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Étudiant restauré avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}

// ==========================================
// MODULE UTILISATEURS / COMPTES
// ==========================================
if ($action === 'supprimer_utilisateur' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès.', 'data' => $user]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_utilisateur') {
    $u = $data['data'] ?? null;
    if ($u) {
        try {
            // REPLACE INTO pour réinsérer l'utilisateur avec son ID et son mot de passe haché d'origine
            $stmt = $pdo->prepare("REPLACE INTO users (id, email, password, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $u['id'], $u['email'], $u['password'], $u['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur restauré avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration du compte.']);
            exit;
        }
    }
}

// ==========================================
// MODULE FILIÈRES
// ==========================================
if ($action === 'supprimer_filiere' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM filieres WHERE id = ?");
        $stmt->execute([$id]);
        $filiere = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($filiere) {
            $del = $pdo->prepare("DELETE FROM filieres WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Filière supprimée avec succès.', 'data' => $filiere]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Filière introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_filiere') {
    $f = $data['data'] ?? null;
    if ($f) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO filieres (id, code_filiere, nom, responsable, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $f['id'], $f['code_filiere'], $f['nom'], $f['responsable'], $f['description']
            ]);
            echo json_encode(['success' => true, 'message' => 'Filière restaurée avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}

// ==========================================
// MODULE NIVEAUX
// ==========================================
if ($action === 'supprimer_niveau' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM niveaux WHERE id = ?");
        $stmt->execute([$id]);
        $niveau = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($niveau) {
            $del = $pdo->prepare("DELETE FROM niveaux WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Niveau supprimé avec succès.', 'data' => $niveau]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Niveau introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_niveau') {
    $n = $data['data'] ?? null;
    if ($n) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO niveaux (id, code_niveau, libelle, filiere_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $n['id'], $n['code_niveau'], $n['libelle'], $n['filiere_id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Niveau restauré avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration du niveau.']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);