<?php

namespace App\Controller;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QrCodeController extends AbstractController
{
    #[Route('/qr', name: 'app_qr_code', methods: ['GET'])]
    public function qr(Request $request): Response
    {
        $data = (string) $request->query->get('data', '');

        if ($data === '') {
            return new Response('Missing "data" query parameter', 400, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $qrCode = new QrCode($data);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response(
            $result->getString(),
            200,
            ['Content-Type' => 'image/png']
        );
    }
}


