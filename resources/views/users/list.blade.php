@extends('adminlte::page')

@section('title', 'Fitness Coach')

@section('content_header')
    <h1 class="m-0 text-dark">Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
                            <button type="button" class="btn btn-success"
                                    onclick="Main.createRecord('{{ route('web.users.create.form') }}')">
                                Создать
                            </button>
                        </div>
                        <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder=""
                                       onkeyup="Main.searchDataTable(dtListelements, this);"
                                       onchange="Main.searchDataTable(dtListelements, this);" data-name="filter">
                                <div class="input-group-append">
                                    <span class="btn btn-default">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-xs-12 col-sm-12 col-md-4 col-lg-4 col-form-label text-right">
                            <button class="btn btn-primary" onclick="Main.dropFilter()">Сброс</button>
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <table style="width: 100%;" class="table table-theme table-row v-middle table-text" id="listelement-table">
                        <thead>
                        @if(!empty($columns))
                            <tr>
                                @foreach ($columns as $column)
                                    <th>{{ $column['column_name'] }}</th>
                                @endforeach
                            </tr>
                        @endif
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        let dtListelements = $('#listelement-table').DataTable(Object.assign({
            ajax: {
                url: "{{ route('web.users.data.list') }}",
                data: function (d) {
                },
                error: function (data) {
                    toastr.error('Ошибка загрузки данных для таблицы. Пожалуйста, перезагрузите страницу');
                },
            },
            columns: {!! $jsonColumns !!},
            sDom: '<"top">rt<"bottom"p><"clear">',
        }, Main.commonConfigDt));

        dtListelements.on('draw', function () {
            Main.showModalElementDataTable('listelement-table');
        });
    </script>
@endsection
