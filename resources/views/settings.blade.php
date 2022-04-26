@extends('adminlte::page')

@section('title', 'Fitness Coach')

@section('content_header')
    <h1 class="m-0 text-dark">Настройки</h1>
@stop

@isset($success)
    @dd(1)
@endisset

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="create" method="POST" enctype="multipart/form-data" role="form"
                          action="{{route('web.users.update', $user->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Имя<sup class="text-danger">*</sup></label>
                                        <input name="name" type="text" maxlength="255" class="form-control @error('name') is-invalid @enderror" required
                                               value="{{ isset($user) ? $user->name ?? '' : old('name') }}"
                                        >
                                        @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>Email<sup class="text-danger">*</sup></label>
                                        <input name="email" type="email" maxlength="255" class="form-control @error('email') is-invalid @enderror" required
                                               value="{{ isset($user) ? $user->email ?? '' : old('email') }}"
                                        >
                                        @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Пароль</label>
                                        <input name="password" type="password" maxlength="255" class="form-control @error('password') is-invalid @enderror">
                                        @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>Повторите пароль</label>
                                        <input name="password_confirmation" type="password" maxlength="255"
                                               class="form-control @error('password_confirmation') is-invalid @enderror">
                                    </div>
                                    @error('password_confirmation')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success modal-button-form">
                                Обновить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="create" method="POST" enctype="multipart/form-data" role="form"
                          action="{{route('web.admin.users.update', 1) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Код fatsecret<sup class="text-danger"></sup></label>
                                        <input name="fatsecret_code" type="text" maxlength="255" class="form-control"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-content-end flex-wrap">
                                    <div class="form-group">
                                        <button class="btn btn-success modal-button-form">
                                            Обновить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
