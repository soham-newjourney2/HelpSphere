<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request=$_POST['request'];
    $docname = trim($_POST['docname']);
    $beneficiary_id = $_SESSION['beneficiary_id']; // assuming you store logged-in user ID

    $stmt=$pdo->prepare("select * from assistance_requests where description=? and beneficiary_id=?");
    $stmt->bindParam(1,$request);
    $stmt->bindParam(2, $_SESSION['beneficiary_id']);
    $stmt->execute();
    if($stmt->rowCount()>0) 
    {
        $result=$stmt->fetch(PDO::FETCH_ASSOC);
        $request_id=isset($result['id'])?$result['id']:0;
        if($request_id<=0)
        {
            echo "Request Does Not Exist! ";
            exit;
        }
    }
    

    // File validation
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileSize = $_FILES['document']['size'];
        $fileType = $_FILES['document']['type'];

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            die("❌ Invalid file type. Only PDF, JPG, PNG allowed.");
        }

        if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            die("❌ File too large. Max 5MB allowed.");
        }

        // Save file to uploads directory
        $uploadDir = "../uploads/documents/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newFileName = uniqid() . "." . $fileExtension;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Insert into DB
            $stmt = $pdo->prepare("INSERT INTO beneficiary_documents (beneficiary_id, request_id, document_name, file_path, uploaded_at, status) VALUES (?, ?, ?, ?, NOW(), ?)");
            // $stmt->bind_param("iss", $beneficiary_id, $docname, $newFileName);
            $stmt->bindParam(1,$_SESSION['beneficiary_id']);
            $stmt->bindParam(2,$request_id);
            $stmt->bindParam(3,$docname);
            $stmt->bindParam(4,$newFileName);
            $stmt->bindValue(5,"Uploaded");
            if ($stmt->execute()) {
                header("Location: beneficiary-documents.php?success=1");
                exit;
            } else {
                die("Database error: ");
            }
        } else {
            die("❌ Error moving uploaded file.");
        }
    } else {
        die("❌ No file uploaded");
    }
}
?>