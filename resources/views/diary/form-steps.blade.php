<form id="create" method="POST" enctype="multipart/form-data" role="form">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Дата<sup class="text-danger">*</sup></label>
                    <input name="date" type="text" maxlength="255" class="form-control" required datepicker data-inputmask
                           value="{{ isset($diary) ? $diary->date ?? '' : old('kcal') }}"
                    >
                </div>
                <div class="form-group">
                    <label>Шаги<sup class="text-danger">*</sup></label>
                    <input name="steps" type="text" maxlength="255" class="form-control" required
                           value="{{ isset($diary) ? $diary->steps ?? '' : old('steps') }}"
                    >
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-success modal-button-form"
                @if(isset($diary)) onclick="Main.updateRecord('{{ route('web.admin.users.update', $diary->id) }}')"
                @else onclick="Main.storeRecord('{{ route('web.admin.users.store') }}')" @endif
        >Создать
        </button>
        <button type="button" class="btn btn-danger" onclick="Main.dissmissModal('#form')">Отмена</button>
    </div>
</form>
