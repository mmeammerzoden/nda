<?php
require_once('vendor/tecnickcom/tcpdf/tcpdf.php');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['name']) || !isset($data['email']) || !isset($data['company']) || !isset($data['signature'])) {
    echo json_encode(['status' => 'error', 'message' => 'Ongeldige invoer']);
    exit;
}

$name = htmlspecialchars($data['name']);
$email = htmlspecialchars($data['email']);
$company = htmlspecialchars($data['company']);
$signatureData = $data['signature'];

// **Stap 1: Base64 handtekening opslaan als afbeelding**
$signaturePath = 'signatures/signature_' . time() . '.png';
$signatureData = explode(',', $signatureData)[1]; // Base64-decoderen
file_put_contents($signaturePath, base64_decode($signatureData));

// **Stap 2: NDA-tekst verwerken**
$ndaText = "This Non-Disclosure Agreement is made as of " . date('F j, Y') . " between MME and $company.\n\n[Hier komt de NDA-tekst]\n\nIN WITNESS WHEREOF:\n\nMME Houdstermaatschappij B.V.:\n\nSignature: ___________________________\n\nName: Bert Rademakers  \nTitle: CEO  \n\n$company:\n\nSignature: ___________________________\n\nName: $name  \nTitle: ___________________________";

// **Stap 3: PDF genereren**
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 10, $ndaText, 0, 'L');

// **Handtekening invoegen**
$pdf->Image($signaturePath, 40, 220, 50, 20, 'PNG');

// PDF opslaan
$pdfFilePath = 'nda_signed_' . time() . '.pdf';
$pdf->Output($pdfFilePath, 'F');

// **Stap 4: E-mail verzenden**
$toCompany = "jouwemail@bedrijf.com";
$subject = "Nieuwe NDA Acceptatie van $name";
$message = "Nieuwe NDA geaccepteerd door:\n\nNaam: $name\nBedrijf: $company\nE-mail: $email\n\nDe ondertekende NDA is bijgevoegd.";

$headers = "From: noreply@jouwbedrijf.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"boundary123\"\r\n";

$emailBody = "--boundary123\r\nContent-Type: application/pdf; name=\"$pdfFilePath\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$pdfFilePath\"\r\n\r\n" . chunk_split(base64_encode(file_get_contents($pdfFilePath))) . "\r\n--boundary123--";

mail($toCompany, $subject, $emailBody, $headers);
mail($email, "Bevestiging NDA", "Beste $name, NDA bijgevoegd.", $headers);

// **Bestanden verwijderen**
unlink($pdfFilePath);
unlink($signaturePath);

echo json_encode(['status' => 'success', 'message' => 'NDA verzonden!']);
?>
