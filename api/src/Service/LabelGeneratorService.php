<?php
// src/Service/LabelGeneratorService.php

namespace App\Service;

use App\Entity\Shipment;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LabelGeneratorService
{
    public function generateLabel(Shipment $shipment): string
    {
        try {
            // 1) QR-Code generieren (Endroid QR-Code v3)
            $qrResult = Builder::create()
                ->data((string) $shipment->getTrackingNumber())
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->size(200)
                ->margin(10)
                ->build();
            // PNG-String
            $qrPng = $qrResult->getString();

            // 2) Barcode generieren
            $barcodeGen = new BarcodeGeneratorPNG();
            $barcodePng = $barcodeGen->getBarcode(
                (string) $shipment->getTrackingNumber(),
                $barcodeGen::TYPE_CODE_128,
                2,
                50
            );

            // 3) Label-Canvas mit GD
            $width  = 600;
            $height = 400;
            $im     = imagecreatetruecolor($width, $height);
            $white  = imagecolorallocate($im, 255, 255, 255);
            imagefilledrectangle($im, 0, 0, $width, $height, $white);

            // QR-Code einbinden
            $qrImg = imagecreatefromstring($qrPng);
            imagecopyresampled(
                $im, $qrImg,
                10, 10,
                0, 0,
                150, 150,
                imagesx($qrImg),
                imagesy($qrImg)
            );

            // Barcode einbinden
            $bcImg = imagecreatefromstring($barcodePng);
            imagecopyresampled(
                $im, $bcImg,
                200, 10,
                0, 0,
                imagesx($bcImg),
                imagesy($bcImg),
                imagesx($bcImg),
                imagesy($bcImg)
            );

            // 4) Absender-Text mit imagestring()
            $black   = imagecolorallocate($im, 0, 0, 0);
            $font    = 3; // GD-Bitmapfont (1–5)
            $yOffset = 180;
            imagestring($im, $font, 10, $yOffset,     $shipment->getSenderName1(),                      $black);
            imagestring($im, $font, 10, $yOffset + 20, $shipment->getSenderStreet(),                     $black);
            imagestring($im, $font, 10, $yOffset + 40, $shipment->getSenderPostalCode() . ' ' . $shipment->getSenderCity(), $black);

            // 5) PNG → Base64
            ob_start();
            imagepng($im);
            $pngData = ob_get_clean();

            // Ressourcen freigeben
            imagedestroy($im);
            imagedestroy($qrImg);
            imagedestroy($bcImg);

            return base64_encode($pngData);

        } catch (\Throwable $e) {
            throw new HttpException(500, 'Label-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
