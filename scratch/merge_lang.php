<?php

$en = require __DIR__ . '/../lang/en/admin/general.php';
$es = require __DIR__ . '/../lang/es/admin/general.php';

$merged = array_merge($en, $es); // ES overrides EN, but missing EN keys are kept.

// For the specific missing keys the user saw on the dashboard, let's translate them directly in code:
$missing_translations = [
    'revenue_payment_metrics' => 'Métricas de Ingresos y Pagos',
    'track_manage_income' => 'Rastrear y gestionar ingresos',
    'platform_earnings' => 'Ganancias de la Plataforma',
    'tutor_payouts' => 'Pagos a Tutores',
    'platform_commission' => 'Comisión de la Plataforma',
    'pending_payouts' => 'Pagos Pendientes',
    'session_engagement' => 'Participación en Sesiones',
    'total_sessions_booked' => 'Total de Sesiones Reservadas',
    'completed_sessions' => 'Sesiones Completadas',
    'rescheduled_sessions' => 'Sesiones Reprogramadas',
    'user_metrics_activity' => 'Métricas y Actividad de Usuarios',
    'total_users' => 'Usuarios Totales',
    'monthly_user_comparison' => 'Comparación Mensual de Usuarios',
    'this_month' => 'Este Mes',
    'last_month' => 'Mes Pasado',
    'total_commission' => 'Comisión Total'
];

foreach ($missing_translations as $key => $val) {
    $merged[$key] = $val;
}

$export = "<?php\n\nreturn " . var_export($merged, true) . ";\n";

file_put_contents(__DIR__ . '/../lang/es/admin/general.php', $export);
echo "Merged successfully.";
