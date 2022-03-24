<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
{{--                    <label>{{ __('articles.datatable.title') }}<sup class="text-danger">*</sup></label>--}}
{{--                    <input name="{{$language}}[title]" type="text" maxlength="255" class="form-control" required--}}
{{--                           value="{{ isset($article) ? $article->translate($language)->title ?? '' : old($language.'title') }}"--}}
{{--                    >--}}
                </div>
            </div>
        </div>
    </div>
    {{--    <div class="modal-footer">--}}
    {{--        <button class="btn btn-success modal-button-form"--}}
    {{--                @if(isset($article)) onclick="Main.updateRecord('{{ route('admin.articles.update', $article->id) }}')"--}}
    {{--                @else onclick="Main.storeRecord('{{ route('admin.articles.store') }}')" @endif--}}
    {{--        >{{ __('articles.create.button') }}</button>--}}
    {{--        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">{{ __('articles.create.cancel') }}</button>--}}
    {{--    </div>--}}
</form>
