@extends('layouts.app')

@section('title', 'Client List')
@section('page_header', 'Client List')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
    <li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
    #dt-controls .dt-buttons { display:flex; gap:6px; flex-wrap:wrap; }
    #dt-controls .btn { font-size:12px; }
    div.dataTables_wrapper div.dataTables_filter input { border-radius:8px; }
</style>
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus me-1"></i> Add Client
                    </a>
                    <div id="dt-controls" class="d-flex align-items-center gap-2"></div>
                </div>

                <table id="clients-table" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Client</th>
                            <th>Industry</th>
                            <th>Team</th>
                            <th>City</th>
                            <th width="110">Status</th>
                            <th width="70" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $index => $client)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->industry }}</td>
                            <td>{{ $client->team->name ?? '—' }}</td>
                            <td>{{ $client->city }}</td>
                            <td>{{ $client->status }}</td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="mdi mdi-dots-horizontal"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('clients.show', $client->id) }}">
                                                View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('clients.edit', $client->id) }}">
                                                Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('clients.settings', $client->id) }}">
                                                <i class="mdi mdi-cog-outline me-1"></i> Settings
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                            onclick="deleteClient({{ $client->id }}); return false;">
                                                Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
jQuery(function ($) {
    const exportCols = ':not(:last-child)'; // exclude the Action column from exports

    const table = $('#clients-table').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        columnDefs: [{ targets: -1, orderable: false, searchable: false }],
        language: { search: '', searchPlaceholder: 'Search clients…' },
    });

    new $.fn.dataTable.Buttons(table, {
        buttons: [
            { extend: 'copyHtml5',  text: '<i class="mdi mdi-content-copy"></i> Copy',  className: 'btn btn-sm btn-light',     exportOptions: { columns: exportCols } },
            { extend: 'csvHtml5',   text: '<i class="mdi mdi-file-delimited"></i> CSV', className: 'btn btn-sm btn-light',     exportOptions: { columns: exportCols }, title: 'Clients' },
            { extend: 'excelHtml5', text: '<i class="mdi mdi-file-excel"></i> Excel',   className: 'btn btn-sm btn-success',   exportOptions: { columns: exportCols }, title: 'Clients' },
            { extend: 'pdfHtml5',   text: '<i class="mdi mdi-file-pdf-box"></i> PDF',   className: 'btn btn-sm btn-danger',    exportOptions: { columns: exportCols }, title: 'Clients' },
            { extend: 'print',      text: '<i class="mdi mdi-printer"></i> Print',      className: 'btn btn-sm btn-secondary', exportOptions: { columns: exportCols }, title: 'Clients' },
        ]
    });
    table.buttons().container().appendTo('#dt-controls');
});

function deleteClient(id) {
    if (!confirm('Delete this client? This cannot be undone.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url('clients') }}/' + id;
    form.innerHTML = '@csrf' + '<input type="hidden" name="_method" value="DELETE">';
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
