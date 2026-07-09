<table>
    <thead>
        <tr><th colspan="2"><strong>Driver Dashboard – Summary</strong></th></tr>
    </thead>
    <tbody>
        @php $s = $summary ?? []; @endphp
        <tr><td>Total drivers</td><td>{{ $s['total'] ?? 0 }}</td></tr>
        <tr><td>Approved</td><td>{{ $s['approved'] ?? 0 }}</td></tr>
        <tr><td>Pending approval</td><td>{{ $s['pending'] ?? 0 }}</td></tr>
        <tr><td>Active</td><td>{{ $s['active'] ?? 0 }}</td></tr>
        <tr><td>New this month</td><td>{{ $s['new_this_month'] ?? 0 }}</td></tr>
        <tr><td>Documents expiring (7 days)</td><td>{{ $s['documents_expiring_7_days'] ?? 0 }}</td></tr>
        <tr><td>Documents expiring (30 days)</td><td>{{ $s['documents_expiring_30_days'] ?? 0 }}</td></tr>
        <tr><td>Negative balance</td><td>{{ $s['negative_balance'] ?? 0 }}</td></tr>
    </tbody>
</table>

<table>
    <thead>
        <tr><th>#</th><th>Driver name</th><th>Trips (last 30 days)</th></tr>
    </thead>
    <tbody>
        @forelse($top_drivers_by_trips ?? [] as $i => $row)
        <tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] }}</td><td>{{ $row['trip_count'] }}</td></tr>
        @empty
        <tr><td colspan="3">No data</td></tr>
        @endforelse
    </tbody>
</table>

<table>
    <thead>
        <tr><th>#</th><th>Driver name</th><th>Earnings (last 30 days)</th></tr>
    </thead>
    <tbody>
        @php $sym = $currency_symbol ?? ''; @endphp
        @forelse($top_drivers_by_earnings ?? [] as $i => $row)
        <tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] }}</td><td>{{ $sym }}{{ number_format((float)($row['earnings'] ?? 0), 2) }}</td></tr>
        @empty
        <tr><td colspan="3">No data</td></tr>
        @endforelse
    </tbody>
</table>
