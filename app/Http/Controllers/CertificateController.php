<?php

namespace App\Http\Controllers;

use App\Models\CourseCertificate;
use App\Services\CourseCertificateService;
use Illuminate\Http\Request;

/** Páginas públicas del certificado: verlo/imprimirlo y comprobar que es auténtico. */
class CertificateController extends Controller
{
    public function show(string $code)
    {
        $cert = CourseCertificate::where('code', CourseCertificateService::normalizeCode($code))->first();

        return response()->view('certificates.show', ['cert' => $cert, 'code' => $code], $cert ? 200 : 404)->header('X-Robots-Tag', 'noindex');
    }

    public function verify(Request $request)
    {
        $code = CourseCertificateService::normalizeCode($request->query('codigo', $request->input('codigo')));

        if ($code !== '') {
            return redirect()->route('certificates.show', ['code' => $code]);
        }

        return response()->view('certificates.verify')->header('X-Robots-Tag', 'noindex');
    }
}
