<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Cek apakah user adalah admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID film tidak valid']);
        exit;
    }
    
    // Ambil data film dulu (untuk hapus file poster)
    $sqlGet = "SELECT poster FROM movies WHERE id = ?";
    $stmtGet = $conn->prepare($sqlGet);
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $resultGet = $stmtGet->get_result();
    $movie = $resultGet->fetch_assoc();
    
    // Ambil semua foto aktor
    $sqlCast = "SELECT actor_photo FROM cast_members WHERE movie_id = ?";
    $stmtCast = $conn->prepare($sqlCast);
    $stmtCast->bind_param("i", $id);
    $stmtCast->execute();
    $resultCast = $stmtCast->get_result();
    
    // ✅ HAPUS FILE POSTER dari folder uploads/
    if ($movie && !empty($movie['poster'])) {
        $posterPath = '../../' . $movie['poster'];
        if (file_exists($posterPath)) {
            unlink($posterPath);
            error_log("Deleted poster: {$posterPath}");
        }
    }
    
    // ✅ HAPUS FILE FOTO AKTOR dari folder uploads/
    while ($cast = $resultCast->fetch_assoc()) {
        if (!empty($cast['actor_photo'])) {
            $actorPhotoPath = '../../' . $cast['actor_photo'];
            if (file_exists($actorPhotoPath)) {
                unlink($actorPhotoPath);
                error_log("Deleted actor photo: {$actorPhotoPath}");
            }
        }
    }
    
    // Hapus cast members dulu (foreign key constraint)
    $sqlDeleteCast = "DELETE FROM cast_members WHERE movie_id = ?";
    $stmtDeleteCast = $conn->prepare($sqlDeleteCast);
    $stmtDeleteCast->bind_param("i", $id);
    $stmtDeleteCast->execute();
    
    // Hapus film dari database
    $sql = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Film dan semua file terkait berhasil dihapus'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus film: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>