@props([
    'status' => null,
    'label' => null,
    'size' => null,
])

@php
    $rawStatus = trim((string) ($status ?? $label ?? ''));
    $slotText = trim((string) $slot);
    $defaultLabel = str_replace('_', ' ', $rawStatus);
    $defaultLabel = $defaultLabel === mb_strtolower($defaultLabel)
        ? mb_convert_case($defaultLabel, MB_CASE_TITLE, 'UTF-8')
        : $defaultLabel;
    $displayLabel = $slotText !== ''
        ? $slotText
        : ($label ?? $defaultLabel);
    $normalizedStatus = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', mb_strtolower($rawStatus))));

    $variant = match (true) {
        in_array($normalizedStatus, [
            'verified',
            'approved',
            'qualified',
            'winner',
            'paid',
            'fully paid',
            'recorded',
            'active',
            'active login',
            'vacant',
            'available',
            'in stock',
        ], true) => 'success',
        in_array($normalizedStatus, [
            'pending',
            'pending payment',
            'for review',
            'ongoing',
            'under review',
            'submitted',
            'needs revision',
            'returned',
            'required',
        ], true) => 'warning',
        in_array($normalizedStatus, [
            'rejected',
            'not selected',
            'cancelled',
            'canceled',
            'missing',
            'deleted',
            'deactivated',
            'inactive',
            'archived',
        ], true) => 'danger',
        in_array($normalizedStatus, [
            'open',
            'open for application',
            'sold',
        ], true) => 'open',
        in_array($normalizedStatus, [
            'partially paid',
            'partial',
        ], true) => 'partial',
        in_array($normalizedStatus, [
            'draft',
            'saved',
            'draft saved',
            'draft restored',
        ], true) => 'draft',
        in_array($normalizedStatus, [
            'occupied',
            'closed',
            'unassigned',
            'no application',
            'no submission yet',
            'n/a',
        ], true) => 'neutral',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->class([
    'status-badge',
    'status-badge--' . $variant,
    'status-badge--' . $size => filled($size),
]) }}>
    {{ $displayLabel }}
</span>
