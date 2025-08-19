@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('FatSecret Connection Result') }}</div>
                
                <div class="card-body text-center">
                    @if($status === 'success')
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>{{ __('Success!') }}</h4>
                            <p class="mb-0">{{ $message }}</p>
                        </div>
                        
                        <div class="mt-4">
                            <p><strong>{{ __('Next Steps:') }}</strong></p>
                            <ol class="text-left">
                                <li>{{ __('Close this page') }}</li>
                                <li>{{ __('Return to the Telegram bot') }}</li>
                                <li>{{ __('Try the sync command') }}</li>
                            </ol>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h4>{{ __('Error!') }}</h4>
                            <p class="mb-0">{{ $message }}</p>
                        </div>
                        
                        <div class="mt-4">
                            <p><strong>{{ __('What you can do:') }}</strong></p>
                            <ul class="text-left">
                                <li>{{ __('Try again later') }}</li>
                                <li>{{ __('Check your internet connection') }}</li>
                                <li>{{ __('Contact support if the problem persists') }}</li>
                            </ul>
                        </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ config('app.url') }}" class="btn btn-primary">
                            <i class="fas fa-home"></i>
                            {{ __('Go to Homepage') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection