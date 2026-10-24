<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Certificado de finalización de un curso, con código público para verificarlo. */
class CourseCertificate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    /** Texto listo para publicar en redes. */
    public function shareText(): string
    {
        $site = setting('_general.site_name') ?: config('app.name');

        return "Terminé «{$this->course_title}» en {$site}. Puedes verificar mi certificado aquí:";
    }

    /** Enlace de LinkedIn para agregar el certificado a «Licencias y certificaciones» del perfil (con su enlace de verificación). */
    public function linkedinProfileUrl(): string
    {
        $issued = $this->issued_at ?? now();

        return 'https://www.linkedin.com/profile/add?' . http_build_query([
            'startTask' => 'CERTIFICATION_NAME',
            'name' => mb_substr(html_entity_decode($this->course_title), 0, 120),
            'organizationName' => setting('_general.site_name') ?: config('app.name'),
            'issueYear' => $issued->year,
            'issueMonth' => $issued->month,
            'certUrl' => $this->url(),
            'certId' => $this->code,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Publicar en LinkedIn (aparece con la vista previa del certificado). */
    public function linkedinShareUrl(): string
    {
        return 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query(['url' => $this->url()], '', '&', PHP_QUERY_RFC3986);
    }

    public function whatsappShareUrl(): string
    {
        return 'https://wa.me/?' . http_build_query(['text' => $this->shareText() . ' ' . $this->url()], '', '&', PHP_QUERY_RFC3986);
    }

    public function url(): string
    {
        return route('certificates.show', ['code' => $this->code]);
    }
}
