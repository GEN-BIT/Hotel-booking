<?php
/**
 * Breadcrumb Navigation
 * 
 * Usage:
 *   require_once __DIR__ . '/breadcrumb.php';
 *   add_breadcrumb('Rooms', BASE_URL . 'rooms/index.php');
 *   add_breadcrumb('Room Types', BASE_URL . 'admin/room-types/index.php');
 *   render_breadcrumbs();
 */

$breadcrumbs = [];

function add_breadcrumb($label, $url = null) {
    global $breadcrumbs;
    $breadcrumbs[] = [
        'label' => $label,
        'url' => $url,
    ];
}

function render_breadcrumbs() {
    global $breadcrumbs;
    
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb" aria-label="Breadcrumb" style="padding: 0.75rem 0;">';
    $html .= '<ol style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; list-style: none; margin: 0; padding: 0; font-size: 0.9rem;">';
    
    $count = count($breadcrumbs);
    foreach ($breadcrumbs as $i => $crumb) {
        $isLast = ($i === $count - 1);
        
        if ($isLast) {
            $html .= '<li style="color: var(--color-muted);" aria-current="page">' . htmlspecialchars($crumb['label']) . '</li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($crumb['url']) . '" style="color: var(--color-primary);">' . htmlspecialchars($crumb['label']) . '</a></li>';
            $html .= '<li style="color: var(--color-muted);" aria-hidden="true">/</li>';
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

function get_current_breadcrumb_label() {
    global $breadcrumbs;
    if (empty($breadcrumbs)) {
        return null;
    }
    return $breadcrumbs[count($breadcrumbs) - 1]['label'] ?? null;
}
