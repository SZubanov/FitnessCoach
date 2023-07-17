@extends('adminlte::page')

@section('title', 'Fitness Coach')

@section('content_header')
    <h1 class="m-0 text-dark">Дашборд</h1>
@stop

@section('content')
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="form-inline">
                        <label for="input1" class="mr-2">Input 1:</label>
                        <input type="text" class="form-control mr-2" id="input1">
                        <button type="button" class="btn btn-primary mr-2">Button 1</button>
                        <button type="button" class="btn btn-secondary">Button 2</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="form-inline">
                        <label for="input2" class="mr-2">Input 2:</label>
                        <input type="text" class="form-control mr-2" id="input2">
                        <button type="button" class="btn btn-primary mr-2">Button 1</button>
                        <button type="button" class="btn btn-secondary">Button 2</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h2>Title 3:</h2>
                    <div class="form-inline">
                        <button type="button" class="btn btn-primary mr-2">Button 1</button>
                        <button type="button" class="btn btn-secondary">Button 2</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
