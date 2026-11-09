<?php
require_once '../../config/db.php';

// Récupération des données JSON envoyées par JavaScript ou POST classique
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_POST['action'] ?? '';
$id = $data['id'] ?? $_POST['id'] ?? 0;

header('Content-Type: application/json');

// ==========================================
// MODULE ETUDIANTS
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
    $eData = $data['data'] ?? null;
    if ($eData) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO etudiants (id, matricule, nom, prenom, email, telephone, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $eData['id'], $eData['matricule'], $eData['nom'], $eData['prenom'], 
                $eData['email'], $eData['telephone'], $eData['classe_id']
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
// MODULE CLASSES
// ==========================================
if ($action === 'supprimer_classe' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($classe) {
            $del = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Classe supprimée avec succès.', 'data' => $classe]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Classe introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_classe') {
    $c = $data['data'] ?? null;
    if ($c) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO classes (id, code_classe, nom, capacite, filiere_id, niveau_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $c['id'], $c['code_classe'], $c['nom'], $c['capacite'], $c['filiere_id'], $c['niveau_id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Classe restaurée avec succès.']);
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
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}

// ==========================================
// MODULE FILIERES
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
            $stmt = $pdo->prepare("REPLACE INTO filieres (id, code_filiere, nom) VALUES (?, ?, ?)");
            $stmt->execute([
                $f['id'], $f['code_filiere'], $f['nom']
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
// MODULE NOTES
// ==========================================
if ($action === 'supprimer_note' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($note) {
            $del = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Note supprimée avec succès.', 'data' => $note]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Note introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_note') {
    $noteData = $data['data'] ?? null;
    if ($noteData) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO notes (id, etudiant_id, matiere, type, valeur) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $noteData['id'], $noteData['etudiant_id'], $noteData['matiere'], 
                $noteData['type'], $noteData['valeur']
            ]);
            echo json_encode(['success' => true, 'message' => 'Note restaurée avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}
// ==========================================
// MODULE DOCUMENTS
// ==========================================
if ($action === 'supprimer_document' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            $del = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Document supprimé avec succès.', 'data' => $doc]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Document introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_document') {
    $dData = $data['data'] ?? null;
    if ($dData) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO documents (id, nom_document, categorie, type_fichier, taille, chemin_fichier, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $dData['id'], $dData['nom_document'], $dData['categorie'], 
                $dData['type_fichier'], $dData['taille'], $dData['chemin_fichier'], $dData['created_at']
            ]);
            echo json_encode(['success' => true, 'message' => 'Document restauré avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}

// ==========================================
// MODULE NOTIFICATIONS
// ==========================================
if ($action === 'supprimer_notification' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($notif) {
            $del = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Notification supprimée avec succès.', 'data' => $notif]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Notification introuvable.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
        exit;
    }
}

if ($action === 'restaurer_notification') {
    $n = $data['data'] ?? null;
    if ($n) {
        try {
            $stmt = $pdo->prepare("REPLACE INTO notifications (id, titre, description, type, lu, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $n['id'], $n['titre'], $n['description'], $n['type'], $n['lu'], $n['created_at']
            ]);
            echo json_encode(['success' => true, 'message' => 'Notification restaurée avec succès.']);
            exit;
        } catch (PDOException $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la restauration.']);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
exit;