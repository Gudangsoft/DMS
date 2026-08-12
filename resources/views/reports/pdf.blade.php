<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan {{ \App\Models\Setting::get('site_name', 'DMS') }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; color: #0b2545; margin-bottom: 0; }
        p.subtitle { color: #6b7280; margin-top: 2px; }
        h2 { font-size: 13px; color: #0b2545; border-bottom: 2px solid #d4af37; padding-bottom: 4px; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 8px; text-align: left; }
        th { background-color: #0b2545; color: #fff; }
        tr:nth-child(even) { background-color: #f9fafb; }
    </style>
</head>
<body>
    <h1>Laporan {{ \App\Models\Setting::get('site_name', 'Document Management System') }}</h1>
    <p class="subtitle">{{ \App\Models\Setting::get('site_tagline', 'STIE Kasih Bangsa') }} &middot; Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>

    <h2>Ringkasan Status</h2>
    <table>
        <tr><th>Pending</th><th>Approved</th><th>Published</th><th>Archived</th></tr>
        <tr>
            <td>{{ $statusSummary['pending'] }}</td>
            <td>{{ $statusSummary['approved'] }}</td>
            <td>{{ $statusSummary['published'] }}</td>
            <td>{{ $statusSummary['archived'] }}</td>
        </tr>
    </table>

    <h2>Jumlah Dokumen per Tahun</h2>
    <table>
        <tr><th>Tahun</th><th>Jumlah</th></tr>
        @foreach ($perYear as $row)
            <tr><td>{{ $row->year }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
    </table>

    <h2>Jumlah Dokumen per Unit</h2>
    <table>
        <tr><th>Unit</th><th>Jumlah</th></tr>
        @foreach ($perUnit as $row)
            <tr><td>{{ $row->unit_name }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
    </table>

    <h2>Jumlah Dokumen per Kategori</h2>
    <table>
        <tr><th>Kategori</th><th>Jumlah</th></tr>
        @foreach ($perCategory as $row)
            <tr><td>{{ $row->category_name }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
    </table>

    <h2>Dokumen Paling Banyak Didownload</h2>
    <table>
        <tr><th>Nomor</th><th>Judul</th><th>Jumlah Download</th></tr>
        @foreach ($mostDownloaded as $doc)
            <tr><td>{{ $doc->document_number ?? '-' }}</td><td>{{ $doc->title }}</td><td>{{ $doc->download_count }}</td></tr>
        @endforeach
    </table>

    <h2>Aktivitas User</h2>
    <table>
        <tr><th>User</th><th>Email</th><th>Jumlah Aktivitas</th></tr>
        @foreach ($userActivity as $row)
            <tr><td>{{ $row->causer?->name ?? 'Unknown' }}</td><td>{{ $row->causer?->email ?? '-' }}</td><td>{{ $row->total }}</td></tr>
        @endforeach
    </table>
</body>
</html>
