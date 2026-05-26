<?php

function get_status_steps()
{
    return [
        'menunggu' => 'Menunggu',
        'diverifikasi' => 'Diverifikasi',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai'
    ];
}

function render_status_stepper($currentStatus)
{
    if ($currentStatus === 'ditolak') {
        return '
            <div class="alert alert-danger mb-0">
                <strong>Laporan Ditolak</strong><br>
                Laporan tidak dapat diproses karena tidak memenuhi ketentuan.
            </div>
        ';
    }

    $steps = get_status_steps();
    $keys = array_keys($steps);
    $currentIndex = array_search($currentStatus, $keys);

    if ($currentIndex === false) {
        $currentIndex = 0;
    }

    $html = '<div class="status-stepper">';

    foreach ($steps as $key => $label) {
        $index = array_search($key, $keys);

        $class = '';

        if ($index < $currentIndex) {
            $class = 'completed';
        } elseif ($index === $currentIndex) {
            $class = 'active';
        }

        $html .= '
            <div class="step ' . $class . '">
                <div class="circle">' . ($index + 1) . '</div>
                <div class="label">' . $label . '</div>
            </div>
        ';
    }

    $html .= '</div>';

    return $html;
}