<table class="report-footer" role="presentation">
    <tr>
        <td>{{ $branding['tenant_name'] ?? $branding['company_name'] }}</td>
        <td class="footer-right">{{ $definition->title }} &middot; {{ count($rows) }} rows</td>
    </tr>
</table>
