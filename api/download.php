<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();

$rhythmId = (int)($_GET['rhythm_id'] ?? 0);

if ($rhythmId && !user_can_access_rhythm($db, $rhythmId)) {
    flash('download_msg', 'You don\'t have access to that rhythm.');
    header('Location: ../library');
    exit;
}

if ($rhythmId) {
    // Sheet music lives in the database as a BLOB (not on local disk), so
    // downloads work the same way whether this is running on a traditional
    // server or a serverless host with an ephemeral filesystem.
    $stmt = $db->prepare('SELECT title, sheet_data, sheet_mime, sheet_original_name FROM rhythms WHERE id = :id');
    $stmt->execute([':id' => $rhythmId]);
    $rhythm = $stmt->fetch();

    if ($rhythm && $rhythm['sheet_data'] !== null) {
        // Log the download
        $db->prepare('UPDATE rhythms SET play_count = play_count + 1 WHERE id = :id')->execute([':id' => $rhythmId]);

        $downloadName = $rhythm['sheet_original_name'] ?: 'sheet-music';
        $mime = $rhythm['sheet_mime'] ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
        header('Content-Length: ' . strlen($rhythm['sheet_data']));
        header('Cache-Control: no-cache, must-revalidate');
        echo $rhythm['sheet_data'];
        exit;
    }

    flash('download_msg', $rhythm ? 'No sheet music has been uploaded for "' . $rhythm['title'] . '" yet.' : 'That rhythm could not be found.');
    header('Location: ../library#rhythm-' . $rhythmId);
    exit;
}

flash('download_msg', 'That rhythm could not be found.');
header('Location: ../library');
exit;
