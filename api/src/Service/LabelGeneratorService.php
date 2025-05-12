<?php
// src/Service/LabelGeneratorService.php

namespace App\Service;

use App\Entity\Package;
use App\Entity\Shipment;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TCPDF;

class LabelGeneratorService
{
    /** @var string Vollständiger Pfad zu DejaVuSans.ttf (Docker: fonts-dejavu-core) */
    private string $fontFile;
    /** @var string Firmen-Logo */
    private string $logoUrl = 'https://camel-24.de/wp-content/uploads/CAM_Logo_50.jpg.pagespeed.ce.4OHgPiTGng.jpg';

    public function __construct()
    {
        $this->fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        if (!is_readable($this->fontFile)) {
            throw new \RuntimeException('Font-Datei nicht lesbar: '.$this->fontFile);
        }
    }

    /* ------------------------------------------------------------------------
     *  Sammel-Label für komplette Sendung  (PNG 600×400)
     * --------------------------------------------------------------------- */
    public function generateLabel(Shipment $shipment): string
    {
        try {
            /* QR-Code */
            $qrPng = Builder::create()
                ->data((string)$shipment->getTrackingNumber())
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->size(200)
                ->margin(0)
                ->build()
                ->getString();

            /* Barcode */
            $barcodeGen = new BarcodeGeneratorPNG();
            $bcPng = $barcodeGen->getBarcode(
                (string)$shipment->getTrackingNumber(),
                $barcodeGen::TYPE_CODE_128,
                1.5, 
                30
            );

            /* Canvas */
            $w = 600; $h = 400; $m = 10;
            $im = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, 0, 0, $w, $h, $white);

            /* Box-Größen */
            $boxW = ($w - 3 * $m) / 2;
            $boxH = 150;

            /* QR links */
            $qrImg = imagecreatefromstring($qrPng);
            $dstSize = min($boxW, $boxH);
            $dstX = $m + ($boxW - $dstSize) / 2;
            $dstY = $m + ($boxH - $dstSize) / 2;
            imagecopyresampled(
                $im, $qrImg, (int)$dstX, (int)$dstY, 0, 0,
                (int)$dstSize, (int)$dstSize, imagesx($qrImg), imagesy($qrImg)
            );

            /* Barcode rechts */
            $bcImg = imagecreatefromstring($bcPng);
            $bcX   = 2 * $m + $boxW;
            $scale = min($boxW / imagesx($bcImg), $boxH / imagesy($bcImg));
            $bw    = imagesx($bcImg) * $scale;
            $bh    = imagesy($bcImg) * $scale;
            $bx    = $bcX + ($boxW - $bw) / 2;
            $by    = $m  + ($boxH - $bh) / 2;
            imagecopyresampled(
                $im, $bcImg, (int)$bx, (int)$by, 0, 0,
                (int)$bw, (int)$bh, imagesx($bcImg), imagesy($bcImg)
            );

            /* Logo */
            if (($logo = @file_get_contents($this->logoUrl)) !== false) {
                $lg = imagecreatefromstring($logo);
                $maxW = 80; $maxH = 40;
                $ls  = min($maxW / imagesx($lg), $maxH / imagesy($lg));
                $lw  = imagesx($lg) * $ls;
                $lh  = imagesy($lg) * $ls;
                $lx  = $w - $m - $lw;
                $ly  = $m;
                imagecopyresampled($im, $lg, (int)$lx, (int)$ly, 0, 0, (int)$lw, (int)$lh, imagesx($lg), imagesy($lg));
                imagedestroy($lg);
            }

            /* Absender-Block */
            $black = imagecolorallocate($im, 0, 0, 0);
            $yText = $m * 2 + $boxH;
            $font  = 3;
            imagestring($im, $font, $m, $yText,       $shipment->getSenderName1(),          $black);
            imagestring($im, $font, $m, $yText + 15, ($shipment->getSenderName2() ?: ''),   $black);
            imagestring($im, $font, $m, $yText + 30,  $shipment->getSenderStreet(),         $black);
            imagestring($im, $font, $m, $yText + 45,  $shipment->getSenderCountry().' '.$shipment->getSenderPostalCode().' '.$shipment->getSenderCity(), $black);
            imagestring($im, $font, $m, $yText + 60, 'Tel: '.$shipment->getSenderPhone(),   $black);

            /* PNG → Base64 */
            ob_start(); imagepng($im); $out = ob_get_clean();
            imagedestroy($im); imagedestroy($qrImg); imagedestroy($bcImg);
            return base64_encode($out);

        } catch (\Throwable $e) {
            throw new HttpException(500, 'Label-Generierung fehlgeschlagen: '.$e->getMessage());
        }
    }

    /* ------------------------------------------------------------------------
     *  Einzel-Package-Label 150×100 mm (Quer-PDF)
     * --------------------------------------------------------------------- */
    public function generatePackagePdf(Package $package, int $index, int $total): string
    {
        try {
            /* Grafiken */
            $qrPng = Builder::create()
                ->data((string)$package->getPackageNumber())
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->size(90)
                ->margin(0)
                ->build()
                ->getString();

            $barcodeGen = new BarcodeGeneratorPNG();
            $bcPng = $barcodeGen->getBarcode(
                (string)$package->getPackageNumber(),
                $barcodeGen::TYPE_CODE_128,
                2, 45
            );

            /* PDF */
            $pdf = new TCPDF('L', 'mm', [150, 100], true, 'UTF-8', false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(5, 5, 5);
            $pdf->AddPage();
            $pdf->SetFontSubsetting(false);             // Performance

            $W = $pdf->getPageWidth();
            $H = $pdf->getPageHeight();
            $m = 5;
            $gap = 5;

            /* Seiten-Rahmen */
            $pdf->Rect($m, $m, $W - 2*$m, $H - 2*$m);

            /* Header-Kasten-Höhe */
            $hHeader = 25;

            /* QR */
            $qrSize = 25;
            $pdf->Image('@'.$qrPng, $m+1, $m+1, $qrSize-2, $qrSize-2, 'PNG');

            /* Barcode (rechts neben QR, davor Lücke) */
            $bcX = $m + $qrSize + $gap;
            $bcW = $W - 2*$m - $qrSize - 2*$gap - 30;   // 30mm Platz fürs Logo
            $pdf->Image('@'.$bcPng, $bcX, $m+3, $bcW, $hHeader-6, 'PNG');

            /* Logo */
            $pdf->Image(
                $this->logoUrl,
                $W - $m - 30,
                $m + ($hHeader - 15)/2,
                28, 15, 'JPG'
            );

            /* Spalten-Breite */
            $colW = ($W - 2*$m - $gap) / 2;
            $yAdr = $m + $hHeader + 2;

            /* Hilfs-Closure */
            $nl = static fn(?string $v) => $v ? $v."\n" : '';

            /* Abholung */
            $s = $package->getShipment();
            $pickup = $nl($s->getSenderName1())
                    . $nl($s->getSenderName2())
                    . $nl($s->getSenderStreet())
                    . $s->getSenderCountry().' '.$s->getSenderPostalCode().' '.$s->getSenderCity()."\n"
                    . 'Telefon: '.$s->getSenderPhone()."\n"
                    . ($s->getPickupNote() ? 'Hinweis: '.$s->getPickupNote() : '');

            $pdf->SetFont('dejavusans','B',10);
            $pdf->SetXY($m,$yAdr);
            $pdf->Cell($colW,6,'ABHOLUNG',0,2);
            $pdf->SetFont('dejavusans','',9);
            $pdf->MultiCell($colW,5,$pickup,0,'L',false,1);

            /* Zustellung */
            $delivery = $nl($s->getRecipientName1())
                      . $nl($s->getRecipientName2())
                      . $nl($s->getRecipientStreet())
                      . $s->getRecipientCountry().' '.$s->getRecipientPostalCode().' '.$s->getRecipientCity()."\n"
                      . 'Telefon: '.$s->getRecipientPhone()."\n"
                      . ($s->getDeliveryNote() ? 'Hinweis: '.$s->getDeliveryNote() : '');

            $pdf->SetFont('dejavusans','B',10);
            $pdf->SetXY($m+$colW+$gap,$yAdr);
            $pdf->Cell($colW,6,'ZUSTELLUNG',0,2);
            $pdf->SetFont('dejavusans','',9);
            $pdf->MultiCell($colW,5,$delivery,0,'L',false,1);

            /* Termine (klein) */
            $pdf->SetFont('dejavusans','',8);
            $txtPickup = sprintf(
                'ABHOL: %s %s–%s',
                $s->getPickupDate()->format('d.m.Y'),
                $s->getPickupTimeFrom()->format('H:i'),
                $s->getPickupTimeTo()->format('H:i')
            );
            $txtDelivery = sprintf(
                'ZUSTELL: %s %s–%s',
                $s->getDeliveryDate()->format('d.m.Y'),
                $s->getDeliveryTimeFrom()->format('H:i'),
                $s->getDeliveryTimeTo()->format('H:i')
            );
            $pdf->SetXY($m,$H-22);
            $pdf->Cell($colW,4,$txtPickup,0,0,'L');
            $pdf->SetXY($m+$colW+$gap,$H-22);
            $pdf->Cell($colW,4,$txtDelivery,0,0,'L');

            /* Fußzeile */
            $detail = sprintf(
                'PKS-NR: %d • Stück %d/%d • %.2f kg • %d×%d×%d cm',
                $package->getPackageNumber(), $index, $total,
                $package->getWeightKg(),
                $package->getLengthCm(), $package->getWidthCm(), $package->getHeightCm()
            );
            $pdf->SetFont('dejavusans','', $package->getWeightKg() > 20 ? 7 : 9);
            $pdf->SetXY($m,$H-10);
            $pdf->Cell($W-2*$m,5,$detail,0,0,'L');

            /* HEAVY-Stempel */
            if ($package->getWeightKg() > 20) {
                $pdf->SetFont('dejavusans','B',14);
                $pdf->SetTextColor(200,0,0);
                $pdf->SetXY($W-$m-35,$H-16);
                $pdf->Cell(35,10,'HEAVY',1,0,'C');
                $pdf->SetTextColor(0,0,0);
            }

            return $pdf->Output('', 'S');

        } catch (\Throwable $e) {
            throw new HttpException(500,'PDF-Label-Generierung fehlgeschlagen: '.$e->getMessage());
        }
    }
}
