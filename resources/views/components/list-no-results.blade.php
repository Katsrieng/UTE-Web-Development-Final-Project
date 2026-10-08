@props(['module', 'clear'])
<x-empty-state icon="bi-search" :title="'No '.$module.' match your search and filters.'"><a href="{{ $clear }}" class="btn btn-sm btn-outline-secondary">Clear filters</a></x-empty-state>
