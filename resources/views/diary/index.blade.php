@extends('adminlte::page')

@section('title', 'Fitness Coach')

@section('content_header')
    <h1 class="m-0 text-dark">Дневник</h1>
@stop

@section('content')
    @csrf
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card rounded">
                    <div class="card-header">КБЖУ</div>
                    <div class="card-body">
                        @isset($user)
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Дата<sup class="text-danger">*</sup></label>
                                    <input name="date-macros-fatsecret" type="text" maxlength="255" class="form-control" required
                                           datepicker data-inputmask>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Diary.storeRecord('{{ route('web.users.diary.fatsecret.macros') }}', 'date-macros-fatsecret')">
                                    Получить из FatSecret
                                </button>
                            </div>
                        </div>
                        <hr>
                        @endisset
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Main.createRecord('{{ route('web.users.diary.create.form.macros') }}')">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-4">
                <div class="card rounded">
                    <div class="card-header">Вес</div>
                    <div class="card-body">
                        @isset($user)
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Дата<sup class="text-danger">*</sup></label>
                                    <input name="date-weight-fatsecret" type="text" maxlength="255" class="form-control" required
                                           datepicker data-inputmask>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Diary.storeRecord('{{ route('web.users.diary.fatsecret.weight') }}', 'date-weight-fatsecret')">
                                    Получить из FatSecret
                                </button>
                            </div>
                        </div>
                        <hr>
                        @endisset
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Main.createRecord('{{ route('web.users.diary.create.form.weight') }}')">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card rounded">
                    <div class="card-header">Шаги</div>
                    <div class="card-body">
                        @isset($user)
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Дата<sup class="text-danger">*</sup></label>
                                    <input name="date" type="text" maxlength="255" class="form-control" required
                                           datepicker data-inputmask>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Main.createRecord('{{ route('web.users.diary.create.form.steps') }}')">
                                    Получить из FatSecret
                                </button>
                            </div>
                        </div>
                        <hr>
                        @endisset
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success btn-block"
                                        onclick="Main.createRecord('{{ route('web.users.diary.create.form.steps') }}')">
                                    Добавить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('/js/diary.js') }}"></script>
@endpush
